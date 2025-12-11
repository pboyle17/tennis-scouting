<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\League;
use App\Models\Team;
use App\Models\TennisMatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncTeamMatchesJob implements ShouldQueue
{
    use Queueable;

    protected $league;
    protected $jobKey;
    protected $teamId;

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
    public function __construct(League $league, $jobKey = null, $teamId = null)
    {
        $this->league = $league;
        $this->jobKey = $jobKey ?? 'team_matches_sync_' . uniqid();
        $this->teamId = $teamId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobKey = $this->jobKey;
        $league = $this->league;

        Log::info("=== TEAM MATCHES SYNC JOB STARTED ===", [
            'job_key' => $jobKey,
            'league_id' => $league->id,
            'league_name' => $league->name,
            'memory_start' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ]);

        try {
            $this->updateProgress($jobKey, 'Starting team matches sync...', 0, 100, [
                'matches_created' => 0,
                'matches_updated' => 0,
                'errors' => 0
            ]);

            // Get teams to sync
            $teamsToSync = $this->teamId
                ? $league->teams->where('id', $this->teamId)
                : $league->teams;

            if ($teamsToSync->isEmpty()) {
                throw new \Exception("No teams found to sync");
            }

            Log::info("Teams to sync", [
                'team_count' => $teamsToSync->count(),
                'team_names' => $teamsToSync->pluck('name')->toArray()
            ]);

            $allMatches = [];
            $teamIndex = 0;

            // Loop through each team and fetch their schedule
            foreach ($teamsToSync as $team) {
                $teamIndex++;
                $progress = 10 + (($teamIndex / $teamsToSync->count()) * 30);

                $this->updateProgress($jobKey, "Fetching matches for {$team->name}...", $progress, 100, [
                    'current_team' => $team->name,
                    'team_progress' => "{$teamIndex}/{$teamsToSync->count()}",
                    'matches_created' => 0,
                    'matches_updated' => 0,
                    'errors' => 0
                ]);

                // Check if team has tennis record link
                if (!$team->tennis_record_link) {
                    Log::warning("Team does not have Tennis Record link", [
                        'team_id' => $team->id,
                        'team_name' => $team->name
                    ]);
                    continue;
                }

                Log::info("Fetching team page", [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'url' => $team->tennis_record_link
                ]);

                // Fetch the team's Tennis Record page
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])
                ->timeout(30)
                ->connectTimeout(10)
                ->get($team->tennis_record_link);

                if (!$response->successful()) {
                    Log::error("Failed to fetch Tennis Record team page", [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'status' => $response->status()
                    ]);
                    continue;
                }

                $html = $response->body();

                // Parse the HTML to extract match data for this team
                $teamMatches = $this->parseScheduleTable($html, $league, $team->id);

                Log::info("Parsed schedule for team", [
                    'team_name' => $team->name,
                    'matches_found' => count($teamMatches)
                ]);

                $allMatches = array_merge($allMatches, $teamMatches);

                // Small delay between requests
                usleep(200000); // 0.2 seconds
            }

            Log::info("Finished fetching all team schedules", [
                'total_matches' => count($allMatches)
            ]);

            $this->updateProgress($jobKey, 'Creating/updating matches...', 40, 100, [
                'total_matches' => count($allMatches),
                'matches_created' => 0,
                'matches_updated' => 0,
                'errors' => 0
            ]);

            $createdCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];
            $scoreConflicts = [];

            // Process each match
            foreach ($allMatches as $index => $matchData) {
                try {
                    $progress = 40 + (($index + 1) / count($allMatches)) * 50;
                    $this->updateProgress($jobKey, "Processing match " . ($index + 1) . " of " . count($allMatches), $progress, 100, [
                        'total_matches' => count($allMatches),
                        'matches_created' => $createdCount,
                        'matches_updated' => $updatedCount,
                        'errors' => $errorCount,
                        'score_conflicts' => count($scoreConflicts)
                    ]);

                    // Check if match already exists to detect score conflicts
                    $existingMatch = TennisMatch::where([
                        'league_id' => $league->id,
                        'home_team_id' => $matchData['home_team_id'],
                        'away_team_id' => $matchData['away_team_id'],
                        'start_time' => $matchData['start_time']
                    ])->first();

                    $hasScoreConflict = false;
                    if ($existingMatch &&
                        $existingMatch->home_score !== null &&
                        $existingMatch->away_score !== null &&
                        ($matchData['home_score'] !== null || $matchData['away_score'] !== null) &&
                        ($existingMatch->home_score != $matchData['home_score'] || $existingMatch->away_score != $matchData['away_score'])) {

                        // Score conflict detected
                        $hasScoreConflict = true;
                        $scoreConflicts[] = [
                            'match_id' => $existingMatch->id,
                            'home_team_name' => $matchData['home_team_name'],
                            'away_team_name' => $matchData['away_team_name'],
                            'start_time' => $matchData['start_time']->format('Y-m-d H:i:s'),
                            'current_home_score' => $existingMatch->home_score,
                            'current_away_score' => $existingMatch->away_score,
                            'new_home_score' => $matchData['home_score'],
                            'new_away_score' => $matchData['away_score']
                        ];

                        Log::warning("Score conflict detected", $scoreConflicts[count($scoreConflicts) - 1]);
                    }

                    // Find or create the match (skip score update if there's a conflict)
                    $updateData = [
                        'location' => $matchData['location'],
                        'external_id' => $matchData['external_id'] ?? null,
                        'tennis_record_match_link' => $matchData['tennis_record_match_link'] ?? null
                    ];

                    // Only update scores if there's no conflict
                    if (!$hasScoreConflict) {
                        $updateData['home_score'] = $matchData['home_score'] ?? null;
                        $updateData['away_score'] = $matchData['away_score'] ?? null;
                    }

                    $match = TennisMatch::updateOrCreate(
                        [
                            'league_id' => $league->id,
                            'home_team_id' => $matchData['home_team_id'],
                            'away_team_id' => $matchData['away_team_id'],
                            'start_time' => $matchData['start_time']
                        ],
                        $updateData
                    );

                    if ($match->wasRecentlyCreated) {
                        $createdCount++;
                    } else {
                        $updatedCount++;
                    }

                    Log::info("Processed match", [
                        'match_id' => $match->id,
                        'home_team' => $matchData['home_team_name'],
                        'away_team' => $matchData['away_team_name'],
                        'start_time' => $matchData['start_time'],
                        'location' => $matchData['location'],
                        'was_created' => $match->wasRecentlyCreated
                    ]);

                } catch (\Exception $e) {
                    Log::error("Error processing match: " . $e->getMessage(), [
                        'match_data' => $matchData,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errorCount++;
                    $errors[] = "Match between {$matchData['home_team_name']} and {$matchData['away_team_name']}";
                }
            }

            // Mark as completed
            $message = "Team matches sync completed. Created {$createdCount}, updated {$updatedCount}";
            if (count($scoreConflicts) > 0) {
                $conflictCount = count($scoreConflicts);
                $message .= ", {$conflictCount} score conflict(s) need review";
            }
            if ($errorCount > 0) {
                $message .= ", {$errorCount} error(s) occurred";
                if (!empty($errors) && count($errors) <= 5) {
                    $message .= " for: " . implode(', ', $errors);
                }
                $message .= ". Check logs for details.";
            } else {
                $message .= " successfully!";
            }

            $this->updateProgress($jobKey, $message, 100, 100, [
                'total_matches' => count($allMatches),
                'matches_created' => $createdCount,
                'matches_updated' => $updatedCount,
                'errors' => $errorCount,
                'error_names' => $errors,
                'score_conflicts' => $scoreConflicts
            ], 'completed');

            // Store score conflicts in cache with league-specific key
            if (count($scoreConflicts) > 0) {
                $conflictsKey = "score_conflicts_league_{$league->id}";
                Cache::put($conflictsKey, $scoreConflicts, 3600); // Store for 1 hour
            }

            Log::info("=== TEAM MATCHES SYNC JOB COMPLETED SUCCESSFULLY ===", [
                'job_key' => $jobKey,
                'league_id' => $league->id,
                'league_name' => $league->name,
                'total_matches' => count($allMatches),
                'created' => $createdCount,
                'updated' => $updatedCount,
                'errors' => $errorCount,
                'memory_end' => memory_get_usage(true) / 1024 / 1024 . ' MB'
            ]);

        } catch (\Exception $e) {
            $this->updateProgress($jobKey, 'Error: ' . $e->getMessage(), 0, 100, [], 'failed');
            Log::error("=== TEAM MATCHES SYNC JOB FAILED ===", [
                'job_key' => $jobKey,
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
     * Parse the schedule table from the HTML
     * Expected columns: Local Schedule, Time, Opponent, Match Site, Result
     */
    private function parseScheduleTable($html, $league, $teamId = null)
    {
        $matches = [];
        $preliminaryMatches = []; // Store matches before determining home/away

        // Get all teams in the league for matching
        $teams = $league->teams;
        $teamsByName = [];
        foreach ($teams as $team) {
            $teamsByName[strtolower($team->name)] = $team;
        }

        Log::info("Teams in league", [
            'team_count' => count($teams),
            'team_names' => array_keys($teamsByName),
            'team_id_filter' => $teamId
        ]);

        // Find the schedule table by looking for the header row, then extract just that table
        $scheduleTableHtml = null;

        // First, find all tables
        if (preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $html, $allTables)) {
            Log::info("Found tables on page", ['table_count' => count($allTables[1])]);

            // Check each table for the schedule headers
            foreach ($allTables[1] as $tableIndex => $tableHtml) {
                // Check if this table has the schedule headers
                if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableHtml, $tableRows)) {
                    foreach ($tableRows[1] as $rowHtml) {
                        if (preg_match_all('/<th[^>]*>(.*?)<\/th>/is', $rowHtml, $headers)) {
                            $headerTexts = array_map(function($header) {
                                return trim(strip_tags($header));
                            }, $headers[1]);

                            Log::debug("Found header row in table", [
                                'table_index' => $tableIndex,
                                'headers' => $headerTexts
                            ]);

                            // Look for the expected column headers
                            $localScheduleIdx = null;
                            $timeIdx = null;
                            $opponentIdx = null;
                            $matchSiteIdx = null;
                            $resultIdx = null;

                            foreach ($headerTexts as $idx => $headerText) {
                                $headerLower = strtolower($headerText);
                                if (stripos($headerLower, 'local') !== false && stripos($headerLower, 'schedule') !== false) {
                                    $localScheduleIdx = $idx;
                                } elseif (stripos($headerLower, 'time') !== false && stripos($headerLower, 'local') === false) {
                                    $timeIdx = $idx;
                                } elseif (stripos($headerLower, 'opponent') !== false) {
                                    $opponentIdx = $idx;
                                } elseif (stripos($headerLower, 'match') !== false && stripos($headerLower, 'site') !== false) {
                                    $matchSiteIdx = $idx;
                                } elseif (stripos($headerLower, 'result') !== false) {
                                    $resultIdx = $idx;
                                }
                            }

                            // If we found the key columns, this is the schedule table
                            if ($localScheduleIdx !== null && $opponentIdx !== null) {
                                $columnMap = [
                                    'local_schedule' => $localScheduleIdx,
                                    'time' => $timeIdx,
                                    'opponent' => $opponentIdx,
                                    'match_site' => $matchSiteIdx,
                                    'result' => $resultIdx
                                ];

                                $scheduleTableHtml = $tableHtml;

                                Log::info("Found schedule table with column map", [
                                    'table_index' => $tableIndex,
                                    'column_map' => $columnMap,
                                    'headers' => $headerTexts
                                ]);
                                break 2; // Break out of both loops
                            }
                        }
                    }
                }
            }
        }

        if (!$columnMap || !$scheduleTableHtml) {
            Log::warning("Could not find schedule table headers - no matching columns found");
            return $matches;
        }

        // Now parse data rows from ONLY the schedule table
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $scheduleTableHtml, $tableRows)) {
            Log::info("Parsing rows from schedule table", ['total_rows' => count($tableRows[1])]);

            foreach ($tableRows[1] as $rowHtml) {
                // Skip header rows
                if (stripos($rowHtml, '<th') !== false) {
                    continue;
                }

                // Extract all cells from the row
                if (preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $rowHtml, $cells)) {
                    // Store raw HTML cells for link extraction
                    $cellsRaw = $cells[1];

                    $cellContents = array_map(function($cell) {
                        return trim(strip_tags($cell));
                    }, $cells[1]);

                    // Skip if we don't have enough cells
                    if (count($cellContents) < max(array_filter($columnMap))) {
                        continue;
                    }

                    // Extract data based on column map
                    $dateStr = $cellContents[$columnMap['local_schedule']] ?? null;
                    $timeStr = ($columnMap['time'] !== null && isset($cellContents[$columnMap['time']]))
                        ? $cellContents[$columnMap['time']]
                        : null;
                    $opponentStr = $cellContents[$columnMap['opponent']] ?? null;
                    $locationStr = ($columnMap['match_site'] !== null && isset($cellContents[$columnMap['match_site']]))
                        ? $cellContents[$columnMap['match_site']]
                        : null;
                    $resultStr = ($columnMap['result'] !== null && isset($cellContents[$columnMap['result']]))
                        ? $cellContents[$columnMap['result']]
                        : null;

                    // Log all cell contents for debugging
                    Log::debug("Raw cell contents", [
                        'all_cells' => $cellContents,
                        'date_str' => $dateStr,
                        'time_str' => $timeStr,
                        'opponent_str' => $opponentStr,
                        'location_str' => $locationStr,
                        'result_str' => $resultStr
                    ]);

                    // Skip if missing essential data
                    if (empty($dateStr) || empty($opponentStr)) {
                        Log::debug("Skipping row - missing essential data", [
                            'has_date' => !empty($dateStr),
                            'has_opponent' => !empty($opponentStr)
                        ]);
                        continue;
                    }

                    try {
                        // Parse date and time - handle mm/dd/yyyy format and 12:00pm format
                        $startTime = null;
                        if ($timeStr && !empty($timeStr)) {
                            // Clean up time string (remove spaces, ensure proper format)
                            $timeStr = trim($timeStr);
                            $timeStr = preg_replace('/\s+/', '', $timeStr); // Remove spaces
                            // Try to parse date + time
                            try {
                                $startTime = Carbon::parse($dateStr . ' ' . $timeStr);
                            } catch (\Exception $e) {
                                Log::warning("Failed to parse date+time, trying date only", [
                                    'date' => $dateStr,
                                    'time' => $timeStr,
                                    'error' => $e->getMessage()
                                ]);
                                $startTime = Carbon::parse($dateStr);
                            }
                        } else {
                            $startTime = Carbon::parse($dateStr);
                        }

                        Log::debug("Parsed date/time", [
                            'input_date' => $dateStr,
                            'input_time' => $timeStr,
                            'parsed_datetime' => $startTime->format('Y-m-d H:i:s')
                        ]);

                        // Find the opponent team - match against team names
                        $opponentTeam = null;
                        $maxMatchLength = 0;
                        $opponentStrLower = strtolower(trim($opponentStr));

                        Log::debug("Attempting to match opponent", [
                            'opponent_string' => $opponentStr,
                            'opponent_lower' => $opponentStrLower,
                            'available_teams' => array_keys($teamsByName)
                        ]);

                        foreach ($teamsByName as $teamName => $team) {
                            // Try substring match (case-insensitive)
                            if (stripos($opponentStrLower, $teamName) !== false) {
                                // Prefer longer matches (more specific team names)
                                if (strlen($teamName) > $maxMatchLength) {
                                    $opponentTeam = $team;
                                    $maxMatchLength = strlen($teamName);
                                    Log::debug("Found potential team match", [
                                        'team_name' => $teamName,
                                        'team_id' => $team->id,
                                        'match_length' => $maxMatchLength
                                    ]);
                                }
                            }
                        }

                        if (!$opponentTeam) {
                            Log::warning("Could not match opponent team", [
                                'opponent_string' => $opponentStr,
                                'opponent_lower' => $opponentStrLower,
                                'available_teams' => array_keys($teamsByName),
                                'team_count' => count($teamsByName)
                            ]);
                            continue;
                        }

                        Log::debug("Matched opponent team", [
                            'opponent_string' => $opponentStr,
                            'matched_team' => $opponentTeam->name,
                            'team_id' => $opponentTeam->id
                        ]);

                        // Parse result for scores if available
                        // Note: These scores are from the current team's perspective
                        // First number is current team's score, second is opponent's score
                        $currentTeamScore = null;
                        $opponentScore = null;
                        if ($resultStr && preg_match('/(\d+)\s*-\s*(\d+)/', $resultStr, $scoreMatches)) {
                            $currentTeamScore = (int)$scoreMatches[1];
                            $opponentScore = (int)$scoreMatches[2];
                        }

                        // Extract match link and external_id from Result column
                        $externalId = null;
                        $tennisRecordMatchLink = null;
                        if ($columnMap['result'] !== null && isset($cellsRaw[$columnMap['result']])) {
                            $resultHtml = $cellsRaw[$columnMap['result']];
                            // Look for links to matchresults.aspx with mid parameter
                            if (preg_match('/<a[^>]*href=["\']([^"\']*matchresults\.aspx[^"\']*)["\'][^>]*>/i', $resultHtml, $linkMatch)) {
                                $relativeUrl = $linkMatch[1];
                                // Convert to absolute URL if needed
                                if (strpos($relativeUrl, 'http') !== 0) {
                                    // Remove leading slash and "adult/" if present to avoid duplication
                                    $relativeUrl = ltrim($relativeUrl, '/');
                                    $relativeUrl = preg_replace('/^adult\//', '', $relativeUrl);
                                    $tennisRecordMatchLink = 'https://www.tennisrecord.com/adult/' . $relativeUrl;
                                } else {
                                    $tennisRecordMatchLink = $relativeUrl;
                                }

                                // Extract mid parameter
                                if (preg_match('/[?&]mid=(\d+)/i', $tennisRecordMatchLink, $midMatch)) {
                                    $externalId = (int)$midMatch[1];
                                }

                                Log::debug("Extracted match link", [
                                    'external_id' => $externalId,
                                    'link' => $tennisRecordMatchLink
                                ]);
                            }
                        }

                        // Get the current team (the one we're syncing for)
                        $currentTeam = $teamId ? $teams->firstWhere('id', $teamId) : null;

                        if (!$currentTeam && $teamId) {
                            continue;
                        }

                        // Store preliminary match data - we'll determine home/away after analyzing all locations
                        $preliminaryMatches[] = [
                            'current_team' => $currentTeam,
                            'opponent_team' => $opponentTeam,
                            'start_time' => $startTime,
                            'location' => $locationStr ?? null,
                            'current_team_score' => $currentTeamScore,
                            'opponent_score' => $opponentScore,
                            'external_id' => $externalId,
                            'tennis_record_match_link' => $tennisRecordMatchLink
                        ];

                        Log::debug("Stored preliminary match", [
                            'current_team' => $currentTeam ? $currentTeam->name : 'N/A',
                            'opponent_team' => $opponentTeam->name,
                            'date' => $startTime->format('Y-m-d H:i:s'),
                            'location' => $locationStr,
                            'score' => $resultStr
                        ]);

                    } catch (\Exception $e) {
                        Log::warning("Error parsing match row", [
                            'date_str' => $dateStr,
                            'time_str' => $timeStr,
                            'opponent_str' => $opponentStr,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Now analyze locations to determine home court
        $locationCounts = [];
        foreach ($preliminaryMatches as $match) {
            $location = $match['location'];
            if ($location && !empty(trim($location))) {
                $locationKey = strtolower(trim($location));
                if (!isset($locationCounts[$locationKey])) {
                    $locationCounts[$locationKey] = [
                        'count' => 0,
                        'original' => $location
                    ];
                }
                $locationCounts[$locationKey]['count']++;
            }
        }

        // Find the most common location (home court)
        $homeLocation = null;
        $maxCount = 0;
        foreach ($locationCounts as $locationKey => $data) {
            if ($data['count'] > $maxCount) {
                $maxCount = $data['count'];
                $homeLocation = $locationKey;
            }
        }

        Log::info("Location analysis", [
            'location_counts' => $locationCounts,
            'home_location' => $homeLocation,
            'home_location_count' => $maxCount
        ]);

        // Now convert preliminary matches to final matches with proper home/away assignment
        foreach ($preliminaryMatches as $match) {
            $currentTeam = $match['current_team'];
            $opponentTeam = $match['opponent_team'];
            $location = $match['location'];

            // Determine if current team is home or away based on location
            $isHomeMatch = false;
            if ($homeLocation && $location) {
                $locationKey = strtolower(trim($location));
                $isHomeMatch = ($locationKey === $homeLocation);
            }

            // Assign home/away teams
            $homeTeam = $isHomeMatch ? $currentTeam : $opponentTeam;
            $awayTeam = $isHomeMatch ? $opponentTeam : $currentTeam;

            // If we couldn't determine home location, default to current team as home
            if (!$homeLocation && $currentTeam) {
                $homeTeam = $currentTeam;
                $awayTeam = $opponentTeam;
                $isHomeMatch = true; // Set this for score assignment
            }

            // Skip if we don't have both teams
            if (!$homeTeam || !$awayTeam) {
                continue;
            }

            // Assign scores correctly based on who is home/away
            // If current team is home: home_score = current_team_score, away_score = opponent_score
            // If current team is away: home_score = opponent_score, away_score = current_team_score
            $homeScore = $isHomeMatch ? $match['current_team_score'] : $match['opponent_score'];
            $awayScore = $isHomeMatch ? $match['opponent_score'] : $match['current_team_score'];

            $matches[] = [
                'home_team_id' => $homeTeam->id,
                'home_team_name' => $homeTeam->name,
                'away_team_id' => $awayTeam->id,
                'away_team_name' => $awayTeam->name,
                'start_time' => $match['start_time'],
                'location' => $location,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'external_id' => $match['external_id'],
                'tennis_record_match_link' => $match['tennis_record_match_link']
            ];

            Log::info("Successfully parsed match with home/away determined by location", [
                'home_team' => $homeTeam->name,
                'away_team' => $awayTeam->name,
                'location' => $location,
                'is_home_location' => $isHomeMatch,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'date' => $match['start_time']->format('Y-m-d H:i:s')
            ]);
        }

        Log::info("Finished parsing schedule table", [
            'total_matches_found' => count($matches)
        ]);

        return $matches;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("=== TEAM MATCHES SYNC JOB MARKED AS FAILED ===", [
            'job_key' => $this->jobKey,
            'league_id' => $this->league->id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->updateProgress($this->jobKey, 'Job failed: ' . $exception->getMessage(), 0, 100, [], 'failed');
    }

    /**
     * Update progress in cache
     */
    private function updateProgress($jobKey, $message, $step, $totalSteps, $data = [], $status = 'processing')
    {
        Cache::put("team_matches_sync_progress_{$jobKey}", [
            'status' => $status,
            'message' => $message,
            'step' => $step,
            'total_steps' => $totalSteps,
            'percentage' => $totalSteps > 0 ? ($step / $totalSteps) * 100 : 0,
            'data' => $data
        ], 600); // Cache for 10 minutes
    }

    /**
     * Get the job key for progress tracking
     */
    public function getJobKey()
    {
        return $this->jobKey;
    }
}
