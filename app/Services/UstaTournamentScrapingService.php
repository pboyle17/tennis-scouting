<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class UstaTournamentScrapingService
{
    /**
     * Scrape tournament data from USTA playtennis.usta.com page
     */
    public function scrapeTournamentData($ustaLink)
    {
        try {
            Log::info("Starting to scrape USTA tournament link: {$ustaLink}");

            // Make request to USTA page
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->timeout(30)->get($ustaLink);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch USTA tournament page. Status: {$response->status()}");
            }

            $html = $response->body();

            // Parse the HTML to extract tournament data
            return $this->parseTournamentDataFromHtml($html, $ustaLink);

        } catch (\Exception $e) {
            Log::error("USTA tournament scraping failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse tournament data from HTML content
     */
    private function parseTournamentDataFromHtml($html, $ustaLink)
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $tournamentData = [
            'name' => null,
            'start_date' => null,
            'end_date' => null,
            'location' => null,
            'description' => null,
            'usta_link' => $ustaLink,
        ];

        // Extract tournament name from page title or h1
        $titleNodes = $xpath->query('//h1[contains(@class, "tournament-name")] | //h1 | //title');
        if ($titleNodes->length > 0) {
            $title = trim($titleNodes->item(0)->textContent);
            // Clean up title (remove " - USTA" or similar suffixes)
            $title = preg_replace('/ - (USTA|Tournament).*$/i', '', $title);
            $tournamentData['name'] = $title;
        }

        // Try to extract dates from various possible elements
        // Look for date patterns like "Jan 15-20, 2024" or "January 15, 2024"
        $datePatterns = [
            '//div[contains(@class, "date")] | //span[contains(@class, "date")]',
            '//div[contains(@class, "tournament-date")]',
            '//div[contains(text(), "Date")]',
        ];

        foreach ($datePatterns as $pattern) {
            $dateNodes = $xpath->query($pattern);
            if ($dateNodes->length > 0) {
                $dateText = trim($dateNodes->item(0)->textContent);
                $dates = $this->extractDatesFromText($dateText);
                if ($dates['start_date']) {
                    $tournamentData['start_date'] = $dates['start_date'];
                    $tournamentData['end_date'] = $dates['end_date'];
                    break;
                }
            }
        }

        // Extract location
        $locationPatterns = [
            '//div[contains(@class, "location")] | //span[contains(@class, "location")]',
            '//div[contains(@class, "tournament-location")]',
            '//div[contains(text(), "Location")]',
        ];

        foreach ($locationPatterns as $pattern) {
            $locationNodes = $xpath->query($pattern);
            if ($locationNodes->length > 0) {
                $location = trim($locationNodes->item(0)->textContent);
                $location = preg_replace('/^Location:?\s*/i', '', $location);
                if (!empty($location) && strlen($location) > 2) {
                    $tournamentData['location'] = $location;
                    break;
                }
            }
        }

        // Extract description/details
        $descriptionPatterns = [
            '//div[contains(@class, "description")] | //div[contains(@class, "details")]',
            '//div[contains(@class, "tournament-description")]',
            '//p[contains(@class, "description")]',
        ];

        foreach ($descriptionPatterns as $pattern) {
            $descriptionNodes = $xpath->query($pattern);
            if ($descriptionNodes->length > 0) {
                $description = trim($descriptionNodes->item(0)->textContent);
                if (!empty($description) && strlen($description) > 10) {
                    $tournamentData['description'] = substr($description, 0, 500); // Limit length
                    break;
                }
            }
        }

        // Fallback: Try to extract from meta tags
        if (!$tournamentData['name']) {
            $metaTitleNodes = $xpath->query('//meta[@property="og:title"]/@content | //meta[@name="title"]/@content');
            if ($metaTitleNodes->length > 0) {
                $tournamentData['name'] = trim($metaTitleNodes->item(0)->textContent);
            }
        }

        // If still no name, extract from URL
        if (!$tournamentData['name']) {
            // Extract tournament name from URL pattern
            if (preg_match('/\/Competitions\/([^\/]+)\/Tournaments/', $ustaLink, $matches)) {
                $tournamentData['name'] = ucwords(str_replace('-', ' ', $matches[1]));
            } else {
                $tournamentData['name'] = 'USTA Tournament';
            }
        }

        Log::info("Scraped USTA tournament data", $tournamentData);

        return $tournamentData;
    }

    /**
     * Extract start and end dates from text
     */
    private function extractDatesFromText($text)
    {
        $dates = [
            'start_date' => null,
            'end_date' => null,
        ];

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));

        // Try to match date range patterns like "Jan 15-20, 2024" or "January 15-20, 2024"
        if (preg_match('/([A-Za-z]+)\s+(\d{1,2})\s*[-–]\s*(\d{1,2}),?\s+(\d{4})/', $text, $matches)) {
            $month = $matches[1];
            $startDay = $matches[2];
            $endDay = $matches[3];
            $year = $matches[4];

            try {
                $dates['start_date'] = date('Y-m-d', strtotime("$month $startDay, $year"));
                $dates['end_date'] = date('Y-m-d', strtotime("$month $endDay, $year"));
            } catch (\Exception $e) {
                Log::warning("Failed to parse date range: " . $e->getMessage());
            }
        }
        // Try to match single date like "January 15, 2024"
        elseif (preg_match('/([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})/', $text, $matches)) {
            try {
                $dateStr = $matches[0];
                $date = date('Y-m-d', strtotime($dateStr));
                $dates['start_date'] = $date;
                $dates['end_date'] = $date;
            } catch (\Exception $e) {
                Log::warning("Failed to parse single date: " . $e->getMessage());
            }
        }
        // Try ISO format dates
        elseif (preg_match('/(\d{4}-\d{2}-\d{2})/', $text, $matches)) {
            $dates['start_date'] = $matches[1];
            $dates['end_date'] = $matches[1];
        }

        return $dates;
    }
}
