<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\League;
use App\Models\Team;
use App\Models\Player;
use App\Services\TennisRecordScrapingService;
use App\Services\UtrService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CreateTeamsFromTennisRecordLeagueJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1800; // 30 minutes

    protected $leagueId;
    protected $jobKey;

    /**
     * Create a new job instance.
     */
    public function __construct($leagueId, $jobKey = null)
    {
        $this->leagueId = $leagueId;
        $this->jobKey = $jobKey ?? 'tennis_record_league_job_' . uniqid();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobKey = $this->jobKey;

        try {
            Log::info("Starting teams creation from Tennis Record league for league ID: {$this->leagueId}");

            // Load the league
            $league = League::findOrFail($this->leagueId);

            if (!$league->tennis_record_link) {
                throw new \Exception('League does not have a Tennis Record link');
            }

            // Step 1: Scrape league page to get team links
            $scrapingService = app(TennisRecordScrapingService::class);
            $teamsData = $scrapingService->scrapeLeagueTeams($league->tennis_record_link);

            if (empty($teamsData)) {
                throw new \Exception('No teams found on the Tennis Record league page');
            }

            $totalTeams = count($teamsData);

            $teamsProcessed = 0;
            $teamsCreated = 0;
            $teamsExisting = 0;
            $teamIds = [];

            // Step 2: Process each team
            foreach ($teamsData as $teamData) {
                try {
                    $teamName = $teamData['name'];
                    $teamLink = $teamData['link'];


                    // Check if team already exists by tennis_record_link
                    $existingTeam = Team::where('tennis_record_link', $teamLink)->first();

                    if ($existingTeam) {
                        Log::info("Team already exists: {$teamName}", ['team_id' => $existingTeam->id]);
                        $team = $existingTeam;
                        $teamsExisting++;
                    } else {
                        // Create new team by scraping its page

                        $teamFullData = $scrapingService->scrapeTeamData($teamLink);

                        // Create the team
                        $team = Team::create([
                            'name' => $teamFullData['team_name'],
                            'tennis_record_link' => $teamLink
                        ]);

                        Log::info("Created team: {$team->name}", ['team_id' => $team->id]);
                        $teamsCreated++;

                        // Create players if any
                        if (!empty($teamFullData['players'])) {
                            $this->createPlayersForTeam($team, $teamFullData['players'], $jobKey, $teamName);
                        }

                        // Small delay between team creations
                        sleep(1);
                    }

                    // Add team to league if not already in it
                    if ($team->league_id !== $league->id) {
                        $team->league_id = $league->id;
                        $team->save();
                        Log::info("Added team {$team->name} to league {$league->name}");
                    }

                    $teamIds[] = $team->id;
                    $teamsProcessed++;

                } catch (\Exception $e) {
                    Log::error("Failed to process team {$teamData['name']}: " . $e->getMessage());
                    // Continue with next team
                    $teamsProcessed++;
                }
            }

            // Mark as completed

            // Clear the running flag
            Cache::forget('tennis_record_league_creation_running_' . $league->id);

            Log::info("League teams creation completed successfully", [
                'league_id' => $league->id,
                'league_name' => $league->name,
                'teams_created' => $teamsCreated,
                'teams_existing' => $teamsExisting,
                'teams_processed' => $teamsProcessed
            ]);

        } catch (\Exception $e) {
            Cache::forget('tennis_record_league_creation_running_' . $this->leagueId);
            Log::error("League teams creation failed: " . $e->getMessage(), [
                'league_id' => $this->leagueId,
                'error' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create players for a team
     */
    private function createPlayersForTeam($team, $playersData, $jobKey, $teamName)
    {
        $totalPlayers = count($playersData);
        $playersCreated = 0;
        $playersFound = 0;

        foreach ($playersData as $playerData) {
            // Check if player already exists
            $existingPlayer = Player::where('first_name', $playerData['first_name'])
                                   ->where('last_name', $playerData['last_name'])
                                   ->first();

            if ($existingPlayer) {
                // Update USTA ratings and Tennis Record link if they exist in the scraped data
                if (isset($playerData['USTA_rating']) && $playerData['USTA_rating']) {
                    $existingPlayer->USTA_rating = $playerData['USTA_rating'];
                }
                if (isset($playerData['USTA_dynamic_rating']) && $playerData['USTA_dynamic_rating']) {
                    $existingPlayer->USTA_dynamic_rating = $playerData['USTA_dynamic_rating'];
                }
                if (isset($playerData['tennis_record_link']) && $playerData['tennis_record_link']) {
                    $existingPlayer->tennis_record_link = $playerData['tennis_record_link'];
                }
                $existingPlayer->tennis_record_last_sync = now();
                $existingPlayer->save();

                // Assign existing player to team
                $team->players()->syncWithoutDetaching([$existingPlayer->id]);
                $playersFound++;
            } else {
                // Create new player with USTA ratings and Tennis Record link
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
                if (isset($playerData['tennis_record_link']) && $playerData['tennis_record_link']) {
                    $newPlayerData['tennis_record_link'] = $playerData['tennis_record_link'];
                }
                $newPlayerData['tennis_record_last_sync'] = now();

                $player = Player::create($newPlayerData);

                // Assign to team
                $team->players()->attach($player->id);
                $playersCreated++;
            }
        }

        Log::info("Created players for team {$teamName}", [
            'team_id' => $team->id,
            'players_created' => $playersCreated,
            'players_found' => $playersFound
        ]);
    }
}
