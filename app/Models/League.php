<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class League extends Model
{
    protected $fillable = ['name', 'usta_link', 'tennis_record_link', 'NTRP_rating', 'is_combo', 'active', 'daily_update', 'daily_update_time', 'last_utr_update_status', 'last_utr_update_message'];

    protected $casts = [
        'active' => 'boolean',
        'daily_update' => 'boolean',
        'utr_last_updated_at' => 'datetime',
        'teams_last_synced_at' => 'datetime',
        'last_daily_run_at' => 'datetime',
    ];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Name of the job batch used for this league's update jobs.
     */
    public function updateBatchName(): string
    {
        return 'league-update-' . $this->id;
    }

    /**
     * Queue a full league update (UTRs, team syncs, match syncs) as one job batch.
     * Returns counts of what was queued.
     */
    public function dispatchUpdate(): array
    {
        $jobs = [];

        $utrIds = [];
        foreach ($this->teams as $team) {
            $utrIds = array_merge($utrIds, $team->players()->whereNotNull('utr_id')->pluck('utr_id')->toArray());
        }
        $utrIds = array_unique($utrIds);
        if (!empty($utrIds)) {
            $utrJobKey = 'utr_update_' . uniqid();
            $jobs[] = new \App\Jobs\UpdateUtrRatingsJob($utrIds, $utrJobKey, $this->id, skipUpdatedToday: true);
            // Remember the UTR job's progress key so the update's progress can include per-player UTR progress
            Cache::put('league_update_utr_' . $this->id, ['key' => $utrJobKey, 'players' => count($utrIds)], now()->addDay());
            $this->utr_last_updated_at = now();
        }

        $teamsToSync = $this->teams()->whereNotNull('tennis_record_link')->get();
        foreach ($teamsToSync as $team) {
            $jobs[] = new \App\Jobs\SyncTeamFromTennisRecordJob($team);
        }
        if ($teamsToSync->isNotEmpty()) {
            $this->teams_last_synced_at = now();
        }

        $teamIds = $this->teams->pluck('id');
        $matches = TennisMatch::where(function ($q) use ($teamIds) {
            $q->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds);
        })->whereNotNull('tennis_record_match_link')->get();
        foreach ($matches as $match) {
            $jobs[] = new \App\Jobs\SyncMatchFromTennisRecordJob($match);
        }

        if (!empty($jobs)) {
            Bus::batch($jobs)->name($this->updateBatchName())->allowFailures()->dispatch();
        }

        $this->save();

        return ['teams' => $teamsToSync->count(), 'matches' => $matches->count()];
    }

    /**
     * Progress of any unfinished update batches for the given leagues, keyed by league id.
     * The UTR job counts one step per player (from its own progress), every other job counts as one step.
     */
    public static function runningUpdates($leagueIds): array
    {
        $names = collect($leagueIds)->mapWithKeys(fn($id) => ['league-update-' . $id => $id]);

        return DB::table('job_batches')
            ->whereIn('name', $names->keys())
            ->whereNull('finished_at')
            ->whereNull('cancelled_at')
            ->where('pending_jobs', '>', DB::raw('failed_jobs'))
            ->get()
            ->groupBy('name')
            ->mapWithKeys(function ($batches, $name) use ($names) {
                $leagueId = $names[$name];
                $jobsTotal = $batches->sum('total_jobs');
                $jobsDone = $jobsTotal - $batches->sum(fn($b) => $b->pending_jobs - $b->failed_jobs);

                $utrTotal = 0;
                $utrDone = 0;
                $syncTotal = $jobsTotal;
                $syncDone = $jobsDone;

                if ($utr = Cache::get('league_update_utr_' . $leagueId)) {
                    $progress = Cache::get($utr['key']);
                    if ($progress) {
                        $utrTotal = $progress['total'];
                        $utrFinished = $progress['status'] === 'completed';
                        // A run stopped early (e.g. rate limited) is still finished
                        $utrDone = $utrFinished ? $utrTotal : $progress['processed'];
                    } else {
                        // Not started yet, or finished long enough ago that its progress expired
                        $utrTotal = $utr['players'];
                        $utrFinished = $jobsDone > 0;
                        $utrDone = $utrFinished ? $utrTotal : 0;
                    }
                    $syncTotal = $jobsTotal - 1;
                    $syncDone = max(0, $jobsDone - ($utrFinished ? 1 : 0));
                }

                return [$leagueId => [
                    'total' => $utrTotal + $syncTotal,
                    'done' => $utrDone + $syncDone,
                    'utr_total' => $utrTotal,
                    'utr_done' => $utrDone,
                    'sync_total' => $syncTotal,
                    'sync_done' => $syncDone,
                    'started_at' => \Carbon\Carbon::createFromTimestamp($batches->min('created_at')),
                ]];
            })
            ->all();
    }
}
