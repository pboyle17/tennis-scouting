<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Player;
use App\Models\League;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SyncTrProfilesJob implements ShouldQueue
{
    use Queueable;

    protected $league;
    protected $teamIds;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 600; // 10 minutes

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(League $league, $teamIds = null)
    {
        $this->league = $league;
        $this->teamIds = $teamIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $league = $this->league;
        $teamIds = $this->teamIds;

        Log::info("=== TR PROFILES SYNC JOB STARTED ===", [
            'league_id' => $league->id,
            'league_name' => $league->name,
            'team_ids' => $teamIds,
            'memory_start' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ]);

        try {
            Log::info("Starting Tennis Record profiles sync for league: {$league->name}", [
                'league_id' => $league->id,
                'team_ids' => $teamIds
            ]);

            // Get players from specified teams or all teams in the league
            $query = Player::whereHas('teams', function ($q) use ($league, $teamIds) {
                $q->where('teams.league_id', $league->id);
                if ($teamIds) {
                    $q->whereIn('teams.id', $teamIds);
                }
            })->whereNotNull('tennis_record_link');

            $players = $query->get();

            Log::info("Query executed, found players", [
                'player_count' => $players->count(),
                'memory_current' => memory_get_usage(true) / 1024 / 1024 . ' MB'
            ]);

            if ($players->isEmpty()) {
                Log::warning("No players with Tennis Record links found", [
                    'league_id' => $league->id,
                    'team_ids' => $teamIds
                ]);
                return;
            }

            $totalPlayers = $players->count();
            $updatedCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $errors = [];

            Log::info("Starting to process players", [
                'total_players' => $totalPlayers
            ]);

            foreach ($players as $index => $player) {
                Log::debug("Processing player {$index}/{$totalPlayers}", [
                    'player_id' => $player->id,
                    'player_name' => "{$player->first_name} {$player->last_name}",
                    'memory' => memory_get_usage(true) / 1024 / 1024 . ' MB'
                ]);
                try {
                    // Skip if USTA rating was updated within the last 7 days
                    if ($player->usta_rating_updated_at && $player->usta_rating_updated_at->isAfter(now()->subDays(7))) {
                        Log::info("Skipping player - USTA rating updated recently", [
                            'player_id' => $player->id,
                            'player_name' => "{$player->first_name} {$player->last_name}",
                            'last_updated' => $player->usta_rating_updated_at->format('Y-m-d H:i:s'),
                            'days_ago' => $player->usta_rating_updated_at->diffInDays(now())
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    // Scrape the player's Tennis Record profile page
                    $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                    ])
                    ->timeout(30)
                    ->connectTimeout(10)
                    ->get($player->tennis_record_link);

                    if (!$response->successful()) {
                        Log::warning("Failed to fetch Tennis Record profile for player {$player->id}", [
                            'player_id' => $player->id,
                            'player_name' => "{$player->first_name} {$player->last_name}",
                            'status' => $response->status(),
                            'link' => $player->tennis_record_link
                        ]);
                        $errorCount++;
                        continue;
                    }

                    $html = $response->body();

                    // Try multiple patterns to find USTA rating
                    $patterns = [
                        // Pattern for rating in bold span (Tennis Record format) - most common
                        '/<span[^>]*font-weight:\s*bold[^>]*>([3-5]\.\d+)\s+([SCMTA])<\/span>/i',
                        '/Rating:\s*<[^>]+>([3-5]\.\d+)([SCMTA])<\/[^>]+>/i',
                        '/USTA\s+Rating:\s*([3-5]\.\d+)([SCMTA])/i',
                        '/<td[^>]*>Rating<\/td>\s*<td[^>]*>([3-5]\.\d+)([SCMTA])<\/td>/i',
                        // Generic pattern for rating directly in any table cell (no label)
                        '/<td[^>]*>\s*([3-5]\.\d+)\s*([SCMTA])\s*<\/td>/i',
                    ];

                    $ratingFound = false;
                    $rating = null;
                    $ratingType = null;

                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $html, $matches)) {
                            $rating = floatval($matches[1]);
                            $ratingType = strtoupper($matches[2]);
                            $ratingFound = true;
                            break;
                        }
                    }

                    if ($ratingFound && $rating >= 3.0 && $rating <= 5.0 && in_array($ratingType, ['S', 'C', 'A', 'M', 'T'])) {
                        // Update player's USTA rating and type
                        $player->USTA_rating = $rating;
                        $player->usta_rating_type = $ratingType;
                        $player->usta_rating_updated_at = now();
                        $player->save();

                        $updatedCount++;

                        Log::info("Updated USTA rating for player", [
                            'player_id' => $player->id,
                            'player_name' => "{$player->first_name} {$player->last_name}",
                            'rating' => $rating,
                            'rating_type' => $ratingType
                        ]);
                    } else {
                        Log::warning("Could not find valid USTA rating for player {$player->id}", [
                            'player_id' => $player->id,
                            'player_name' => "{$player->first_name} {$player->last_name}",
                            'link' => $player->tennis_record_link
                        ]);
                        $errorCount++;
                    }

                    // Small delay to avoid overwhelming the server
                    usleep(200000); // 0.2 seconds

                } catch (\Exception $e) {
                    Log::error("Error scraping Tennis Record profile for player {$player->id}: " . $e->getMessage(), [
                        'player_id' => $player->id,
                        'player_name' => "{$player->first_name} {$player->last_name}",
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errorCount++;
                    $errors[] = "{$player->first_name} {$player->last_name}";
                }
            }

            Log::info("Finished processing all players in loop", [
                'total_processed' => $totalPlayers,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
                'errors' => $errorCount
            ]);

            Log::info("=== TR PROFILES SYNC JOB COMPLETED SUCCESSFULLY ===", [
                'league_id' => $league->id,
                'league_name' => $league->name,
                'total_players' => $totalPlayers,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
                'errors' => $errorCount,
                'memory_end' => memory_get_usage(true) / 1024 / 1024 . ' MB'
            ]);

        } catch (\Exception $e) {
            Log::error("=== TR PROFILES SYNC JOB FAILED ===", [
                'league_id' => $league->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'memory' => memory_get_usage(true) / 1024 / 1024 . ' MB'
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("=== TR PROFILES SYNC JOB MARKED AS FAILED ===", [
            'league_id' => $this->league->id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
