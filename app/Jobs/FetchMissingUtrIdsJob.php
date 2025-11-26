<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Player;
use Illuminate\Support\Facades\Log;
use App\Services\UtrService;
use Illuminate\Support\Facades\Cache;

class FetchMissingUtrIdsJob implements ShouldQueue
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

        // Only get players without UTR IDs
        $players = Player::whereNull('utr_id')->get();
        $totalPlayers = $players->count();
        $processed = 0;

        // Initialize progress tracking
        $jobId = $this->job->getJobId();
        Cache::put("utr_search_progress_{$jobId}", [
            'total' => $totalPlayers,
            'processed' => 0,
            'status' => 'processing',
            'current_player' => null,
            'found_count' => 0,
            'not_found_count' => 0
        ], 300); // Cache for 5 minutes

        $foundCount = 0;
        $notFoundCount = 0;

        foreach ($players as $player) {
            try {
                // Update current player being processed
                Cache::put("utr_search_progress_{$jobId}", [
                    'total' => $totalPlayers,
                    'processed' => $processed,
                    'status' => 'processing',
                    'current_player' => $player->first_name . ' ' . $player->last_name,
                    'found_count' => $foundCount,
                    'not_found_count' => $notFoundCount
                ], 300);

                $playerName = $player->first_name . ' ' . $player->last_name;
                $searchResults = $utrService->searchPlayers($playerName, 5);

                // Look for exact or close matches
                $bestMatch = $this->findBestMatch($player, $searchResults);

                if ($bestMatch) {
                    $player->utr_id = $bestMatch['id'];
                    $player->utr_singles_rating = $bestMatch['singlesUtr'] ?? null;
                    $player->utr_doubles_rating = $bestMatch['doublesUtr'] ?? null;

                    // Set reliability flags - only true if reliability is exactly 100
                    $player->utr_singles_reliable = isset($bestMatch['ratingProgressSingles']) && $bestMatch['ratingProgressSingles'] == 100;
                    $player->utr_doubles_reliable = isset($bestMatch['ratingProgressDoubles']) && $bestMatch['ratingProgressDoubles'] == 100;

                    // Set updated timestamps
                    $player->utr_singles_updated_at = now();
                    $player->utr_doubles_updated_at = now();

                    $player->save();
                    $foundCount++;

                    Log::info("Found UTR ID for {$playerName}: {$bestMatch['id']} (Match: {$bestMatch['displayName']}, Singles: {$bestMatch['singlesUtr']}, Doubles: {$bestMatch['doublesUtr']})");
                } else {
                    $notFoundCount++;
                    Log::info("No UTR match found for {$playerName}");
                }

                $processed++;

                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 seconds

            } catch (\Exception $e) {
                Log::error("UTR search failed for player {$player->id}: " . $e->getMessage());
                $notFoundCount++;
                $processed++;
            }
        }

        // Mark as completed
        Cache::put("utr_search_progress_{$jobId}", [
            'total' => $totalPlayers,
            'processed' => $processed,
            'status' => 'completed',
            'current_player' => null,
            'found_count' => $foundCount,
            'not_found_count' => $notFoundCount
        ], 300);

        // Clear the running flag
        Cache::forget('utr_search_running');

        Log::info("UTR ID search completed. Found: {$foundCount}, Not found: {$notFoundCount}");
    }

    /**
     * Find the best match from search results
     */
    private function findBestMatch($player, $searchResults)
    {
        if (!isset($searchResults['hits']) || empty($searchResults['hits'])) {
            return null;
        }

        $playerFirstName = strtolower(trim($player->first_name));
        $playerLastName = strtolower(trim($player->last_name));

        // If there's only one result and names match, use it automatically
        if (count($searchResults['hits']) === 1 && isset($searchResults['hits'][0]['source'])) {
            $source = $searchResults['hits'][0]['source'];
            $resultFirstName = strtolower(trim($source['firstName'] ?? ''));
            $resultLastName = strtolower(trim($source['lastName'] ?? ''));

            // Check if names match
            if ($resultFirstName === $playerFirstName && $resultLastName === $playerLastName) {
                Log::info("Auto-selecting single matching UTR result for {$player->first_name} {$player->last_name}");
                return $source;
            }
        }

        foreach ($searchResults['hits'] as $result) {
            if (!isset($result['source'])) {
                continue;
            }

            $source = $result['source'];
            $resultFirstName = strtolower(trim($source['firstName'] ?? ''));
            $resultLastName = strtolower(trim($source['lastName'] ?? ''));

            // Exact match on both names
            if ($resultFirstName === $playerFirstName && $resultLastName === $playerLastName) {
                return $source;
            }
        }

        // If no exact match, look for close matches (same last name, first name starts with same letter)
        foreach ($searchResults['hits'] as $result) {
            if (!isset($result['source'])) {
                continue;
            }

            $source = $result['source'];
            $resultFirstName = strtolower(trim($source['firstName'] ?? ''));
            $resultLastName = strtolower(trim($source['lastName'] ?? ''));

            // Same last name and first name starts with same letter
            if ($resultLastName === $playerLastName &&
                !empty($resultFirstName) && !empty($playerFirstName) &&
                substr($resultFirstName, 0, 1) === substr($playerFirstName, 0, 1)) {
                return $source;
            }
        }

        return null; // No good match found
    }
}
