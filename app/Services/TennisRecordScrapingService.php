<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            ])->timeout(30)->get($tennisRecordLink);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch Tennis Record page. Status: {$response->status()}");
            }

            $html = $response->body();

            // Parse the HTML to extract team data
            return $this->parseTeamDataFromHtml($html, $tennisRecordLink);

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
        $teamName = $this->extractTeamName($html);

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
    private function extractTeamName($html)
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
     * Extract players from HTML
     */
    private function extractPlayers($html)
    {
        $players = [];

        // Pattern to find player profile links
        // <a class="link" href="/adult/profile.aspx?playername=First Last">First Last</a>
        $pattern = '/<a class="link" href="\/adult\/profile\.aspx\?playername=([^"]+)">([^<]+)<\/a>/';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullName = trim($match[2]);

                // Skip if this doesn't look like a valid name
                if (strlen($fullName) < 3 || is_numeric($fullName)) {
                    continue;
                }

                // Parse the name
                $nameParts = $this->parsePlayerName($fullName);

                if ($nameParts) {
                    $players[] = $nameParts;
                }
            }
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
}
