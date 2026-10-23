<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * World Tennis Number (ITF) public player data, from the API worldtennisnumber.com loads from. No login needed.
 */
class WtnService
{
    private const ENDPOINT = 'https://prd-itf-kube.clubspark.pro/graphql';

    private const PERSON_FIELDS = 'tennisID nativeGivenName nativeFamilyName nationalityCode sex birthYear
        addresses { city state countryCode } worldTennisNumbers { type tennisNumber confidence }';

    public static function profileUrl(string $tennisId): string
    {
        return 'https://worldtennisnumber.com/eng/player-profile?tennis-id=' . $tennisId;
    }

    /**
     * People matching a name search (fuzzy, like the site's search box).
     */
    public function searchPersons(string $name, int $limit = 10): array
    {
        $data = $this->query('query p($filter: PublicPersonFilterOptions, $pageArgs: PaginationArgs) {
                publicPersons(filter: $filter, pageArgs: $pageArgs) { items { ' . self::PERSON_FIELDS . ' } }
            }', ['filter' => ['search' => ['term' => $name]], 'pageArgs' => ['limit' => $limit, 'skip' => 0]]);

        return $data['publicPersons']['items'] ?? [];
    }

    /**
     * One person by WTN tennis id, e.g. "DEM9468872".
     */
    public function fetchByTennisId(string $tennisId): ?array
    {
        return $this->fetchPerson($tennisId, 'TennisID');
    }

    /**
     * One person from a worldtennisnumber.com profile link, which uses either
     * ?tennis-id=DEM9468872 or the internal ?id=6517394ad60dcfca540da817.
     */
    public function fetchByProfileLink(string $link): ?array
    {
        if (preg_match('/[?&]tennis-id=([A-Z0-9]+)/i', $link, $m)) {
            return $this->fetchPerson(strtoupper($m[1]), 'TennisID');
        }
        if (preg_match('/[?&]id=([a-f0-9]{24})/i', $link, $m)) {
            return $this->fetchPerson($m[1], 'ID');
        }
        return null;
    }

    private function fetchPerson(string $identifier, string $type): ?array
    {
        $data = $this->query('query p($id: PersonIDInput!) { person(id: $id) { ' . self::PERSON_FIELDS . ' } }',
            ['id' => ['identifier' => $identifier, 'type' => $type]]);

        return $data['person'] ?? null;
    }

    /**
     * ['singles' => 24.34, 'doubles' => 24.25] from a person's worldTennisNumbers.
     */
    public static function numbers(array $person): array
    {
        $byType = collect($person['worldTennisNumbers'] ?? [])->keyBy('type');
        return [
            'singles' => $byType['SINGLE']['tennisNumber'] ?? null,
            'doubles' => $byType['DOUBLE']['tennisNumber'] ?? null,
        ];
    }

    private function query(string $query, array $variables): array
    {
        $response = Http::withHeaders(['Origin' => 'https://worldtennisnumber.com'])
            ->timeout(30)
            ->post(self::ENDPOINT, ['query' => $query, 'variables' => $variables]);

        if (!$response->successful()) {
            throw new \Exception("WTN request failed. Status: {$response->status()}");
        }

        $json = $response->json() ?? [];
        if (!empty($json['errors']) && empty($json['data'])) {
            throw new \Exception('WTN returned errors: ' . json_encode($json['errors']));
        }

        return $json['data'] ?? [];
    }
}
