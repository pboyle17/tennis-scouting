<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Database backups in S3 (backups/backup_{dbname}_{Y-m-d_His}.sql).
 */
class BackupStore
{
    /**
     * Newest backup by the timestamp in its filename: ['key', 'filename', 'database', 'at'].
     * $excludeDatabase skips backups taken from that database (e.g. this app's own database).
     */
    public function latest(?string $excludeDatabase = null): ?array
    {
        $latest = null;
        foreach (Storage::disk('s3')->files('backups') as $key) {
            if (!preg_match('/backup_(.+?)_(\d{4}-\d{2}-\d{2}_\d{6})\.sql$/', basename($key), $m)) {
                continue;
            }
            if ($excludeDatabase && $m[1] === $excludeDatabase) {
                continue;
            }
            $at = Carbon::createFromFormat('Y-m-d_His', $m[2], config('app.timezone'));
            if (!$latest || $at->gt($latest['at'])) {
                $latest = ['key' => $key, 'filename' => basename($key), 'database' => $m[1], 'at' => $at];
            }
        }
        return $latest;
    }
}
