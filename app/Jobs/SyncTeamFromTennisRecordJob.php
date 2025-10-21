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

class SyncTeamFromTennisRecordJob implements ShouldQueue
{
    use Queueable;

    protected $team;
    protected $jobKey;

    /**
     * Create a new job instance.
     */
    public function __construct(Team $team, $jobKey = null)
    {
        $this->team = $team;
        $this->jobKey = $jobKey ?? 'tennis_record_sync_' . uniqid();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobKey = $this->jobKey;
        $team = $this->team;

        try {
            Log::info("Starting team sync from Tennis Record link: {$team->tennis_record_link}");

            if (!$team->tennis_record_link) {
                throw new \Exception('Team does not have a Tennis Record link');
            }

            // Step 1: Scrape Tennis Record page
            $this->updateProgress($jobKey, 'Scraping Tennis Record page...', 0, 5);
            $scrapingService = app(TennisRecordScrapingService::class);
            $teamData = $scrapingService->scrapeTeamData($team->tennis_record_link);

            if (empty($teamData['players'])) {
                throw new \Exception('No players found on the Tennis Record page');
            }

            $totalPlayers = count($teamData['players']);
            $playersAdded = 0;
            $playersRemoved = 0;
            $playersUpdated = 0;

            // Step 2: Process players from Tennis Record
            $this->updateProgress($jobKey, 'Processing players...', 1, 3, [
                'team_name' => $team->name,
                'total_players' => $totalPlayers
            ]);

            // Get current team player IDs
            $currentPlayerIds = $team->players->pluck('id')->toArray();

            // Track which players we find on Tennis Record
            $foundPlayerIds = [];

            foreach ($teamData['players'] as $index => $playerData) {
                // Find or create player
                $existingPlayer = Player::where('first_name', $playerData['first_name'])
                                       ->where('last_name', $playerData['last_name'])
                                       ->first();

                if ($existingPlayer) {
                    $player = $existingPlayer;

                    // Update USTA ratings
                    $updated = false;
                    if (isset($playerData['USTA_rating']) && $playerData['USTA_rating']) {
                        $player->USTA_rating = $playerData['USTA_rating'];
                        $updated = true;
                    }
                    if (isset($playerData['USTA_dynamic_rating']) && $playerData['USTA_dynamic_rating']) {
                        $player->USTA_dynamic_rating = $playerData['USTA_dynamic_rating'];
                        $updated = true;
                    }

                    if ($updated) {
                        $player->save();
                        $playersUpdated++;
                    }

                    // Add to team if not already on team
                    if (!in_array($player->id, $currentPlayerIds)) {
                        $team->players()->attach($player->id);
                        $playersAdded++;
                    }
                } else {
                    // Create new player
                    $newPlayerData = [
                        'first_name' => $playerData['first_name'],
                        'last_name' => $playerData['last_name']
                    ];

                    if (isset($playerData['USTA_rating']) && $playerData['USTA_rating']) {
                        $newPlayerData['USTA_rating'] = $playerData['USTA_rating'];
                    }
                    if (isset($playerData['USTA_dynamic_rating']) && $playerData['USTA_dynamic_rating']) {
                        $newPlayerData['USTA_dynamic_rating'] = $playerData['USTA_dynamic_rating'];
                    }

                    $player = Player::create($newPlayerData);
                    $team->players()->attach($player->id);
                    $playersAdded++;
                }

                $foundPlayerIds[] = $player->id;

                // Update progress
                $this->updateProgress($jobKey, 'Processing players...', 1, 3, [
                    'team_name' => $team->name,
                    'total_players' => $totalPlayers,
                    'players_added' => $playersAdded,
                    'players_updated' => $playersUpdated,
                    'current_player' => $playerData['first_name'] . ' ' . $playerData['last_name']
                ]);

                usleep(100000); // 0.1 seconds
            }

            // Step 3: Remove players no longer on the roster
            $this->updateProgress($jobKey, 'Removing players no longer on roster...', 2, 3);

            $playersToRemove = array_diff($currentPlayerIds, $foundPlayerIds);
            if (!empty($playersToRemove)) {
                $team->players()->detach($playersToRemove);
                $playersRemoved = count($playersToRemove);
            }

            // Mark as completed
            $this->updateProgress($jobKey, 'Sync completed!', 3, 3, [
                'team_name' => $team->name,
                'team_id' => $team->id,
                'total_players' => $totalPlayers,
                'players_added' => $playersAdded,
                'players_removed' => $playersRemoved,
                'players_updated' => $playersUpdated
            ], 'completed');

            // Clear the running flag
            Cache::forget('tennis_record_team_sync_running_' . $team->id);

            Log::info("Team sync completed successfully", [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'players_added' => $playersAdded,
                'players_removed' => $playersRemoved,
                'players_updated' => $playersUpdated
            ]);

        } catch (\Exception $e) {
            $this->updateProgress($jobKey, 'Error: ' . $e->getMessage(), 0, 3, [], 'failed');
            Cache::forget('tennis_record_team_sync_running_' . $team->id);
            Log::error("Team sync failed: " . $e->getMessage(), [
                'team_id' => $team->id,
                'tennis_record_link' => $team->tennis_record_link,
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
        Cache::put("tennis_record_team_sync_progress_{$jobKey}", [
            'status' => $status,
            'message' => $message,
            'step' => $step,
            'total_steps' => $totalSteps,
            'percentage' => ($step / $totalSteps) * 100,
            'data' => $data
        ], 600); // Cache for 10 minutes
    }
}
