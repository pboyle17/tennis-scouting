<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\League;
use App\Models\Player;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\UtrService;
use App\Exceptions\UtrRateLimitException;
use Carbon\Carbon;

class UpdateUtrRatingsJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1800; // 30 minutes

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    protected $playerIds;
    protected $jobKey;
    protected $leagueId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $playerIds = [], $jobKey = null, ?int $leagueId = null)
    {
        $this->playerIds = $playerIds;
        $this->jobKey = $jobKey ?? 'utr_update_' . uniqid();
        $this->leagueId = $leagueId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
      $utrService = app(UtrService::class);
      $query = Player::query();

        if (!empty($this->playerIds)) {
            $query->whereIn('utr_id', $this->playerIds);
        }

      // Most stale ratings first, so if we get rate limited and have to stop
      // early, the players who most need an update are the ones who got it.
      // Never-updated players (NULL on both columns) are the most stale of all.
      $players = $query
          ->orderByRaw('LEAST(utr_singles_updated_at, utr_doubles_updated_at) ASC NULLS FIRST')
          ->get();
      $total = $players->count();
      $processed = 0;
      $updated = 0;
      $failed = 0;

      // Set initial status
      Cache::put($this->jobKey, [
          'status' => 'processing',
          'total' => $total,
          'processed' => 0,
          'updated' => 0,
          'failed' => 0,
      ], 300); // 5 minutes

      $rateLimited = false;

      foreach ($players as $player) {
          try {
            $data = $utrService->fetchUtrRating($player->utr_id);
            $player->utr_singles_rating = $data['singlesUtr'];
            $player->utr_doubles_rating = $data['doublesUtr'];

            // Set reliability flags - only true if reliability is exactly 100
            $player->utr_singles_reliable = isset($data['ratingProgressSingles']) && $data['ratingProgressSingles'] == 100;
            $player->utr_doubles_reliable = isset($data['ratingProgressDoubles']) && $data['ratingProgressDoubles'] == 100;

            // Set updated timestamps
            $player->utr_singles_updated_at = now();
            $player->utr_doubles_updated_at = now();

            $player->save();
            $updated++;
          } catch (UtrRateLimitException $e) {
              // Once UTR starts rate limiting us, every further request in this
              // run will 429 too (it's a quota, not a burst limit) — stop
              // immediately instead of burning through the rest of the list.
              Log::warning("Stopping UTR update early: rate limited after {$processed}/{$total} players. " . $e->getMessage());
              $rateLimited = true;
              $failed += ($total - $processed);
              break;
          } catch (\Exception $e) {
              Log::error("UTR update failed for player {$player->id}: " . $e->getMessage());
              $failed++;
          }

          $processed++;

          // Update progress
          Cache::put($this->jobKey, [
              'status' => 'processing',
              'total' => $total,
              'processed' => $processed,
              'updated' => $updated,
              'failed' => $failed,
          ], 300);

          // Throttle in batches of 20 requests, then pause a minute, rather
          // than spacing every request evenly. No need to wait after the
          // last player in the list.
          if ($processed % 20 === 0 && $processed < $total) {
              sleep(60);
          }
      }

      // Mark as completed
      Cache::put($this->jobKey, [
          'status' => 'completed',
          'total' => $total,
          'processed' => $processed,
          'updated' => $updated,
          'failed' => $failed,
      ], 300);

      Log::info(
        "UTR update completed. Total: {$total}, Updated: {$updated}, Failed: {$failed}"
    );

      $this->recordLeagueStatus($total, $updated, $failed, $rateLimited);
    }

    /**
     * Surface the outcome of this run on the league, so a broken daily update
     * (e.g. UTR rate limiting) is visible on the league list instead of only
     * showing up in the logs.
     */
    protected function recordLeagueStatus(int $total, int $updated, int $failed, bool $rateLimited): void
    {
        if (!$this->leagueId || $total === 0) {
            return;
        }

        $league = League::find($this->leagueId);
        if (!$league) {
            return;
        }

        if ($rateLimited) {
            $status = 'error';
            $message = "UTR update stopped early: rate limited by UTR API after {$updated}/{$total} players updated.";
        } elseif ($failed === 0) {
            $status = 'ok';
            $message = null;
        } elseif ($failed === $total) {
            $status = 'error';
            $message = "UTR update failed for all {$total} players.";
        } else {
            $status = 'warning';
            $message = "UTR update failed for {$failed} of {$total} players.";
        }

        $league->update([
            'last_utr_update_status' => $status,
            'last_utr_update_message' => $message,
        ]);
    }
}
