<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Player;
use Illuminate\Support\Facades\Log;
use App\Services\UtrService;

class UpdateUtrRatingsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
      $utrService = app(UtrService::class);
      $players = Player::all();

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

            Log::info("Sample UTR data (last player)", $data);
    }
}
