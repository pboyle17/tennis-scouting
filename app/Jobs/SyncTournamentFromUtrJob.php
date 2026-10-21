<?php

namespace App\Jobs;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentDraw;
use App\Models\TournamentMatch;
use App\Services\UtrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pull an imported UTR event (players, draws, match results) into a tournament.
 * Only names, UTR ids and ratings are stored; contact details in the payload are ignored.
 */
class SyncTournamentFromUtrJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 600;
    public $tries = 1;

    /** @var array<int, Player> players resolved so far, keyed by UTR id */
    protected array $players = [];

    public function __construct(public Tournament $tournament)
    {
    }

    public function handle(UtrService $utr): void
    {
        $eventId = $this->tournament->utrEventId();
        if (!$eventId) {
            Log::warning("Tournament {$this->tournament->id} has no UTR event id in its UTR link");
            return;
        }

        $event = $utr->fetchImportedEvent($eventId);
        $this->tournament->fill([
            'start_date' => $this->tournament->start_date ?? ($event['startDate'] ?? null),
            'end_date' => $this->tournament->end_date ?? ($event['endDate'] ?? null),
        ]);

        // Players, with their current ratings
        $playerIds = [];
        foreach ($utr->fetchImportedEventPlayers($eventId) as $data) {
            if ($player = $this->resolvePlayer($data, updateRatings: true)) {
                $playerIds[] = $player->id;
            }
        }
        $this->tournament->players()->syncWithoutDetaching($playerIds);

        // Draws and their results
        $drawCount = 0;
        $matchCount = 0;
        foreach ($utr->fetchImportedEventDraws($eventId) as $drawData) {
            usleep(500000); // be gentle with the UTR API between draw requests
            $results = $utr->fetchImportedEventResults($eventId, $drawData['id']);

            DB::transaction(function () use ($drawData, $results, &$matchCount) {
                $draw = TournamentDraw::updateOrCreate(
                    ['tournament_id' => $this->tournament->id, 'utr_draw_id' => $drawData['id']],
                    [
                        'name' => $drawData['name'],
                        'team_type' => $drawData['teamType'] ?? null,
                        'gender' => ($drawData['gender'] ?? '') ?: null,
                        'player_count' => $drawData['playerCount'] ?? null,
                    ]
                );

                foreach ($results['draws'][0]['matches'] ?? [] as $matchData) {
                    $this->syncMatch($draw, $matchData);
                    $matchCount++;
                }
            });
            $drawCount++;
        }

        $this->tournament->utr_last_synced_at = now();
        $this->tournament->save();

        Log::info("Synced tournament {$this->tournament->id} from UTR event {$eventId}", [
            'players' => count($playerIds),
            'draws' => $drawCount,
            'matches' => $matchCount,
        ]);
    }

    protected function syncMatch(TournamentDraw $draw, array $matchData): void
    {
        $result = $matchData['result'] ?? [];
        if (empty($result['id'])) {
            return;
        }

        $match = TournamentMatch::updateOrCreate(
            ['utr_result_id' => $result['id']],
            [
                'tournament_draw_id' => $draw->id,
                'played_on' => isset($matchData['date']) ? substr($matchData['date'], 0, 10) : null,
                'round_code' => $result['round']['code'] ?? null,
                'round_name' => $result['round']['name'] ?? null,
                'outcome' => $result['outcome'] ?? null,
                'completion_type' => $result['completionType'] ?? null,
            ]
        );

        // Players on each side (winner1/2, loser1/2 are UTR ids)
        $members = collect($matchData['participatingMembers'] ?? [])->keyBy('playerId');
        $sides = [
            ['winner1', true, 1], ['winner2', true, 2],
            ['loser1', false, 1], ['loser2', false, 2],
        ];
        $keepPlayerIds = [];
        foreach ($sides as [$key, $won, $position]) {
            $utrId = $result['players'][$key] ?? null;
            if (!$utrId || !$members->has($utrId)) {
                continue;
            }
            $member = $members[$utrId];
            $player = $this->resolvePlayer($member);
            if (!$player) {
                continue;
            }
            $keepPlayerIds[] = $player->id;

            $matchPlayer = $match->matchPlayers()->firstOrNew(['player_id' => $player->id]);
            $matchPlayer->won = $won;
            $matchPlayer->position = $position;
            // Keep the ratings from the first sync as the closest thing to "at match time"
            if (!$matchPlayer->exists) {
                $matchPlayer->utr_singles_rating = $member['utrSingles'] ?? $member['singlesUtr'] ?? null;
                $matchPlayer->utr_doubles_rating = $member['utrDoubles'] ?? $member['doublesUtr'] ?? null;
            }
            $matchPlayer->save();
        }
        $match->matchPlayers()->whereNotIn('player_id', $keepPlayerIds)->delete();

        // Set scores, stored from the winner's side
        $match->sets()->delete();
        foreach ($result['score'] ?? [] as $setNumber => $set) {
            if (!isset($set['winner'], $set['loser'])) {
                continue;
            }
            $match->sets()->create([
                'set_number' => (int) $setNumber,
                'winner_score' => $set['winner'],
                'loser_score' => $set['loser'],
                'winner_tiebreak' => $set['winnerTiebreak'] ?? null,
                'loser_tiebreak' => $set['tiebreak'] ?? null,
            ]);
        }
    }

    /**
     * Find the player by UTR id, else adopt a same-named player with no UTR id, else create one.
     */
    protected function resolvePlayer(array $data, bool $updateRatings = false): ?Player
    {
        $utrId = $data['playerId'] ?? null;
        if (!$utrId) {
            return null;
        }

        $player = $this->players[$utrId] ?? null;

        if (!$player) {
            [$first, $last] = $this->names($data);

            $player = Player::where('utr_id', $utrId)->first();
            if (!$player && $first && $last) {
                $sameName = Player::whereNull('utr_id')
                    ->whereRaw('LOWER(first_name) = ?', [mb_strtolower($first)])
                    ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($last)])
                    ->get();
                $player = $sameName->count() === 1 ? $sameName->first() : null;
            }
            if (!$player) {
                if (!$first && !$last) {
                    return null;
                }
                $player = new Player(['first_name' => $first, 'last_name' => $last]);
            }
            $player->utr_id = $utrId;
        }

        if ($updateRatings || !$player->exists) {
            $singles = $data['singlesUtr'] ?? $data['utrSingles'] ?? null;
            $doubles = $data['doublesUtr'] ?? $data['utrDoubles'] ?? null;
            if ($singles) {
                $player->utr_singles_rating = $singles;
                $player->utr_singles_updated_at = now();
                if (isset($data['ratingProgressSingles'])) {
                    $player->utr_singles_reliable = $data['ratingProgressSingles'] == 100;
                }
            }
            if ($doubles) {
                $player->utr_doubles_rating = $doubles;
                $player->utr_doubles_updated_at = now();
                if (isset($data['ratingProgressDoubles'])) {
                    $player->utr_doubles_reliable = $data['ratingProgressDoubles'] == 100;
                }
            }
        }

        if ($player->isDirty() || !$player->exists) {
            $player->save();
        }

        return $this->players[$utrId] = $player;
    }

    protected function names(array $data): array
    {
        $first = trim($data['firstName'] ?? '');
        $last = trim($data['lastName'] ?? '');
        if ((!$first || !$last) && !empty($data['displayName'])) {
            $parts = preg_split('/\s+/', trim($data['displayName']), 2);
            $first = $first ?: ($parts[0] ?? '');
            $last = $last ?: ($parts[1] ?? '');
        }
        return [$first, $last];
    }
}
