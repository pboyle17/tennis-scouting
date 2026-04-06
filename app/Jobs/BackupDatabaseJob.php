<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BackupDatabaseJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        try {
            $db = $this->parseConnectionDetails();
            $dbHost = $db['host'];
            $dbPort = $db['port'];
            $dbName = $db['database'];
            $dbUser = $db['username'];
            $dbPassword = $db['password'];

            $env = app()->environment();
            $timestamp = now()->format('Y-m-d_His');
            $filename = "{$env}_backup_{$dbName}_{$timestamp}.sql";
            $localPath = storage_path("app/backups/{$filename}");

            if (!file_exists(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }

            putenv("PGPASSWORD={$dbPassword}");

            $command = sprintf(
                'pg_dump -h %s -p %s -U %s -d %s -F c --exclude-table=rackets --exclude-table=string_jobs -f %s 2>&1',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbName),
                escapeshellarg($localPath)
            );

            exec($command, $output, $returnCode);
            putenv('PGPASSWORD');

            if ($returnCode !== 0) {
                \Log::error('Scheduled database backup failed', [
                    'output' => $output,
                    'return_code' => $returnCode,
                ]);
                return;
            }

            $s3Path = "backups/{$filename}";
            \Storage::disk('s3')->put($s3Path, file_get_contents($localPath));
            unlink($localPath);

            \Log::info("Scheduled database backup uploaded to S3: {$filename}");

        } catch (\Exception $e) {
            \Log::error('Scheduled database backup error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function parseConnectionDetails(): array
    {
        $databaseUrl = env('DATABASE_URL');

        if (!$databaseUrl) {
            return [
                'host' => config('database.connections.pgsql.host'),
                'port' => config('database.connections.pgsql.port'),
                'database' => config('database.connections.pgsql.database'),
                'username' => config('database.connections.pgsql.username'),
                'password' => config('database.connections.pgsql.password'),
            ];
        }

        $url = parse_url($databaseUrl);

        return [
            'host' => $url['host'] ?? 'localhost',
            'port' => $url['port'] ?? '5432',
            'database' => ltrim($url['path'] ?? '', '/'),
            'username' => $url['user'] ?? '',
            'password' => $url['pass'] ?? '',
        ];
    }
}
