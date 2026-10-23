<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Reads public tournament data from USTA's playtennis tournament GraphQL API
 * (the same API the playtennis.usta.com tournament pages load from). No login needed.
 */
class UstaTournamentService
{
    private const ENDPOINT = 'https://prd-usta-kube-tournaments.clubspark.pro/';

    /**
     * The tournament id (a GUID) from any playtennis.usta.com tournament page link,
     * e.g. .../Tournaments/players/6B9D5E94-4276-410A-98E5-4E0E63586D82
     */
    public static function parseTournamentId(?string $link): ?string
    {
        if ($link && str_contains($link, 'playtennis.usta.com')
            && preg_match('#/Tournaments/[^/]+/([0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12})#i', $link, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }

    /**
     * Name, dates and venue.
     */
    public function fetchTournament(string $tournamentId): array
    {
        $data = $this->query('GetTournament', '
            query GetTournament($id: UUID!) {
                publishedTournament(id: $id) {
                    id
                    name
                    timings { startDate endDate }
                    primaryLocation { name town county }
                }
            }', ['id' => $tournamentId]);

        $tournament = $data['publishedTournament'] ?? null;
        if (!$tournament) {
            throw new \Exception("USTA tournament {$tournamentId} not found");
        }
        return $tournament;
    }

    /**
     * Everyone registered for the tournament: name, gender, city/state and the events entered.
     */
    public function fetchRegistrations(string $tournamentId): array
    {
        $data = $this->query('GetPlayers', '
            query GetPlayers($id: UUID!, $queryParameters: QueryParametersPaged!) {
                paginatedPublicTournamentRegistrations(tournamentId: $id, queryParameters: $queryParameters) {
                    totalItems
                    items {
                        firstName: playerFirstName
                        lastName: playerLastName
                        gender: playerGender
                        city: playerCity
                        state: playerState
                        playerCustomIds { key value }
                        events { id division { eventType gender ageCategory { todsCode } ratingCategory { ratingType value } } }
                    }
                }
            }', [
                'id' => $tournamentId,
                // limit 0 returns every registration, as the playtennis page does
                'queryParameters' => [
                    'limit' => 0,
                    'offset' => 0,
                    'sorts' => [['property' => 'playerLastName', 'sortDirection' => 'ASCENDING']],
                    'filters' => [],
                ],
            ]);

        return $data['paginatedPublicTournamentRegistrations']['items'] ?? [];
    }

    /**
     * The USTA member id (UAID) from a registration's custom ids.
     */
    public static function ustaId(array $registration): ?string
    {
        $id = collect($registration['playerCustomIds'] ?? [])->firstWhere('key', 'ustaId')['value'] ?? null;
        return $id ? (string) $id : null;
    }

    private function query(string $operation, string $query, array $variables): array
    {
        $response = Http::withHeaders(['Origin' => 'https://playtennis.usta.com'])
            ->timeout(30)
            ->post(self::ENDPOINT, [
                'operationName' => $operation,
                'variables' => $variables,
                'query' => $query,
            ]);

        if (!$response->successful()) {
            throw new \Exception("USTA {$operation} request failed. Status: {$response->status()}");
        }

        $json = $response->json() ?? [];
        if (!empty($json['errors']) && empty($json['data'])) {
            throw new \Exception("USTA {$operation} returned errors: " . json_encode($json['errors']));
        }

        return $json['data'] ?? [];
    }
}
