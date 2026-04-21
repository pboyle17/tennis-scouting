<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RestoreFromS3BackupJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;
    public int $tries = 1;

    public function __construct(public readonly string $s3Key) {}

    public function handle(): void
    {
        Log::info('RestoreFromS3BackupJob: starting restore', ['s3_key' => $this->s3Key]);

        $db = $this->parseConnectionDetails();
        $dbHost     = $db['host'];
        $dbPort     = $db['port'];
        $dbName     = $db['database'];
        $dbUser     = $db['username'];
        $dbPassword = $db['password'];

        $filename  = basename($this->s3Key);
        $localPath = storage_path("app/backups/{$filename}");

        if (!file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        // Download from S3
        $backupContent = Storage::disk('s3')->get($this->s3Key);
        file_put_contents($localPath, $backupContent);
        Log::info('RestoreFromS3BackupJob: downloaded from S3', ['local_path' => $localPath]);

        DB::disconnect('pgsql');

        putenv("PGPASSWORD={$dbPassword}");

        // Terminate existing connections
        $terminateCommand = sprintf(
            "psql -h %s -p %s -U %s -d postgres -c \"SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = %s AND pid <> pg_backend_pid();\" 2>&1",
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbName)
        );
        exec($terminateCommand, $terminateOutput, $terminateReturnCode);

        // Drop foreign keys from excluded tables so players table can be recreated
        $dropFkCommand = sprintf(
            "psql -h %s -p %s -U %s -d %s -c \"ALTER TABLE IF EXISTS rackets DROP CONSTRAINT IF EXISTS rackets_player_id_foreign; ALTER TABLE IF EXISTS string_jobs DROP CONSTRAINT IF EXISTS string_jobs_racket_id_foreign;\" 2>&1",
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbName)
        );
        exec($dropFkCommand, $dropFkOutput, $dropFkReturnCode);
        Log::info('RestoreFromS3BackupJob: dropped FK constraints', ['return_code' => $dropFkReturnCode]);

        // Build filtered table-of-contents excluding rackets/string_jobs
        $filteredTocPath = storage_path('app/backups/toc_filtered.txt');
        $listCommand = sprintf('pg_restore -l %s 2>&1', escapeshellarg($localPath));
        exec($listCommand, $tocOutput, $listReturnCode);

        $filteredToc = array_filter($tocOutput, fn($line) =>
            !preg_match('/rackets/', $line) && !preg_match('/string_jobs/', $line)
        );
        file_put_contents($filteredTocPath, implode("\n", $filteredToc));

        // Restore
        $restoreCommand = sprintf(
            'pg_restore -h %s -p %s -U %s -d %s --clean --if-exists --no-owner --no-privileges -L %s -v %s 2>&1',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbName),
            escapeshellarg($filteredTocPath),
            escapeshellarg($localPath)
        );
        exec($restoreCommand, $restoreOutput, $restoreReturnCode);

        if (file_exists($filteredTocPath)) {
            unlink($filteredTocPath);
        }

        // Clean up orphaned rackets/string_jobs
        $cleanupCommand = sprintf(
            "psql -h %s -p %s -U %s -d %s -c \"DELETE FROM string_jobs WHERE racket_id IN (SELECT r.id FROM rackets r LEFT JOIN players p ON r.player_id = p.id WHERE p.id IS NULL); DELETE FROM rackets WHERE player_id NOT IN (SELECT id FROM players);\" 2>&1",
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbName)
        );
        exec($cleanupCommand, $cleanupOutput, $cleanupReturnCode);
        Log::info('RestoreFromS3BackupJob: cleaned up orphans', ['return_code' => $cleanupReturnCode]);

        // Reset sequences for preserved tables
        $resetSeqCommand = sprintf(
            "psql -h %s -p %s -U %s -d %s -c \"SELECT setval('rackets_id_seq', COALESCE((SELECT MAX(id) FROM rackets), 0) + 1, false); SELECT setval('string_jobs_id_seq', COALESCE((SELECT MAX(id) FROM string_jobs), 0) + 1, false);\" 2>&1",
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbName)
        );
        exec($resetSeqCommand, $resetSeqOutput, $resetSeqReturnCode);

        // Re-add foreign keys
        $addFkCommand = sprintf(
            "psql -h %s -p %s -U %s -d %s -c \"ALTER TABLE rackets ADD CONSTRAINT rackets_player_id_foreign FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE; ALTER TABLE string_jobs ADD CONSTRAINT string_jobs_racket_id_foreign FOREIGN KEY (racket_id) REFERENCES rackets(id) ON DELETE CASCADE;\" 2>&1",
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbName)
        );
        exec($addFkCommand, $addFkOutput, $addFkReturnCode);

        putenv('PGPASSWORD');
        unlink($localPath);

        DB::purge('pgsql');
        DB::reconnect('pgsql');

        if ($restoreReturnCode !== 0) {
            Log::error('RestoreFromS3BackupJob: restore failed', [
                'return_code' => $restoreReturnCode,
                'output'      => $restoreOutput,
            ]);
            throw new \RuntimeException('pg_restore failed with code ' . $restoreReturnCode);
        }

        Log::info('RestoreFromS3BackupJob: restore complete', ['file' => $filename]);
    }

    private function parseConnectionDetails(): array
    {
        $databaseUrl = env('DATABASE_URL');

        if (!$databaseUrl) {
            return [
                'host'     => config('database.connections.pgsql.host'),
                'port'     => config('database.connections.pgsql.port'),
                'database' => config('database.connections.pgsql.database'),
                'username' => config('database.connections.pgsql.username'),
                'password' => config('database.connections.pgsql.password'),
            ];
        }

        $url = parse_url($databaseUrl);

        return [
            'host'     => $url['host'] ?? 'localhost',
            'port'     => $url['port'] ?? '5432',
            'database' => ltrim($url['path'] ?? '', '/'),
            'username' => $url['user'] ?? '',
            'password' => $url['pass'] ?? '',
        ];
    }
}
