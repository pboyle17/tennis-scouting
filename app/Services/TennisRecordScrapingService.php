<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class TennisRecordScrapingService
{
    /**
     * Scrape team data from Tennis Record page
     */
    public function scrapeTeamData($tennisRecordLink)
    {
        try {
            Log::info("Starting to scrape Tennis Record link: {$tennisRecordLink}");

            // Make request to Tennis Record page
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])
            ->timeout(120) // Increase timeout to 120 seconds (2 minutes)
            ->connectTimeout(30) // Allow 30 seconds to establish connection
            ->get($tennisRecordLink);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch Tennis Record page. Status: {$response->status()}");
            }

            $html = $response->body();

            // Parse the HTML to extract team data
            $teamData = $this->parseTeamDataFromHtml($html, $tennisRecordLink);

            return $teamData;
        } catch (\Exception $e) {
            Log::error("Tennis Record scraping failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse team data from HTML content
     */
    private function parseTeamDataFromHtml($html, $tennisRecordLink)
    {
        // Extract team name
        $teamName = $this->extractTeamName($html, $tennisRecordLink);

        // Extract players
        $players = $this->extractPlayers($html);

        Log::info("Scraped Tennis Record team data", [
            'team_name' => $teamName,
            'player_count' => count($players),
            'players' => $players
        ]);

        return [
            'team_name' => $teamName,
            'players' => $players,
            'tennis_record_link' => $tennisRecordLink
        ];
    }

    /**
     * Extract team name from HTML
     */
    private function extractTeamName($html, $tennisRecordLink)
    {
        // Look for the team name in the table
        // Pattern: <td>Team Name</td> after "Team Profile" header
        if (preg_match('/<td style="text-align:left;" class="padding10">([^<]+)<\/td>[^<]*<\/tr>[^<]*<\/table>\s*<\/div>/s', $html, $matches)) {
            return trim($matches[1]);
        }

        // Alternative: look for the last table row before the player table
        if (preg_match_all('/<tr style="height:60px;"[^>]*>.*?<td[^>]*>([^<]+)<\/td>/s', $html, $matches)) {
            $possibleNames = $matches[1];
            foreach (array_reverse($possibleNames) as $name) {
                $name = trim($name);
                // Skip league names and take the team name
                if (!str_contains($name, 'Adult') && !str_contains($name, 'League') && strlen($name) > 5) {
                    return $name;
                }
            }
        }

        // Fallback: generate from URL
        if (preg_match('/teamname=([^&]+)/', $tennisRecordLink, $matches)) {
            return urldecode($matches[1]);
        }

        return 'Tennis Record Team ' . date('Y-m-d H:i');
    }

    /**
     * Extract players from HTML using DomCrawler
     */
    private function extractPlayers($html)
    {
        $players = [];

        try {
            $crawler = new Crawler($html);

            // Find the main roster table (looking for table with player links)
            $tables = $crawler->filter('table');
            $tableCount = $tables->count();

            foreach ($tables as $table) {
                $tableCrawler = new Crawler($table);

                // Find header row to identify column positions
                $headerCells = $tableCrawler->filter('tr')->first()->filter('th, td');
                $nameColumnIndex = null;
                $ratingColumnIndex = null;

                $headerCells->each(function (Crawler $cell, $index) use (&$nameColumnIndex, &$ratingColumnIndex) {
                    $text = strtolower(trim($cell->text()));
                    if ($text === 'name') {
                        $nameColumnIndex = $index;
                    } elseif ($text === 'rating') {
                        $ratingColumnIndex = $index;
                    }
                });

                Log::info("Found column indexes", [
                    'name_column' => $nameColumnIndex,
                    'rating_column' => $ratingColumnIndex
                ]);

                // Process each row in the table
                $tableCrawler->filter('tr')->each(function (Crawler $row) use (&$players, $nameColumnIndex, $ratingColumnIndex) {
                    // Skip header rows
                    if ($row->filter('th')->count() > 0) {
                        return;
                    }

                    $cells = $row->filter('td');

                    // Skip if not enough cells
                    if ($cells->count() === 0) {
                        return;
                    }

                    // Extract player name from the name column
                    $fullName = null;
                    $rating = null;
                    $playerProfileLink = null;

                    if ($nameColumnIndex !== null && $cells->count() > $nameColumnIndex) {
                        $nameCell = $cells->eq($nameColumnIndex);
                        $playerLink = $nameCell->filter('a.link[href*="profile.aspx"]');

                        Log::info($playerLink->attr('href'));

                        if ($playerLink->count() > 0) {
                            $fullName = trim($playerLink->text());

                            // Extract the player's Tennis Record profile link
                            $href = $playerLink->attr('href');
                            if ($href) {
                                // Build full URL if needed
                                if (!str_starts_with($href, 'http')) {
                                    $playerProfileLink = 'https://www.tennisrecord.com' . (str_starts_with($href, '/') ? '' : '/') . $href;
                                } else {
                                    $playerProfileLink = $href;
                                }
                            }
                        }
                    }

                    Log::info($playerProfileLink);

                    // Extract rating from the rating column
                    if ($ratingColumnIndex !== null && $cells->count() > $ratingColumnIndex) {
                        $ratingCell = $cells->eq($ratingColumnIndex);
                        $ratingText = trim($ratingCell->text());

                        // Extract numeric rating (e.g., "3.5" from text)
                        if (preg_match('/\d+\.\d+/', $ratingText, $matches)) {
                            $rating = $matches[0];
                        }
                    }

                    // Skip if no valid name found
                    if (!$fullName || strlen($fullName) < 3 || is_numeric($fullName)) {
                        return;
                    }

                    // Parse the name
                    $nameParts = $this->parsePlayerName($fullName);

                    if ($nameParts) {
                        $playerData = $nameParts;
                        if ($rating) {
                            $playerData['USTA_dynamic_rating'] = $rating;
                        }
                        if ($playerProfileLink) {
                            $playerData['tennis_record_link'] = $playerProfileLink;
                        }

                        $players[] = $playerData;
                    }
                });

                // If we found players in this table, we're done
                if (count($players) > 0) {
                    break;
                }
            }

        } catch (\Exception $e) {
            Log::error("Error parsing players with DomCrawler: " . $e->getMessage());
            // Fall back to empty array
            return [];
        }

        // Remove duplicates
        $uniquePlayers = [];
        foreach ($players as $player) {
            $key = strtolower(trim($player['first_name'] . ' ' . $player['last_name']));
            if (!isset($uniquePlayers[$key])) {
                $uniquePlayers[$key] = $player;
            }
        }

        return array_values($uniquePlayers);
    }

    /**
     * Parse player name into first and last name
     */
    private function parsePlayerName($fullName)
    {
        $fullName = trim($fullName);

        // Handle "Last, First" format
        if (strpos($fullName, ',') !== false) {
            $parts = explode(',', $fullName, 2);
            return [
                'first_name' => $this->cleanName(trim($parts[1])),
                'last_name' => $this->cleanName(trim($parts[0]))
            ];
        }

        // Handle "First Last" or "First Middle Last" format
        $words = preg_split('/\s+/', $fullName);

        if (count($words) >= 2) {
            // Take last word as last name, everything else as first name
            $lastName = array_pop($words);
            $firstName = implode(' ', $words);

            return [
                'first_name' => $this->cleanName($firstName),
                'last_name' => $this->cleanName($lastName)
            ];
        }

        return null;
    }

    /**
     * Clean and format name
     */
    private function cleanName($name)
    {
        $name = trim($name);
        // Keep original capitalization from Tennis Record
        return $name;
    }

    /**
     * Scrape league data to get all team links
     */
    public function scrapeLeagueTeams($tennisRecordLeagueLink)
    {
        try {
            Log::info("Starting to scrape Tennis Record league link: {$tennisRecordLeagueLink}");

            // Make request to Tennis Record league page
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])
            ->timeout(120)
            ->connectTimeout(30)
            ->get($tennisRecordLeagueLink);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch Tennis Record league page. Status: {$response->status()}");
            }

            $html = $response->body();

            // Parse the HTML to extract team links
            $teams = $this->extractTeamLinksFromLeague($html);

            Log::info("Scraped Tennis Record league data", [
                'team_count' => count($teams),
                'teams' => $teams
            ]);

            return $teams;
        } catch (\Exception $e) {
            Log::error("Tennis Record league scraping failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract team links from league HTML
     */
    private function extractTeamLinksFromLeague($html)
    {
        $teams = [];

        try {
            $crawler = new Crawler($html);

            // Find all team profile links
            $teamLinks = $crawler->filter('a.link[href*="teamprofile.aspx"]');

            $teamLinks->each(function (Crawler $link) use (&$teams) {
                $teamName = trim($link->text());
                $href = $link->attr('href');

                // Build full URL if needed
                if (!str_starts_with($href, 'http')) {
                    $href = 'https://www.tennisrecord.com' . (str_starts_with($href, '/') ? '' : '/') . $href;
                }

                // Avoid duplicates
                $key = strtolower($teamName);
                if (!isset($teams[$key]) && $teamName && strlen($teamName) > 3) {
                    $teams[$key] = [
                        'name' => $teamName,
                        'link' => $href
                    ];
                }
            });

            Log::info("Extracted team links from league", [
                'count' => count($teams)
            ]);

        } catch (\Exception $e) {
            Log::error("Error extracting team links from league: " . $e->getMessage());
            throw $e;
        }

        return array_values($teams);
    }
}
