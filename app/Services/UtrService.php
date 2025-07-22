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

        // Log::info("JWT: {$jwt}: ");

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
}
