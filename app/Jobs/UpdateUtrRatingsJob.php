<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Player;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\UtrService;
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

    /**
     * Create a new job instance.
     */
    public function __construct(array $playerIds = [], $jobKey = null)
    {
        $this->playerIds = $playerIds;
        $this->jobKey = $jobKey ?? 'utr_update_' . uniqid();
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

      $players = $query->get();
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

      foreach ($players as $player) {
          try {
            $data = $utrService->fetchUtrRating($player->utr_id);
            $player->utr_singles_rating = $data['singlesUtr'];
            $player->utr_doubles_rating = $data['doublesUtr'];

            // Set reliability flags - only true if reliability is exactly 100
            $player->utr_singles_reliable = isset($data['ratingProgressSingles']) && $data['ratingProgressSingles'] == 100;
            $player->utr_doubles_reliable = isset($data['ratingProgressDoubles']) && $data['ratingProgressDoubles'] == 100;

            $player->save();
            $updated++;
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
    }
}
