<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Configuration;
use Illuminate\Support\Facades\Log;

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
}
