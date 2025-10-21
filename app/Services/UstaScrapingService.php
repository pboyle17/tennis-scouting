<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UstaScrapingService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Scrape team data from USTA TennisLink page
     */
    public function scrapeTeamData($ustaLink)
    {
        try {
            Log::info("Starting to scrape USTA link: {$ustaLink}");

            // Make request to USTA page
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->timeout(30)->get($ustaLink);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch USTA page. Status: {$response->status()}");
            }

            $html = $response->body();

            // Parse the HTML to extract team data
            return $this->parseTeamDataFromHtml($html, $ustaLink);

        } catch (\Exception $e) {
            Log::error("USTA scraping failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse team data from HTML content
     */
    private function parseTeamDataFromHtml($html, $ustaLink)
    {
        // Extract team name - look for various patterns
        $teamName = $this->extractTeamName($html);

        // Extract players from roster tables
        $players = $this->extractPlayers($html);

        Log::info("Scraped team data", [
            'team_name' => $teamName,
            'player_count' => count($players),
            'players' => $players
        ]);

        return [
            'team_name' => $teamName,
            'players' => $players,
            'usta_link' => $ustaLink
        ];
    }

    /**
     * Extract team name from HTML
     */
    private function extractTeamName($html)
    {
        // Try multiple patterns to find team name
        $patterns = [
            // Look for team name in title or headings
            '/<title[^>]*>([^<]*team[^<]*)<\/title>/i',
            '/<h1[^>]*>([^<]*)<\/h1>/i',
            '/<h2[^>]*>([^<]*)<\/h2>/i',
            // Look for specific USTA patterns
            '/team[^:]*:\s*([^<\n\r]+)/i',
            '/roster[^:]*:\s*([^<\n\r]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $teamName = trim($matches[1]);
                if (strlen($teamName) > 3 && strlen($teamName) < 100) {
                    // Clean up the team name
                    $teamName = html_entity_decode($teamName);
                    $teamName = preg_replace('/\s+/', ' ', $teamName);
                    return $teamName;
                }
            }
        }

        // Fallback: generate name from URL
        return $this->generateTeamNameFromUrl($html);
    }

    /**
     * Generate team name from URL or page content
     */
    private function generateTeamNameFromUrl($html)
    {
        // Look for any mentions of team or club names
        if (preg_match('/([A-Z][a-z]+\s+[A-Z][a-z]+\s+(?:Tennis\s+)?(?:Club|Team|Academy))/i', $html, $matches)) {
            return trim($matches[1]);
        }

        // Default fallback
        return 'USTA Team ' . date('Y-m-d H:i');
    }

    /**
     * Extract players from HTML tables
     */
    private function extractPlayers($html)
    {
        $players = [];

        // Remove script and style tags to avoid false matches
        $cleanHtml = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html);
        $cleanHtml = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $cleanHtml);

        // Try to find player data in tables
        $players = array_merge($players, $this->extractPlayersFromTables($cleanHtml));

        // Try to find player data in lists
        $players = array_merge($players, $this->extractPlayersFromLists($cleanHtml));

        // Remove duplicates based on full name
        $uniquePlayers = [];
        foreach ($players as $player) {
            $key = strtolower(trim($player['first_name'] . ' ' . $player['last_name']));
            if (!isset($uniquePlayers[$key]) && strlen($key) > 3) {
                $uniquePlayers[$key] = $player;
            }
        }

        return array_values($uniquePlayers);
    }

    /**
     * Extract players from HTML tables
     */
    private function extractPlayersFromTables($html)
    {
        $players = [];

        // Find all table rows that might contain player data
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $tableMatches)) {
            foreach ($tableMatches[1] as $rowContent) {
                // Skip header rows
                if (stripos($rowContent, '<th') !== false) {
                    continue;
                }

                // Extract cell content
                if (preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $rowContent, $cellMatches)) {
                    $cells = array_map(function($cell) {
                        return trim(strip_tags($cell));
                    }, $cellMatches[1]);

                    // Look for patterns that indicate player data
                    $player = $this->extractPlayerFromCells($cells);
                    if ($player) {
                        $players[] = $player;
                    }
                }
            }
        }

        return $players;
    }

    /**
     * Extract players from HTML lists or other structures
     */
    private function extractPlayersFromLists($html)
    {
        $players = [];

        // Look for player name patterns in the HTML
        $namePatterns = [
            // Standard name patterns
            '/([A-Z][a-z]+),\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]*)?)/i', // Last, First Middle
            '/([A-Z][a-z]+)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]*)?)/i',   // First Middle Last
        ];

        foreach ($namePatterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    // Check if this looks like a player name (not just any text)
                    if ($this->isLikelyPlayerName($match)) {
                        $players[] = $this->formatPlayerName($match);
                    }
                }
            }
        }

        return $players;
    }

    /**
     * Extract player from table cells
     */
    private function extractPlayerFromCells($cells)
    {
        if (count($cells) < 2) {
            return null;
        }

        // Try different cell arrangements
        foreach ($cells as $cell) {
            if ($this->containsPlayerName($cell)) {
                return $this->parseNameString($cell);
            }
        }

        // Try combining first two cells as first/last name
        $firstCell = $cells[0];
        $secondCell = $cells[1];

        if ($this->isValidName($firstCell) && $this->isValidName($secondCell)) {
            return [
                'first_name' => $this->cleanName($firstCell),
                'last_name' => $this->cleanName($secondCell)
            ];
        }

        return null;
    }

    /**
     * Check if string contains a player name
     */
    private function containsPlayerName($string)
    {
        // Must contain letters and be reasonable length
        return preg_match('/^[A-Za-z\s\.\-\']{3,50}$/', trim($string)) &&
               str_word_count($string) >= 2;
    }

    /**
     * Parse name string into first/last name
     */
    private function parseNameString($nameString)
    {
        $nameString = trim($nameString);

        // Handle "Last, First" format
        if (strpos($nameString, ',') !== false) {
            $parts = explode(',', $nameString, 2);
            return [
                'first_name' => $this->cleanName($parts[1]),
                'last_name' => $this->cleanName($parts[0])
            ];
        }

        // Handle "First Last" or "First Middle Last" format
        $words = explode(' ', $nameString);
        if (count($words) >= 2) {
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
     * Check if this is likely a player name
     */
    private function isLikelyPlayerName($match)
    {
        $fullMatch = $match[0];

        // Skip if contains numbers (likely scores or ratings)
        if (preg_match('/\d/', $fullMatch)) {
            return false;
        }

        // Skip common non-name words
        $skipWords = ['team', 'club', 'tennis', 'league', 'division', 'match', 'game', 'set'];
        foreach ($skipWords as $word) {
            if (stripos($fullMatch, $word) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format player name from regex match
     */
    private function formatPlayerName($match)
    {
        if (strpos($match[0], ',') !== false) {
            // "Last, First" format
            return [
                'first_name' => $this->cleanName($match[2]),
                'last_name' => $this->cleanName($match[1])
            ];
        } else {
            // "First Last" format
            return [
                'first_name' => $this->cleanName($match[1]),
                'last_name' => $this->cleanName($match[2])
            ];
        }
    }

    /**
     * Check if string is a valid name
     */
    private function isValidName($string)
    {
        $string = trim($string);
        return strlen($string) >= 2 &&
               strlen($string) <= 30 &&
               preg_match('/^[A-Za-z\s\.\-\']+$/', $string) &&
               !is_numeric($string);
    }

    /**
     * Clean and format name
     */
    private function cleanName($name)
    {
        $name = trim($name);
        $name = ucwords(strtolower($name));
        return $name;
    }
}
