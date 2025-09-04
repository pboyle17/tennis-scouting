<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Player;
use Illuminate\Support\Facades\Log;
use App\Services\UtrService;
use Carbon\Carbon;

class UpdateUtrRatingsJob implements ShouldQueue
{
    use Queueable;

    protected $playerIds;

    /**
     * Create a new job instance.
     */
    public function __construct(array $playerIds = [])
    {
        $this->playerIds = $playerIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
      $utrService = app(UtrService::class);
      $query = Player::query();

        if (!empty($this->playerIds)) {
            $query->whereIn('id', $this->playerIds);
        }

      // Only include players created > 24h ago AND updated > 24h ago (or never updated)
        $cutoff = Carbon::now()->subDay();

        $query->where('created_at', '<', $cutoff)
              ->where(function ($q) use ($cutoff) {
                  $q->whereNull('updated_at')
                    ->orWhere('updated_at', '<', $cutoff);
              });

      $players = $query->get();

      foreach ($players as $player) {
          try {
            $data = $utrService->fetchUtrRating($player->utr_id);
            $player->utr_singles_rating = $data['singlesUtr'];
            $player->utr_doubles_rating = $data['doublesUtr'];
            $player->save();
          } catch (\Exception $e) {
              Log::error("UTR update failed for player {$player->id}: " . $e->getMessage());
          }
      }

            Log::info("Sample UTR data for " . ($data['firstName'] ?? 'Unknown') . " " . ($data['lastName'] ?? 'Unknown'), $data ?? ['note' => 'No data returned']
);
    }
}
