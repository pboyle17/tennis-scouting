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
