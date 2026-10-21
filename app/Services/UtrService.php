<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Configuration;
use Illuminate\Support\Facades\Log;
use App\Exceptions\UtrRateLimitException;

class UtrService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function fetchUtrRating($utrId)
    {
        $config = Configuration::first();

        if (!$config || !$config->jwt) {
            throw new \Exception("No JWT configured in configurations table.");
        }

        $jwt = $config->jwt;

        $response = Http::withHeaders([
          'Authorization' => 'Bearer ' . $jwt
        ])->get("https://api.utrsports.net/v1/player/{$utrId}/profile");

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->status() === 429) {
            $retryAfter = $response->header('Retry-After');

            Log::error("UTR API rate limited (429) fetching player {$utrId}", [
                'utr_id' => $utrId,
                'retry_after' => $retryAfter,
                'headers' => $response->headers(),
            ]);

            throw new UtrRateLimitException(
                "UTR API rate limited (429) for ID {$utrId}." . ($retryAfter ? " Retry-After: {$retryAfter}" : ''),
                $retryAfter !== null ? (int) $retryAfter : null
            );
        }

        throw new \Exception(
          "Failed to fetch UTR rating for ID {$utrId}. " .
          "Status: {$response->status()}. " .
          "Body: " . json_encode($response->json())
      );
    }

    public function searchPlayers($playerName, $top = 10)
    {
        $config = Configuration::first();

        if (!$config || !$config->jwt) {
            throw new \Exception("No JWT configured in configurations table.");
        }

        $jwt = $config->jwt;

        // Format the query like the JavaScript example
        $queryName = trim($playerName);
        $queryName = str_replace(' ', '+', $queryName);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $jwt
        ])->get("https://api.utrsports.net/v2/search", [
            'query' => $queryName,
            'top' => $top,
            'skip' => 0
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception(
            "Failed to search for player '{$playerName}'. " .
            "Status: {$response->status()}. " .
            "Body: " . json_encode($response->json())
        );
    }

    /**
     * Imported (e.g. USTA-sanctioned) event details: name, dates, game types.
     */
    public function fetchImportedEvent(int $eventId): array
    {
        return $this->getJson("v1/imported/events/{$eventId}");
    }

    /**
     * Draws (divisions) in an imported event.
     */
    public function fetchImportedEventDraws(int $eventId): array
    {
        return $this->getJson("v1/imported/events/{$eventId}/draws");
    }

    /**
     * Match results for one draw of an imported event.
     */
    public function fetchImportedEventResults(int $eventId, int $drawId): array
    {
        return $this->getJson("v1/imported/events/{$eventId}/results", ['drawId' => $drawId]);
    }

    /**
     * All players in an imported event (follows paging).
     */
    public function fetchImportedEventPlayers(int $eventId): array
    {
        $players = [];
        $skip = 0;
        $pageSize = 100;

        do {
            $page = $this->getJson("v1/imported/events/{$eventId}/players", [
                'order' => 'desc', 'sort' => 'singlesUtr', 'skip' => $skip, 'top' => $pageSize,
            ]);
            $batch = $page['players'] ?? [];
            $players = array_merge($players, $batch);
            $skip += $pageSize;
        } while (count($batch) === $pageSize && $skip < ($page['totalCount'] ?? 0));

        return $players;
    }

    /**
     * Authenticated GET against the UTR API, throwing UtrRateLimitException on 429.
     */
    private function getJson(string $path, array $query = []): array
    {
        $config = Configuration::first();

        if (!$config || !$config->jwt) {
            throw new \Exception("No JWT configured in configurations table.");
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config->jwt
        ])->get("https://api.utrsports.net/{$path}", $query);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        if ($response->status() === 429) {
            $retryAfter = $response->header('Retry-After');
            throw new UtrRateLimitException(
                "UTR API rate limited (429) for {$path}." . ($retryAfter ? " Retry-After: {$retryAfter}" : ''),
                $retryAfter !== null ? (int) $retryAfter : null
            );
        }

        throw new \Exception("UTR API request failed for {$path}. Status: {$response->status()}.");
    }
}
