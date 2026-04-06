<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
        // Dispatch UTR update
        $utrIds = [];
        foreach ($league->teams as $team) {
            $utrIds = array_merge($utrIds, $team->players()->whereNotNull('utr_id')->pluck('utr_id')->toArray());
        }
        $utrIds = array_unique($utrIds);
        if (!empty($utrIds)) {
            \App\Jobs\UpdateUtrRatingsJob::dispatch($utrIds, 'utr_update_' . uniqid());
            $league->utr_last_updated_at = now();
        }

        // Dispatch team syncs
        $teamsToSync = $league->teams()->whereNotNull('tennis_record_link')->get();
        foreach ($teamsToSync as $team) {
            \App\Jobs\SyncTeamFromTennisRecordJob::dispatch($team);
        }
        if ($teamsToSync->isNotEmpty()) {
            $league->teams_last_synced_at = now();
        }

        // Dispatch match detail syncs
        $teamIds = $league->teams->pluck('id');
        $matches = \App\Models\TennisMatch::where(function ($q) use ($teamIds) {
            $q->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds);
        })->whereNotNull('tennis_record_match_link')->get();
        foreach ($matches as $match) {
            \App\Jobs\SyncMatchFromTennisRecordJob::dispatch($match);
        }

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

        if ($pendingCount === 0) {
            \App\Jobs\BackupDatabaseJob::dispatch();
            Log::info('All scheduled league updates complete — database backup dispatched.');
        }
    }
})->everyMinute()->name('daily-league-updates')->withoutOverlapping();
