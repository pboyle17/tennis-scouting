<?php

namespace App\Jobs;

use App\Exceptions\UtrRateLimitException;
use App\Models\Player;
use App\Models\Tournament;
use App\Services\UstaTournamentService;
use App\Services\UtrService;
use App\Services\WtnService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Add everyone registered for a USTA tournament to the tournament, creating players as needed,
 * then tie players to their UTR profile and World Tennis Number.
 * Stores names, USTA ids and UTR/WTN ids and ratings; city/state/gender are only used to pick the right match.
 */
class ImportUstaTournamentPlayersJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 1800;
    public $tries = 1;

    public function __construct(public Tournament $tournament, public string $ustaTournamentId)
    {
    }

    public static function statusKey(int $tournamentId): string
    {
        return "usta_import_{$tournamentId}";
    }

    public function handle(UstaTournamentService $usta, UtrService $utr, WtnService $wtn): void
    {
        $stats = ['registrations' => 0, 'created' => 0, 'existing' => 0, 'ambiguous' => 0,
                  'utr_matched' => 0, 'utr_unmatched' => 0, 'utr_already' => 0, 'rate_limited' => false,
                  'wtn_matched' => 0, 'wtn_unmatched' => 0, 'wtn_refreshed' => 0];
        $imported = []; // player id => ['player' => Player, 'reg' => registration]
        $this->status('importing players', $stats);

        $toMatch = []; // player id => registration, for players still without a UTR id
        $pivot = [];   // player id => ['flights' => [...]]

        foreach ($usta->fetchRegistrations($this->ustaTournamentId) as $reg) {
            $first = $this->cleanName($reg['firstName'] ?? '');
            $last = $this->cleanName($reg['lastName'] ?? '');
            if (!$first || !$last) {
                continue;
            }
            $stats['registrations']++;

            $ustaId = UstaTournamentService::ustaId($reg);

            // Same USTA id is a sure match; otherwise fall back to the name
            $existing = $ustaId ? Player::where('usta_id', $ustaId)->get() : collect();
            if ($existing->isEmpty()) {
                $existing = Player::whereRaw('LOWER(first_name) = ?', [mb_strtolower($first)])
                    ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($last)])
                    ->when($ustaId, fn($q) => $q->where(fn($q) => $q->whereNull('usta_id')->orWhere('usta_id', $ustaId)))
                    ->get();
            }

            if ($existing->count() > 1) {
                // Several players share this name; attach none rather than guess
                $stats['ambiguous']++;
                Log::info("USTA import: skipped {$first} {$last}, {$existing->count()} players share that name");
                continue;
            }

            if ($player = $existing->first()) {
                $stats['existing']++;
            } else {
                $player = Player::create(['first_name' => $first, 'last_name' => $last, 'usta_id' => $ustaId]);
                $stats['created']++;
            }
            if ($ustaId && !$player->usta_id) {
                $player->update(['usta_id' => $ustaId]);
            }
            $imported[$player->id] = ['player' => $player, 'reg' => $reg];
            // cast to JSON by TournamentPlayer
            $pivot[$player->id] = ['flights' => collect($reg['events'] ?? [])
                ->map(fn($e) => static::flightName($e))->filter()->unique()->sort()->values()->all()];

            if ($player->utr_id) {
                $stats['utr_already']++;
            } else {
                $toMatch[$player->id] = ['player' => $player, 'reg' => $reg];
            }
        }

        $this->tournament->players()->syncWithoutDetaching($pivot);
        $this->status('matching UTR profiles', $stats);

        foreach ($toMatch as ['player' => $player, 'reg' => $reg]) {
            try {
                $match = $this->findUtrMatch($utr, $player, $reg);
            } catch (UtrRateLimitException $e) {
                $stats['rate_limited'] = true;
                $stats['utr_unmatched'] += count($toMatch) - $stats['utr_matched'] - $stats['utr_unmatched'];
                Log::warning("USTA import: UTR rate limited, stopping UTR matching early. " . $e->getMessage());
                break;
            } catch (\Exception $e) {
                Log::error("USTA import: UTR search failed for {$player->first_name} {$player->last_name}: " . $e->getMessage());
                $match = null;
            }

            if ($match) {
                $player->utr_id = $match['id'];
                $player->utr_singles_rating = $match['singlesUtr'] ?? null;
                $player->utr_doubles_rating = $match['doublesUtr'] ?? null;
                $player->utr_singles_reliable = isset($match['ratingProgressSingles']) && $match['ratingProgressSingles'] == 100;
                $player->utr_doubles_reliable = isset($match['ratingProgressDoubles']) && $match['ratingProgressDoubles'] == 100;
                $player->utr_singles_updated_at = now();
                $player->utr_doubles_updated_at = now();
                $player->save();
                $stats['utr_matched']++;
            } else {
                $stats['utr_unmatched']++;
            }

            $this->status('matching UTR profiles', $stats);
            usleep(500000); // be gentle with the UTR search API
        }

        $this->matchWtn($wtn, $imported, $stats);

        $this->status('completed', $stats);
        Log::info("USTA import for tournament {$this->tournament->id} completed", $stats);
    }

    /**
     * The single UTR profile with exactly this name, narrowed by state, city and gender when
     * several share the name. Returns null unless exactly one candidate is left.
     */
    protected function findUtrMatch(UtrService $utr, Player $player, array $reg): ?array
    {
        $results = $utr->searchPlayers("{$player->first_name} {$player->last_name}", 10);
        $hits = collect($results['players']['hits'] ?? $results['hits'] ?? [])->pluck('source')->filter();

        $sameName = $hits->filter(fn($h) =>
            mb_strtolower(trim($h['firstName'] ?? '')) === mb_strtolower($player->first_name)
            && mb_strtolower(trim($h['lastName'] ?? '')) === mb_strtolower($player->last_name)
        )->values();

        $narrowers = [
            fn($h) => !empty($reg['state']) && str_ends_with(mb_strtolower($h['location']['display'] ?? ''), ', ' . mb_strtolower($reg['state'])),
            fn($h) => !empty($reg['city']) && str_starts_with(mb_strtolower($h['location']['display'] ?? ''), mb_strtolower($reg['city']) . ','),
            fn($h) => !empty($reg['gender']) && mb_strtolower($h['gender'] ?? '') === mb_strtolower($reg['gender']),
        ];
        foreach ($narrowers as $narrow) {
            if ($sameName->count() <= 1) {
                break;
            }
            $narrowed = $sameName->filter($narrow)->values();
            if ($narrowed->isNotEmpty()) {
                $sameName = $narrowed;
            }
        }

        return $sameName->count() === 1 ? $sameName->first() : null;
    }

    /**
     * Refresh WTN numbers for players with a WTN id, and look up the rest by name.
     */
    protected function matchWtn(WtnService $wtn, array $imported, array &$stats): void
    {
        $this->status('matching World Tennis Numbers', $stats);

        foreach ($imported as ['player' => $player, 'reg' => $reg]) {
            try {
                if ($player->wtn_id) {
                    $person = $wtn->fetchByTennisId($player->wtn_id);
                    $stats['wtn_refreshed'] += $person ? 1 : 0;
                } else {
                    $person = $this->findWtnMatch($wtn, $player, $reg);
                    $person ? $stats['wtn_matched']++ : $stats['wtn_unmatched']++;
                }
            } catch (\Exception $e) {
                Log::error("USTA import: WTN lookup failed for {$player->first_name} {$player->last_name}: " . $e->getMessage());
                $person = null;
                $player->wtn_id ?: $stats['wtn_unmatched']++;
            }

            if ($person && !Player::where('wtn_id', $person['tennisID'])->where('id', '!=', $player->id)->exists()) {
                $numbers = WtnService::numbers($person);
                $player->wtn_id = $person['tennisID'];
                $player->tennis_number_link = $player->tennis_number_link ?: WtnService::profileUrl($person['tennisID']);
                $player->tennis_number_singles_rating = $numbers['singles'] ?? $player->tennis_number_singles_rating;
                $player->tennis_number_doubles_rating = $numbers['doubles'] ?? $player->tennis_number_doubles_rating;
                $player->save();
            }

            $this->status('matching World Tennis Numbers', $stats);
            usleep(300000);
        }
    }

    /**
     * The single WTN person with exactly this name, narrowed to USA, then the registration's
     * gender and state. Returns null unless exactly one candidate is left.
     */
    protected function findWtnMatch(WtnService $wtn, Player $player, array $reg): ?array
    {
        $sameName = collect($wtn->searchPersons("{$player->first_name} {$player->last_name}", 10))
            ->filter(fn($p) =>
                mb_strtolower(trim($p['nativeGivenName'] ?? '')) === mb_strtolower($player->first_name)
                && mb_strtolower(trim($p['nativeFamilyName'] ?? '')) === mb_strtolower($player->last_name)
            )->values();

        $sex = match (strtoupper($reg['gender'] ?? '')) { 'MALE' => 'M', 'FEMALE' => 'F', default => null };
        $narrowers = [
            fn($p) => ($p['nationalityCode'] ?? null) === 'USA',
            fn($p) => $sex && ($p['sex'] ?? null) === $sex,
            fn($p) => !empty($reg['state']) && collect($p['addresses'] ?? [])->contains(fn($a) => strcasecmp($a['state'] ?? '', $reg['state']) === 0),
        ];
        foreach ($narrowers as $narrow) {
            if ($sameName->count() <= 1) {
                break;
            }
            $narrowed = $sameName->filter($narrow)->values();
            if ($narrowed->isNotEmpty()) {
                $sameName = $narrowed;
            }
        }

        return $sameName->count() === 1 ? $sameName->first() : null;
    }

    /**
     * A readable flight name from a USTA event's division, e.g. "Girls 18U Singles" or "Men's NTRP 3.5 Doubles".
     */
    public static function flightName(array $event): ?string
    {
        $division = $event['division'] ?? [];
        $gender = match (strtoupper($division['gender'] ?? '')) {
            'BOYS' => 'Boys', 'GIRLS' => 'Girls',
            'MALE', 'MEN', 'MENS' => "Men's", 'FEMALE', 'WOMEN', 'WOMENS' => "Women's",
            'MIXED', 'COED' => 'Mixed',
            default => null,
        };
        $age = $division['ageCategory']['todsCode'] ?? null;
        if ($age && preg_match('/^U(\d+)$/i', $age, $m)) {
            $age = $m[1] . 'U';
        } elseif ($age && preg_match('/^O(\d+)$/i', $age, $m)) {
            $age = $m[1] . '+';
        }
        $rating = $division['ratingCategory'] ?? null;
        $rating = !empty($rating['value']) ? trim(($rating['ratingType'] ?? '') . ' ' . $rating['value']) : null;
        $type = isset($division['eventType']) ? ucfirst(strtolower($division['eventType'])) : null;

        $name = collect([$gender, $age, $rating, $type])->filter()->implode(' ');
        return $name !== '' ? $name : null;
    }

    protected function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    protected function status(string $stage, array $stats): void
    {
        Cache::put(static::statusKey($this->tournament->id), ['stage' => $stage, 'at' => now()->toDateTimeString()] + $stats, now()->addDay());
    }
}
