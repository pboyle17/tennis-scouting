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

      // Only include players created > 24h ago AND updated > 24h ago (or never updated)
        // $cutoff = Carbon::now()->subDay();

        // $query->where('created_at', '<', $cutoff)
        //       ->where(function ($q) use ($cutoff) {
        //           $q->whereNull('updated_at')
        //             ->orWhere('updated_at', '<', $cutoff);
        //       });

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
