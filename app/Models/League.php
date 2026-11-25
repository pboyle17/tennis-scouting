<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class League extends Model
{
    protected $fillable = ['name', 'usta_link', 'tennis_record_link', 'NTRP_rating', 'is_combo', 'active', 'daily_update', 'daily_update_time', 'last_utr_update_status', 'last_utr_update_message', 'last_schedule_sync_status', 'last_schedule_sync_message'];

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
     * Queue a full league update as job batches that run in stages, each starting once the
     * previous one finishes (even with failures): team rosters, then the schedule sync, then
     * match details and UTRs. Match syncs only find players on the teams' rosters, so rosters
     * go first. Returns counts of what will be synced.
     */
    public function dispatchUpdate(): array
    {
        $teamsToSync = $this->teams()->whereNotNull('tennis_record_link')->get();
        if ($teamsToSync->isNotEmpty()) {
            $this->teams_last_synced_at = now();
        }
        $this->save();

        $leagueId = $this->id;
        $this->dispatchUpdateStage(
            $teamsToSync->map(fn($team) => new \App\Jobs\SyncTeamFromTennisRecordJob($team))->all(),
            static fn() => League::find($leagueId)?->dispatchScheduleStage()
        );

        return ['teams' => $teamsToSync->count(), 'matches' => $this->matchesWithTennisRecordLink()->count()];
    }

    /**
     * Stage 2: re-read the team schedule pages, the only source for match times
     * (match results pages show just a date), to pick up new and rescheduled matches.
     */
    public function dispatchScheduleStage(): void
    {
        $leagueId = $this->id;
        $jobs = $this->teams()->whereNotNull('tennis_record_link')->exists()
            ? [new \App\Jobs\SyncTeamMatchesJob($this)]
            : [];
        $this->dispatchUpdateStage($jobs, static fn() => League::find($leagueId)?->dispatchDetailsStage());
    }

    /**
     * Stage 3: match details and UTRs, now that rosters and the schedule are current.
     */
    public function dispatchDetailsStage(): void
    {
        $jobs = [];

        $utrIds = $this->teams()->with('players')->get()
            ->flatMap(fn($team) => $team->players->whereNotNull('utr_id')->pluck('utr_id'))
            ->unique()->values()->all();
        if (!empty($utrIds)) {
            $utrJobKey = 'utr_update_' . uniqid();
            $jobs[] = new \App\Jobs\UpdateUtrRatingsJob($utrIds, $utrJobKey, $this->id, skipUpdatedToday: true);
            // Remember the UTR job's progress key so the update's progress can include per-player UTR progress
            Cache::put('league_update_utr_' . $this->id, ['key' => $utrJobKey, 'players' => count($utrIds)], now()->addDay());
            $this->utr_last_updated_at = now();
            $this->save();
        }

        foreach ($this->matchesWithTennisRecordLink()->get() as $match) {
            $jobs[] = new \App\Jobs\SyncMatchFromTennisRecordJob($match);
        }

        $this->dispatchUpdateStage($jobs);
    }

    /**
     * Run one stage of the update as a batch, then the next stage when it finishes.
     */
    private function dispatchUpdateStage(array $jobs, ?\Closure $next = null): void
    {
        if (empty($jobs)) {
            $next && $next();
            return;
        }

        $batch = Bus::batch($jobs)->name($this->updateBatchName())->allowFailures();
        if ($next) {
            $batch->finally($next);
        }
        $batch->dispatch();
    }

    private function matchesWithTennisRecordLink()
    {
        $teamIds = $this->teams()->pluck('id');
        return TennisMatch::where(function ($q) use ($teamIds) {
            $q->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds);
        })->whereNotNull('tennis_record_match_link');
    }

    /**
     * Problems from the last roster and schedule syncs for the leagues list notice,
     * as ['status' => 'error'|'warning', 'message' => ...], or null when both were clean.
     */
    public function teamSyncProblem(): ?array
    {
        $messages = [];

        $syncedTeams = $this->teams->whereNotNull('tennis_record_link');
        $rosterFailures = $syncedTeams->whereNotNull('last_roster_sync_error');
        if ($rosterFailures->isNotEmpty()) {
            $messages[] = "Roster sync failed for {$rosterFailures->count()} of {$syncedTeams->count()} teams: "
                . $rosterFailures->pluck('name')->join(', ') . '. '
                . 'Last error: ' . rtrim(\Illuminate\Support\Str::limit($rosterFailures->first()->last_roster_sync_error, 150), '.') . '.';
        }

        if ($this->last_schedule_sync_status) {
            $messages[] = $this->last_schedule_sync_message;
        }

        if (empty($messages)) {
            return null;
        }

        $allFailed = $rosterFailures->count() === $syncedTeams->count() || $this->last_schedule_sync_status === 'error';
        return ['status' => $allFailed ? 'error' : 'warning', 'message' => implode(' ', $messages)];
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
