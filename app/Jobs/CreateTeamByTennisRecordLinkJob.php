<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Team;
use App\Models\Player;
use App\Services\TennisRecordScrapingService;
use App\Services\UtrService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CreateTeamByTennisRecordLinkJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    protected $tennisRecordLink;
    protected $jobKey;

    /**
     * Create a new job instance.
     */
    public function __construct($tennisRecordLink, $jobKey = null)
    {
        $this->tennisRecordLink = $tennisRecordLink;
        $this->jobKey = $jobKey ?? 'tennis_record_job_' . uniqid();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobKey = $this->jobKey;

        try {
            Log::info("Starting team creation from Tennis Record link: {$this->tennisRecordLink}");

            // Step 1: Scrape Tennis Record page
            $this->updateProgress($jobKey, 'Scraping Tennis Record page...', 0, 5);
            $scrapingService = app(TennisRecordScrapingService::class);
            $teamData = $scrapingService->scrapeTeamData($this->tennisRecordLink);

            if (empty($teamData['players'])) {
                throw new \Exception('No players found on the Tennis Record page');
            }

            // Step 2: Create team
            $this->updateProgress($jobKey, 'Creating team...', 1, 5);
            $team = Team::create([
                'name' => $teamData['team_name'],
                'tennis_record_link' => $this->tennisRecordLink
            ]);

            $totalPlayers = count($teamData['players']);
            $playersCreated = 0;
            $playersFound = 0;
            $utrIdsFound = 0;
            $ratingsUpdated = 0;

            // Step 3: Create players and assign to team
            $this->updateProgress($jobKey, 'Creating players...', 2, 5, [
                'team_name' => $team->name,
                'total_players' => $totalPlayers,
                'players_created' => 0
            ]);

            $createdPlayerIds = [];

            foreach ($teamData['players'] as $index => $playerData) {
                // Check if player already exists
                $existingPlayer = Player::where('first_name', $playerData['first_name'])
                                       ->where('last_name', $playerData['last_name'])
                                       ->first();

                if ($existingPlayer) {
                    // Update USTA ratings if they exist in the scraped data
                    if (isset($playerData['USTA_rating']) && $playerData['USTA_rating']) {
                        $existingPlayer->USTA_rating = $playerData['USTA_rating'];
                    }
                    if (isset($playerData['USTA_dynamic_rating']) && $playerData['USTA_dynamic_rating']) {
                        $existingPlayer->USTA_dynamic_rating = $playerData['USTA_dynamic_rating'];
                    }
                    $existingPlayer->save();

                    // Assign existing player to team
                    $team->players()->syncWithoutDetaching([$existingPlayer->id]);
                    $playersFound++;
                    $createdPlayerIds[] = $existingPlayer->id;
                } else {
                    // Create new player with USTA ratings
                    $newPlayerData = [
                        'first_name' => $playerData['first_name'],
                        'last_name' => $playerData['last_name']
                    ];

                    // Add USTA ratings if they exist
                    if (isset($playerData['USTA_rating']) && $playerData['USTA_rating']) {
                        $newPlayerData['USTA_rating'] = $playerData['USTA_rating'];
                    }
                    if (isset($playerData['USTA_dynamic_rating']) && $playerData['USTA_dynamic_rating']) {
                        $newPlayerData['USTA_dynamic_rating'] = $playerData['USTA_dynamic_rating'];
                    }

                    $player = Player::create($newPlayerData);

                    // Assign to team
                    $team->players()->attach($player->id);
                    $createdPlayerIds[] = $player->id;
                    $playersCreated++;
                }

                // Update progress
                $this->updateProgress($jobKey, 'Creating players...', 2, 5, [
                    'team_name' => $team->name,
                    'total_players' => $totalPlayers,
                    'players_created' => $playersCreated,
                    'players_found' => $playersFound,
                    'current_player' => $playerData['first_name'] . ' ' . $playerData['last_name']
                ]);

                usleep(100000); // 0.1 seconds
            }

            // Step 4: Fetch UTR IDs for players without them
            $this->updateProgress($jobKey, 'Searching for UTR IDs...', 3, 5, [
                'team_name' => $team->name,
                'total_players' => $totalPlayers,
                'players_created' => $playersCreated,
                'players_found' => $playersFound
            ]);

            $playersNeedingUtrIds = Player::whereIn('id', $createdPlayerIds)
                                         ->whereNull('utr_id')
                                         ->get();

            if ($playersNeedingUtrIds->count() > 0) {
                $utrService = app(UtrService::class);

                foreach ($playersNeedingUtrIds as $index => $player) {
                    try {
                        $this->updateProgress($jobKey, 'Searching for UTR IDs...', 3, 5, [
                            'team_name' => $team->name,
                            'current_player' => $player->first_name . ' ' . $player->last_name,
                            'utr_ids_found' => $utrIdsFound,
                            'searching_count' => $index + 1,
                            'total_to_search' => $playersNeedingUtrIds->count()
                        ]);

                        $playerName = $player->first_name . ' ' . $player->last_name;
                        $searchResults = $utrService->searchPlayers($playerName, 5);

                        $bestMatch = $this->findBestMatch($player, $searchResults);

                        if ($bestMatch) {
                            $player->utr_id = $bestMatch['id'];
                            $player->save();
                            $utrIdsFound++;

                            Log::info("Found UTR ID for {$playerName}: {$bestMatch['id']}");
                        }

                        usleep(500000); // 0.5 seconds

                    } catch (\Exception $e) {
                        Log::error("UTR search failed for player {$player->id}: " . $e->getMessage());
                    }
                }
            }

            // Step 5: Fetch UTR ratings for players with UTR IDs
            $this->updateProgress($jobKey, 'Fetching UTR ratings...', 4, 5, [
                'team_name' => $team->name,
                'utr_ids_found' => $utrIdsFound
            ]);

            $playersWithUtrIds = Player::whereIn('id', $createdPlayerIds)
                                     ->whereNotNull('utr_id')
                                     ->get();

            if ($playersWithUtrIds->count() > 0) {
                $utrService = app(UtrService::class);

                foreach ($playersWithUtrIds as $index => $player) {
                    try {
                        $this->updateProgress($jobKey, 'Fetching UTR ratings...', 4, 5, [
                            'team_name' => $team->name,
                            'current_player' => $player->first_name . ' ' . $player->last_name,
                            'ratings_updated' => $ratingsUpdated,
                            'updating_count' => $index + 1,
                            'total_to_update' => $playersWithUtrIds->count()
                        ]);

                        $data = $utrService->fetchUtrRating($player->utr_id);
                        $player->utr_singles_rating = $data['singlesUtr'];
                        $player->utr_doubles_rating = $data['doublesUtr'];
                        $player->save();
                        $ratingsUpdated++;

                        usleep(500000); // 0.5 seconds

                    } catch (\Exception $e) {
                        Log::error("UTR rating fetch failed for player {$player->id}: " . $e->getMessage());
                    }
                }
            }

            // Mark as completed
            $this->updateProgress($jobKey, 'Completed!', 5, 5, [
                'team_name' => $team->name,
                'team_id' => $team->id,
                'total_players' => $totalPlayers,
                'players_created' => $playersCreated,
                'players_found' => $playersFound,
                'utr_ids_found' => $utrIdsFound,
                'ratings_updated' => $ratingsUpdated
            ], 'completed');

            // Clear the running flag
            Cache::forget('tennis_record_team_creation_running');

            Log::info("Team creation completed successfully", [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'players_created' => $playersCreated,
                'players_found' => $playersFound,
                'utr_ids_found' => $utrIdsFound,
                'ratings_updated' => $ratingsUpdated
            ]);

        } catch (\Exception $e) {
            $this->updateProgress($jobKey, 'Error: ' . $e->getMessage(), 0, 5, [], 'failed');
            Cache::forget('tennis_record_team_creation_running');
            Log::error("Team creation failed: " . $e->getMessage(), [
                'tennis_record_link' => $this->tennisRecordLink,
                'error' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update progress in cache
     */
    private function updateProgress($jobKey, $message, $step, $totalSteps, $data = [], $status = 'processing')
    {
        Cache::put("tennis_record_team_creation_progress_{$jobKey}", [
            'status' => $status,
            'message' => $message,
            'step' => $step,
            'total_steps' => $totalSteps,
            'percentage' => ($step / $totalSteps) * 100,
            'data' => $data
        ], 600); // Cache for 10 minutes
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

        // If no exact match, look for close matches
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

        return null;
    }
}
