<?php

namespace App\Jobs;

use App\Models\Court;
use App\Models\CourtPlayer;
use App\Models\Player;
use App\Models\TennisMatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class SyncMatchFromTennisRecordJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected TennisMatch $match;
    protected bool $hasChanges = false;
    protected array $oldCourtPlayerRatings = [];

    /**
     * Create a new job instance.
     */
    public function __construct(TennisMatch $match)
    {
        $this->match = $match;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->match->tennis_record_match_link) {
            Log::warning("Match {$this->match->id} has no Tennis Record link");
            return;
        }

        try {
            // Fetch the page
            $response = Http::timeout(30)->get($this->match->tennis_record_match_link);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch Tennis Record page: {$response->status()}");
            }

            $html = $response->body();
            Log::info("Fetched HTML page", ['html_length' => strlen($html)]);

            $crawler = new Crawler($html);
            Log::info("Created crawler instance");

            // Load match relationships
            $this->match->load(['homeTeam.players', 'awayTeam.players']);

            Log::info("Starting to parse match {$this->match->id}", [
                'home_team' => $this->match->homeTeam->name,
                'away_team' => $this->match->awayTeam->name,
                'home_players_count' => $this->match->homeTeam->players->count(),
                'away_players_count' => $this->match->awayTeam->players->count(),
            ]);

            // Store original match score to check if anything changed
            $originalHomeScore = $this->match->home_score;
            $originalAwayScore = $this->match->away_score;
            $originalCourtsCount = $this->match->courts()->count();

            // Store existing court player ratings before deletion (in case scores haven't changed)
            $existingCourts = $this->match->courts()->with('courtPlayers')->get();
            foreach ($existingCourts as $court) {
                foreach ($court->courtPlayers as $courtPlayer) {
                    $key = "{$court->court_type}_{$court->court_number}_{$courtPlayer->player_id}_{$courtPlayer->team_id}";
                    $this->oldCourtPlayerRatings[$key] = [
                        'utr_singles_rating' => $courtPlayer->utr_singles_rating,
                        'utr_doubles_rating' => $courtPlayer->utr_doubles_rating,
                        'usta_dynamic_rating' => $courtPlayer->usta_dynamic_rating,
                    ];
                }
            }

            // Delete existing courts for this match (if re-syncing)
            $deletedCount = $this->match->courts()->count();
            $this->match->courts()->delete();
            Log::info("Deleted {$deletedCount} existing courts for match {$this->match->id}");

            // Parse singles courts
            $this->parseSinglesCourts($crawler);

            // Parse doubles courts
            $this->parseDoublesCourts($crawler);

            // Calculate match score based on courts won
            $this->updateMatchScore();

            // Check if anything actually changed
            $newCourtsCount = $this->match->courts()->count();
            $this->hasChanges = ($originalHomeScore !== $this->match->home_score ||
                                 $originalAwayScore !== $this->match->away_score ||
                                 $originalCourtsCount !== $newCourtsCount);

            $courtsCreated = $this->match->courts()->count();
            Log::info("Successfully synced match {$this->match->id} from Tennis Record", [
                'courts_created' => $courtsCreated,
                'home_score' => $this->match->home_score,
                'away_score' => $this->match->away_score,
                'had_changes' => $this->hasChanges,
                'preserved_ratings' => !$this->hasChanges
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to sync match {$this->match->id} from Tennis Record: {$e->getMessage()}", [
                'match_id' => $this->match->id,
                'link' => $this->match->tennis_record_match_link,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function parseSinglesCourts(Crawler $crawler): void
    {
        Log::info("parseSinglesCourts method called");

        // First, let's see what divs we can find
        $allDivs = $crawler->filter('div')->count();
        Log::info("Total divs found: {$allDivs}");

        $wrapper496Count = $crawler->filter('div.wrapper496')->count();
        Log::info("wrapper496 divs found: {$wrapper496Count}");

        // Also try alternative selector
        $altWrappers = $crawler->filter('[class*="wrapper"]')->count();
        Log::info("Divs with 'wrapper' in class: {$altWrappers}");

        // Look for divs with class "wrapper496" containing "Singles #X"
        $wrappersFound = 0;
        $crawler->filter('div.wrapper496')->each(function (Crawler $wrapper) use (&$wrappersFound) {
            $wrappersFound++;
            $text = trim($wrapper->text());
            Log::info("Found wrapper496", ['text' => substr($text, 0, 100)]);

            // Check if this wrapper contains "Singles #X"
            if (preg_match('/Singles\s*#(\d+)/i', $text, $matches)) {
                $courtNumber = (int) $matches[1];
                Log::info("Found Singles #{$courtNumber} wrapper");

                // Find the next sibling div with class "container496"
                $container = $wrapper->nextAll()->filter('div.container496')->first();

                if ($container->count() > 0) {
                    Log::info("Found container496 for Singles #{$courtNumber}");

                    // Get the table inside this container
                    $table = $container->filter('table')->first();

                    if ($table->count() > 0) {
                        Log::info("Found table for Singles #{$courtNumber}");
                        $this->parseSinglesCourtTable($table, $courtNumber);
                    } else {
                        Log::warning("No table found in container for Singles #{$courtNumber}");
                    }
                } else {
                    Log::warning("No container496 found after Singles #{$courtNumber} wrapper");
                }
            }
        });

        Log::info("Finished parsing singles courts", ['wrappers_found' => $wrappersFound]);
    }

    protected function parseSinglesCourtTable(Crawler $table, int $courtNumber): void
    {
        try {
            Log::info("Parsing Singles #{$courtNumber} table");

            // Find rows in the table
            $rows = $table->filter('tr');
            Log::info("Found {$rows->count()} rows in Singles #{$courtNumber} table");

            if ($rows->count() < 2) {
                Log::warning("Not enough rows in Singles #{$courtNumber} table");
                return;
            }

            // Find the data row (not the header row)
            $dataRow = null;
            $rows->each(function (Crawler $row, $index) use (&$dataRow) {
                $cells = $row->filter('td');
                Log::info("Row {$index} has {$cells->count()} cells");
                if ($cells->count() >= 3 && $dataRow === null) {
                    // Check if this row has player links (contains 'a' tags) OR contains dashes (default)
                    $linkCount = $cells->eq(0)->filter('a')->count();
                    $firstCellText = trim($cells->eq(0)->text());
                    // Remove whitespace to check for dashes (handles "---\n---" format)
                    $withoutWhitespace = preg_replace('/\s+/', '', $firstCellText);
                    $hasDashes = !empty($withoutWhitespace) && preg_match('/^-+$/', $withoutWhitespace);
                    Log::info("Row {$index} first cell has {$linkCount} links", ['text' => substr($firstCellText, 0, 50), 'has_dashes' => $hasDashes]);
                    if ($linkCount > 0 || $hasDashes) {
                        $dataRow = $row;
                    }
                }
            });

            if (!$dataRow) {
                Log::warning("Could not find data row for Singles #{$courtNumber}");
                return;
            }

            $cells = $dataRow->filter('td');
            Log::info("Data row has {$cells->count()} cells");

            // Extract home team player (first cell)
            $homeCell = $cells->eq(0);
            $homePlayerName = $this->extractPlayerName($homeCell->text());
            $homeDefaulted = $this->isDefaulted($homePlayerName);
            Log::info("Singles #{$courtNumber} - Home player: {$homePlayerName}", ['defaulted' => $homeDefaulted]);

            // Extract away team player (last cell)
            $awayCell = $cells->eq($cells->count() - 1);
            $awayPlayerName = $this->extractPlayerName($awayCell->text());
            $awayDefaulted = $this->isDefaulted($awayPlayerName);
            Log::info("Singles #{$courtNumber} - Away player: {$awayPlayerName}", ['defaulted' => $awayDefaulted]);

            // Find the score cell - look for the cell containing the score text
            $scoreText = '';
            $homeWon = false;

            $cells->each(function (Crawler $cell, $cellIndex) use (&$scoreText, &$homeWon) {
                // Get HTML content and convert <br> tags to newlines
                $html = $cell->html();
                $textWithBreaks = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
                $text = strip_tags($textWithBreaks);
                $text = trim($text);

                // Score cells contain "6 - 3" format
                if (preg_match('/\d+\s*-\s*\d+/', $text)) {
                    $scoreText = $text;
                    Log::info("Found score in cell {$cellIndex}: {$scoreText}");
                }
                // Check for winner arrow
                if ($cell->filter('img')->count() > 0) {
                    $imgSrc = $cell->filter('img')->attr('src');
                    Log::info("Found image in cell {$cellIndex}: {$imgSrc}");
                    if (str_contains($imgSrc, 'arrowhead_right')) {
                        $homeWon = true; // Arrow right means home team won
                        Log::info("Home team won (arrowhead_right)");
                    } elseif (str_contains($imgSrc, 'arrowhead_left')) {
                        $homeWon = false; // Arrow left means away team won
                        Log::info("Away team won (arrowhead_left)");
                    }
                }
            });

            list($homeScore, $awayScore, $setScores) = $this->parseScore($scoreText, $homeWon);
            Log::info("Singles #{$courtNumber} - Parsed score: Home {$homeScore} - Away {$awayScore}, Sets: " . count($setScores));

            // If sets are tied, determine winner by total games
            if ($homeScore === $awayScore && count($setScores) > 0) {
                $totalHomeGames = array_sum(array_column($setScores, 'home_score'));
                $totalAwayGames = array_sum(array_column($setScores, 'away_score'));
                Log::info("Singles #{$courtNumber} - Sets tied, checking total games", [
                    'home_games' => $totalHomeGames,
                    'away_games' => $totalAwayGames
                ]);

                if ($totalHomeGames > $totalAwayGames) {
                    $homeScore = 2; // Home wins the court
                    $awayScore = 0;
                    $homeWon = true;
                    Log::info("Singles #{$courtNumber} - Home wins by total games");
                } elseif ($totalAwayGames > $totalHomeGames) {
                    $homeScore = 0;
                    $awayScore = 2; // Away wins the court
                    $homeWon = false;
                    Log::info("Singles #{$courtNumber} - Away wins by total games");
                }
            }

            // Find players in database (skip if defaulted)
            $homePlayer = null;
            $awayPlayer = null;

            if (!$homeDefaulted) {
                $homePlayer = $this->findPlayer($homePlayerName, $this->match->homeTeam);
            }

            if (!$awayDefaulted) {
                $awayPlayer = $this->findPlayer($awayPlayerName, $this->match->awayTeam);
            }

            // If neither team defaulted but we can't find players, warn and skip
            if (!$homeDefaulted && !$awayDefaulted && (!$homePlayer || !$awayPlayer)) {
                Log::warning("Could not find players for Singles #{$courtNumber}", [
                    'home_player' => $homePlayerName,
                    'home_found' => $homePlayer ? 'yes' : 'no',
                    'away_player' => $awayPlayerName,
                    'away_found' => $awayPlayer ? 'yes' : 'no',
                    'match_id' => $this->match->id
                ]);
                return;
            }

            // If one side defaulted, determine winner
            if ($homeDefaulted) {
                $homeWon = false;
                $awayWon = true;
                // Set default score if no score was parsed
                if ($homeScore === 0 && $awayScore === 0) {
                    $awayScore = 1; // Away wins by default
                }
                Log::info("Singles #{$courtNumber} - Home team defaulted, away team wins");
            } elseif ($awayDefaulted) {
                $homeWon = true;
                $awayWon = false;
                // Set default score if no score was parsed
                if ($homeScore === 0 && $awayScore === 0) {
                    $homeScore = 1; // Home wins by default
                }
                Log::info("Singles #{$courtNumber} - Away team defaulted, home team wins");
            }

            Log::info("Singles #{$courtNumber} - Player status", [
                'home_player_id' => $homePlayer ? $homePlayer->id : 'defaulted',
                'away_player_id' => $awayPlayer ? $awayPlayer->id : 'defaulted'
            ]);

            // Create court record
            $court = Court::create([
                'tennis_match_id' => $this->match->id,
                'court_type' => 'singles',
                'court_number' => $courtNumber,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
            ]);

            Log::info("Singles #{$courtNumber} - Created court record", ['court_id' => $court->id]);

            // Create court set records for each set
            foreach ($setScores as $setData) {
                $court->courtSets()->create($setData);
                Log::info("Singles #{$courtNumber} - Created set #{$setData['set_number']}: {$setData['home_score']}-{$setData['away_score']}");
            }

            // Create court player records with snapshots
            // If no arrow was found and no default, determine winner by score
            if (!$homeDefaulted && !$awayDefaulted && $homeScore === 0 && $awayScore === 0) {
                // Try to determine from score text if possible
                $homeWon = $homeScore > $awayScore;
            }

            // Only create court player records for players who didn't default
            if ($homePlayer) {
                $this->createCourtPlayer($court, $homePlayer, $this->match->home_team_id, $homeWon);
            }
            if ($awayPlayer) {
                $this->createCourtPlayer($court, $awayPlayer, $this->match->away_team_id, !$homeWon);
            }

            Log::info("Singles #{$courtNumber} - Created court player records");

        } catch (\Exception $e) {
            Log::error("Failed to parse singles court #{$courtNumber}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function parseDoublesCourts(Crawler $crawler): void
    {
        Log::info("parseDoublesCourts method called");

        $allDivs = $crawler->filter('div')->count();
        Log::info("Total divs found for doubles: {$allDivs}");

        $wrapper496Count = $crawler->filter('div.wrapper496')->count();
        Log::info("wrapper496 divs found for doubles: {$wrapper496Count}");

        // Look for divs with class "wrapper496" containing "Doubles #X"
        $wrappersFound = 0;
        $crawler->filter('div.wrapper496')->each(function (Crawler $wrapper) use (&$wrappersFound) {
            $wrappersFound++;
            $text = trim($wrapper->text());
            Log::info("Checking wrapper for Doubles", ['text' => substr($text, 0, 100)]);

            // Check if this wrapper contains "Doubles #X"
            if (preg_match('/Doubles\s*#(\d+)/i', $text, $matches)) {
                $courtNumber = (int) $matches[1];
                Log::info("Found Doubles #{$courtNumber} wrapper");

                // Find the next sibling div with class "container496"
                $container = $wrapper->nextAll()->filter('div.container496')->first();

                if ($container->count() > 0) {
                    Log::info("Found container496 for Doubles #{$courtNumber}");

                    // Get the table inside this container
                    $table = $container->filter('table')->first();

                    if ($table->count() > 0) {
                        Log::info("Found table for Doubles #{$courtNumber}");
                        $this->parseDoublesCourtTable($table, $courtNumber);
                    } else {
                        Log::warning("No table found in container for Doubles #{$courtNumber}");
                    }
                } else {
                    Log::warning("No container496 found after Doubles #{$courtNumber} wrapper");
                }
            }
        });

        Log::info("Finished parsing doubles courts", ['wrappers_checked' => $wrappersFound]);
    }

    protected function parseDoublesCourtTable(Crawler $table, int $courtNumber): void
    {
        try {
            Log::info("Parsing Doubles #{$courtNumber} table");

            // Find rows in the table
            $rows = $table->filter('tr');
            Log::info("Found {$rows->count()} rows in Doubles #{$courtNumber} table");

            if ($rows->count() < 2) {
                Log::warning("Not enough rows in Doubles #{$courtNumber} table");
                return;
            }

            // Find the data row (not the header row)
            $dataRow = null;
            $rows->each(function (Crawler $row, $index) use (&$dataRow) {
                $cells = $row->filter('td');
                Log::info("Doubles row {$index} has {$cells->count()} cells");
                if ($cells->count() >= 3 && $dataRow === null) {
                    // Check if this row has player links (contains 'a' tags) OR contains dashes (default)
                    $linkCount = $cells->eq(0)->filter('a')->count();
                    $firstCellText = trim($cells->eq(0)->text());
                    // Remove whitespace to check for dashes (handles "---\n---" format)
                    $withoutWhitespace = preg_replace('/\s+/', '', $firstCellText);
                    $hasDashes = !empty($withoutWhitespace) && preg_match('/^-+$/', $withoutWhitespace);
                    Log::info("Doubles row {$index} first cell has {$linkCount} links", ['text' => substr($firstCellText, 0, 50), 'has_dashes' => $hasDashes]);
                    if ($linkCount > 0 || $hasDashes) {
                        $dataRow = $row;
                    }
                }
            });

            if (!$dataRow) {
                Log::warning("Could not find data row for Doubles #{$courtNumber}");
                return;
            }

            $cells = $dataRow->filter('td');
            Log::info("Doubles data row has {$cells->count()} cells");

            // Extract home team players (first cell, separated by <br>)
            $homeCell = $cells->eq(0);
            Log::info("Doubles #{$courtNumber} - Extracting home players");
            // Check if cell contains only dashes (default) before extracting names
            $homeCellText = trim($homeCell->text());
            $homeDefaulted = $this->isDefaulted($homeCellText);
            $homePlayerNames = $homeDefaulted ? [] : $this->extractDoublesPlayerNames($homeCell);
            Log::info("Doubles #{$courtNumber} - Home players: " . implode(', ', $homePlayerNames), ['defaulted' => $homeDefaulted]);

            // Extract away team players (last cell, separated by <br>)
            $awayCell = $cells->eq($cells->count() - 1);
            Log::info("Doubles #{$courtNumber} - Extracting away players");
            // Check if cell contains only dashes (default) before extracting names
            $awayCellText = trim($awayCell->text());
            $awayDefaulted = $this->isDefaulted($awayCellText);
            $awayPlayerNames = $awayDefaulted ? [] : $this->extractDoublesPlayerNames($awayCell);
            Log::info("Doubles #{$courtNumber} - Away players: " . implode(', ', $awayPlayerNames), ['defaulted' => $awayDefaulted]);

            // Find the score cell and winner arrow
            $scoreText = '';
            $homeWon = false;

            $cells->each(function (Crawler $cell, $cellIndex) use (&$scoreText, &$homeWon) {
                // Get HTML content and convert <br> tags to newlines
                $html = $cell->html();
                $textWithBreaks = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
                $text = strip_tags($textWithBreaks);
                $text = trim($text);

                // Score cells contain "6 - 3" format
                if (preg_match('/\d+\s*-\s*\d+/', $text)) {
                    $scoreText = $text;
                    Log::info("Found score in cell {$cellIndex}: {$scoreText}");
                }
                // Check for winner arrow
                if ($cell->filter('img')->count() > 0) {
                    $imgSrc = $cell->filter('img')->attr('src');
                    Log::info("Found image in cell {$cellIndex}: {$imgSrc}");
                    if (str_contains($imgSrc, 'arrowhead_right')) {
                        $homeWon = true; // Arrow right means home team won
                        Log::info("Home team won (arrowhead_right)");
                    } elseif (str_contains($imgSrc, 'arrowhead_left')) {
                        $homeWon = false; // Arrow left means away team won
                        Log::info("Away team won (arrowhead_left)");
                    }
                }
            });

            list($homeScore, $awayScore, $setScores) = $this->parseScore($scoreText, $homeWon);
            Log::info("Doubles #{$courtNumber} - Parsed score: Home {$homeScore} - Away {$awayScore}, Sets: " . count($setScores));

            // If sets are tied, determine winner by total games
            if ($homeScore === $awayScore && count($setScores) > 0) {
                $totalHomeGames = array_sum(array_column($setScores, 'home_score'));
                $totalAwayGames = array_sum(array_column($setScores, 'away_score'));
                Log::info("Doubles #{$courtNumber} - Sets tied, checking total games", [
                    'home_games' => $totalHomeGames,
                    'away_games' => $totalAwayGames
                ]);

                if ($totalHomeGames > $totalAwayGames) {
                    $homeScore = 2; // Home wins the court
                    $awayScore = 0;
                    $homeWon = true;
                    Log::info("Doubles #{$courtNumber} - Home wins by total games");
                } elseif ($totalAwayGames > $totalHomeGames) {
                    $homeScore = 0;
                    $awayScore = 2; // Away wins the court
                    $homeWon = false;
                    Log::info("Doubles #{$courtNumber} - Away wins by total games");
                }
            }

            // Find players (skip if defaulted)
            $homePlayers = [];
            $awayPlayers = [];

            if (!$homeDefaulted) {
                $homePlayers = array_map(fn($name) => $this->findPlayer($name, $this->match->homeTeam), $homePlayerNames);
                $homePlayers = array_filter($homePlayers); // Filter out nulls
            }

            if (!$awayDefaulted) {
                $awayPlayers = array_map(fn($name) => $this->findPlayer($name, $this->match->awayTeam), $awayPlayerNames);
                $awayPlayers = array_filter($awayPlayers); // Filter out nulls
            }

            Log::info("Doubles #{$courtNumber} - Found players", [
                'home_count' => count($homePlayers),
                'away_count' => count($awayPlayers),
                'home_defaulted' => $homeDefaulted,
                'away_defaulted' => $awayDefaulted
            ]);

            // If neither team defaulted but we can't find all players, warn and skip
            if (!$homeDefaulted && !$awayDefaulted && (count($homePlayers) !== 2 || count($awayPlayers) !== 2)) {
                Log::warning("Could not find all players for Doubles #{$courtNumber}", [
                    'home_players' => $homePlayerNames,
                    'away_players' => $awayPlayerNames,
                    'found_home' => count($homePlayers),
                    'found_away' => count($awayPlayers),
                    'match_id' => $this->match->id
                ]);
                return;
            }

            // If one side defaulted, determine winner
            if ($homeDefaulted) {
                $homeWon = false;
                $awayWon = true;
                // Set default score if no score was parsed
                if ($homeScore === 0 && $awayScore === 0) {
                    $awayScore = 1; // Away wins by default
                }
                Log::info("Doubles #{$courtNumber} - Home team defaulted, away team wins");
            } elseif ($awayDefaulted) {
                $homeWon = true;
                $awayWon = false;
                // Set default score if no score was parsed
                if ($homeScore === 0 && $awayScore === 0) {
                    $homeScore = 1; // Home wins by default
                }
                Log::info("Doubles #{$courtNumber} - Away team defaulted, home team wins");
            }

            // Create court record
            $court = Court::create([
                'tennis_match_id' => $this->match->id,
                'court_type' => 'doubles',
                'court_number' => $courtNumber,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
            ]);

            Log::info("Doubles #{$courtNumber} - Created court record", ['court_id' => $court->id]);

            // Create court set records for each set
            foreach ($setScores as $setData) {
                $court->courtSets()->create($setData);
                Log::info("Doubles #{$courtNumber} - Created set #{$setData['set_number']}: {$setData['home_score']}-{$setData['away_score']}");
            }

            // Create court player records for players who didn't default
            // If no arrow was found and no default, determine winner by score
            if (!$homeDefaulted && !$awayDefaulted && $homeScore === 0 && $awayScore === 0) {
                $homeWon = $homeScore > $awayScore;
            }

            // Only create court player records for players who didn't default
            foreach ($homePlayers as $player) {
                $this->createCourtPlayer($court, $player, $this->match->home_team_id, $homeWon);
            }
            foreach ($awayPlayers as $player) {
                $this->createCourtPlayer($court, $player, $this->match->away_team_id, !$homeWon);
            }

            Log::info("Doubles #{$courtNumber} - Created court player records");

        } catch (\Exception $e) {
            Log::error("Failed to parse doubles court #{$courtNumber}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if a player name indicates a default (dashes)
     * Example: "-----------------" or "---" or "-----\n-----" -> true
     */
    protected function isDefaulted(string $text): bool
    {
        $trimmed = trim($text);
        // Check if the string is only dashes and whitespace (including newlines/br tags)
        // Remove all whitespace and check if what remains is only dashes
        $withoutWhitespace = preg_replace('/\s+/', '', $trimmed);
        return empty($withoutWhitespace) || preg_match('/^-+$/', $withoutWhitespace);
    }

    /**
     * Extract player name from text that may include USTA rating in parentheses
     * Example: "Smith, John (4.5)" -> "Smith, John"
     */
    protected function extractPlayerName(string $text): string
    {
        // Remove USTA rating in parentheses
        $name = preg_replace('/\s*\([0-9.]+\)\s*$/', '', $text);
        return trim($name);
    }

    /**
     * Extract player names from doubles cell (separated by <br> tags)
     * Returns array of player names
     */
    protected function extractDoublesPlayerNames(Crawler $cell): array
    {
        $names = [];

        // Get all <a> tags in the cell (each player has a link)
        $links = $cell->filter('a');

        Log::info("Extracting doubles players from cell", [
            'link_count' => $links->count(),
            'full_text' => substr($cell->text(), 0, 200)
        ]);

        // Log each link text individually
        $linkIndex = 0;
        $links->each(function (Crawler $link) use (&$names, &$linkIndex) {
            $playerText = trim($link->text());
            Log::info("Processing link {$linkIndex}", ['raw_text' => $playerText]);

            $playerName = $this->extractPlayerName($playerText);
            Log::info("After extractPlayerName", ['cleaned_name' => $playerName]);

            if (!empty($playerName)) {
                $names[] = $playerName;
                Log::info("Added player to names array", ['name' => $playerName, 'total_names' => count($names)]);
            }
            $linkIndex++;
        });

        // If we didn't get 2 names from links, try alternative parsing
        if (count($names) < 2) {
            Log::warning("Only got " . count($names) . " names from links, trying alternative parsing");

            $fullText = $cell->text();
            Log::info("Full cell text for parsing", ['text' => $fullText]);

            // Split by ratings pattern to separate players
            // This handles: "Name1 (rating) Name2 (rating)" or "Name1 (rating) Name2"
            $parts = preg_split('/\s*\([\d.]+\)\s*/', $fullText);

            Log::info("Split by ratings", ['parts' => $parts]);

            foreach ($parts as $part) {
                $trimmed = trim($part);
                // Skip empty parts and very short parts (likely just whitespace)
                if (!empty($trimmed) && strlen($trimmed) > 2) {
                    // Check if this part looks like a name (contains letters)
                    if (preg_match('/[A-Za-z]/', $trimmed)) {
                        $names[] = $trimmed;
                        Log::info("Added name from split", ['name' => $trimmed]);
                    }
                }
            }
        }

        Log::info("Final extracted doubles player names", [
            'count' => count($names),
            'names' => $names
        ]);
        return $names;
    }

    protected function findPlayer(string $name, $team): ?Player
    {
        if (!$team) {
            return null;
        }

        // Split name into parts (format could be "Last, First" or "First Last")
        $nameParts = preg_split('/[,\s]+/', $name);
        $nameParts = array_filter(array_map('trim', $nameParts));

        // Try to find player in team
        foreach ($team->players as $player) {
            $playerFullName = strtolower($player->first_name . ' ' . $player->last_name);
            $searchName = strtolower(implode(' ', $nameParts));

            if (str_contains($playerFullName, $searchName) || str_contains($searchName, strtolower($player->last_name))) {
                return $player;
            }
        }

        return null;
    }

    protected function updateMatchScore(): void
    {
        // Count courts won by each team based on home_score vs away_score
        $homeWins = 0;
        $awayWins = 0;

        foreach ($this->match->courts as $court) {
            if ($court->home_score > $court->away_score) {
                $homeWins++;
            } elseif ($court->away_score > $court->home_score) {
                $awayWins++;
            }
            // Ties don't count for either team
        }

        // Update the match score
        $this->match->update([
            'home_score' => $homeWins,
            'away_score' => $awayWins,
        ]);

        Log::info("Updated match {$this->match->id} score", [
            'home_wins' => $homeWins,
            'away_wins' => $awayWins
        ]);
    }

    protected function createCourtPlayer(Court $court, Player $player, int $teamId, bool $won): void
    {
        // Check if we have old ratings to preserve (when scores haven't changed)
        $key = "{$court->court_type}_{$court->court_number}_{$player->id}_{$teamId}";
        $useOldRatings = !$this->hasChanges && isset($this->oldCourtPlayerRatings[$key]);

        if ($useOldRatings) {
            $oldRatings = $this->oldCourtPlayerRatings[$key];
            CourtPlayer::create([
                'court_id' => $court->id,
                'player_id' => $player->id,
                'team_id' => $teamId,
                'won' => $won,
                'utr_singles_rating' => $oldRatings['utr_singles_rating'],
                'utr_doubles_rating' => $oldRatings['utr_doubles_rating'],
                'usta_dynamic_rating' => $oldRatings['usta_dynamic_rating'],
            ]);
        } else {
            // Use current player ratings for new/changed matches
            CourtPlayer::create([
                'court_id' => $court->id,
                'player_id' => $player->id,
                'team_id' => $teamId,
                'won' => $won,
                'utr_singles_rating' => $player->utr_singles_rating,
                'utr_doubles_rating' => $player->utr_doubles_rating,
                'usta_dynamic_rating' => $player->USTA_dynamic_rating,
            ]);
        }
    }

    /**
     * Parse score text and return both set counts and individual set scores
     * Returns array with [homeWins, awayWins, setScores[]]
     *
     * @param string $scoreText The score text from Tennis Record
     * @param bool $homeWon Whether the home team won (from arrow direction)
     */
    protected function parseScore(string $scoreText, bool $homeWon): array
    {
        // Handle various score formats
        // "6 - 3\n6 - 0" -> sets separated by newlines
        // "W" or "Won" -> winner
        // "Default" -> forfeit

        if (empty($scoreText) || in_array(strtolower($scoreText), ['default', 'retired', 'bye'])) {
            return [0, 0, []];
        }

        if (in_array(strtolower($scoreText), ['w', 'won'])) {
            return [1, 0, []];
        }

        // Split by newlines or commas to get individual sets
        $sets = preg_split('/[\n,]+/', $scoreText);
        $homeWins = 0;
        $awayWins = 0;
        $setScores = [];

        foreach ($sets as $setIndex => $set) {
            if (preg_match('/(\d+)\s*-\s*(\d+)/', trim($set), $matches)) {
                // Tennis Record shows winner's score first
                // So we need to swap based on who won
                $firstScore = (int)$matches[1];
                $secondScore = (int)$matches[2];

                if ($homeWon) {
                    // Home won, so first score is home, second is away
                    $homeScore = $firstScore;
                    $awayScore = $secondScore;
                } else {
                    // Away won, so first score is away, second is home
                    $homeScore = $secondScore;
                    $awayScore = $firstScore;
                }

                // Store individual set scores
                $setScores[] = [
                    'set_number' => $setIndex + 1,
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                ];

                // Count set wins
                if ($homeScore > $awayScore) {
                    $homeWins++;
                } else {
                    $awayWins++;
                }
            }
        }

        return [$homeWins, $awayWins, $setScores];
    }
}
