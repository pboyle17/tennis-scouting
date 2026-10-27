<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $currentTime = now()->format('H:i');
    $leagues = \App\Models\League::where('daily_update', true)
        ->where('daily_update_time', $currentTime)
        ->get();

    foreach ($leagues as $league) {
        $league->dispatchUpdate();

        $league->last_daily_run_at = now();
        $league->save();

        Log::info("Scheduled league update dispatched for: {$league->name}");
    }

    // If any leagues ran, check whether all daily-scheduled leagues have now run today
    if ($leagues->isNotEmpty()) {
        $pendingCount = \App\Models\League::where('daily_update', true)
            ->where(function ($q) {
                $q->whereNull('last_daily_run_at')
                  ->orWhereDate('last_daily_run_at', '<', today());
            })
            ->count();

        $backupKey = 'backup_dispatched_' . today()->format('Y-m-d');
        if ($pendingCount === 0 && !app()->isProduction() && !Cache::has($backupKey)) {
            \App\Jobs\BackupDatabaseJob::dispatch();
            Cache::put($backupKey, true, now()->endOfDay());
            Log::info('All scheduled league updates complete — database backup dispatched.');
        }

        // Take weekly rating snapshots on Fridays
        $snapshotKey = 'rating_snapshot_' . today()->format('Y-m-d');
        if (now()->isFriday() && $pendingCount === 0 && !Cache::has($snapshotKey)) {
            $today = today();
            \App\Models\Player::whereNotNull('utr_singles_rating')
                ->orWhereNotNull('utr_doubles_rating')
                ->orWhereNotNull('USTA_dynamic_rating')
                ->get()
                ->each(function ($player) use ($today) {
                    \App\Models\PlayerRatingSnapshot::updateOrCreate(
                        ['player_id' => $player->id, 'snapshot_date' => $today],
                        [
                            'utr_singles_rating'  => $player->utr_singles_rating,
                            'utr_doubles_rating'  => $player->utr_doubles_rating,
                            'usta_dynamic_rating' => $player->USTA_dynamic_rating,
                        ]
                    );
                });
            Cache::put($snapshotKey, true, now()->endOfDay());
            Log::info('Friday rating snapshot saved for all players.');
        }
    }
})->everyMinute()->name('daily-league-updates')->withoutOverlapping();

Artisan::command('worker:scale-down', function () {
    $scaledDown = app(\App\Services\HerokuWorkerScaler::class)->scaleDownIfIdle();
    $this->info($scaledDown ? 'Queue empty, worker scaled down.' : 'Jobs still queued, keeping the worker running.');
})->purpose('Scale the Heroku worker dyno to 0 when the queue is empty');

Artisan::command('backups:restore-latest {--hour= : Only run when it is this hour (0-23) in the app timezone} {--dry-run : Only show which backup would be restored}', function (\App\Services\BackupStore $backups) {
    // Locally the newest "other" backup is production's, and restoring it would wipe local data
    if (app()->isLocal() && !$this->option('dry-run')) {
        $this->error('Refusing to restore over the local database. Use --dry-run to preview.');
        return 1;
    }

    $hour = $this->option('hour');
    if ($hour !== null && now()->hour !== (int) $hour) {
        $this->info('Not ' . $hour . ':00 ' . config('app.timezone') . ' yet, skipping.');
        return;
    }

    // Skip backups taken from this app's own database; we want the newest copy of the source database
    $ownDatabase = config('database.connections.pgsql.database');
    if ($url = env('DATABASE_URL')) {
        $ownDatabase = ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/');
    }

    $latest = $backups->latest(excludeDatabase: $ownDatabase);
    if (!$latest) {
        $this->warn('No backups found in S3.');
        return;
    }

    if ($this->option('dry-run')) {
        $this->info("Would restore {$latest['filename']} (taken {$latest['at']->toDayDateTimeString()}).");
        return;
    }

    $this->info("Restoring {$latest['filename']} (taken {$latest['at']->toDayDateTimeString()})...");
    // Run in this process rather than queueing it, so no worker dyno is needed
    \App\Jobs\RestoreFromS3BackupJob::dispatchSync($latest['key']);
    $this->info('Restore complete.');
})->purpose('Restore this database from the newest backup in S3 (used by Heroku Scheduler)');
