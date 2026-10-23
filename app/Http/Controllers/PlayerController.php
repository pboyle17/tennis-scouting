<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Http\Controllers\Controller;
use App\Jobs\UpdateUtrRatingsJob;
use App\Jobs\FetchMissingUtrIdsJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
      $sortField = $request->get('sort', 'utr_singles_rating');
      $sortDirection = $request->get('direction', 'desc');

      $players = Player::with('teams')->orderBy($sortField, $sortDirection)->get();

      return view('players.index', compact('players', 'sortField', 'sortDirection'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('players.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'utr_id' => 'nullable|integer',
            'utr_rating' => 'nullable|numeric',
            'USTA_rating' => 'nullable|numeric'
        ]);

        Player::create($validated);

        return redirect()->route('players.index')->with('success', 'Player created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Player $player)
    {
        // Load player with teams and their leagues
        $player->load('teams.league');

        // Get all court player records for this player (match results)
        $courtPlayers = \App\Models\CourtPlayer::where('player_id', $player->id)
            ->with([
                'court.tennisMatch.homeTeam',
                'court.tennisMatch.awayTeam',
                'court.tennisMatch.league',
                'court.courtPlayers.player',
                'court.courtSets',
                'team.league'
            ])
            ->get()
            ->sortByDesc(function($courtPlayer) {
                return $courtPlayer->court->tennisMatch->start_time ?? $courtPlayer->court->tennisMatch->created_at;
            });

        // Tournament matches (synced from UTR) for this player
        $tournamentMatchPlayers = \App\Models\TournamentMatchPlayer::where('player_id', $player->id)
            ->with(['match.draw.tournament', 'match.sets', 'match.matchPlayers.player'])
            ->get();

        // League and tournament matches together, newest first, for the match history
        $historyEntries = collect()
            ->concat($courtPlayers->map(fn($cp) => [
                'type' => 'league',
                'item' => $cp,
                'date' => $cp->court->tennisMatch->start_time ?? $cp->court->tennisMatch->created_at,
                'rank' => 0,
            ]))
            ->concat($tournamentMatchPlayers->map(fn($tmp) => [
                'type' => 'tournament',
                'item' => $tmp,
                'date' => $tmp->match->played_on ?? $tmp->match->created_at,
                // Tournament matches only have a date, so later rounds sort first within a day
                'rank' => $tmp->match->roundRank(),
            ]))
            ->sortBy([
                fn($a, $b) => \Carbon\Carbon::parse($b['date'])->timestamp <=> \Carbon\Carbon::parse($a['date'])->timestamp,
                fn($a, $b) => $b['rank'] <=> $a['rank'],
            ])
            ->values();

        $historyTournaments = $tournamentMatchPlayers->map(fn($tmp) => $tmp->match->draw->tournament)->unique('id')->values();
        $tournamentPartners = $tournamentMatchPlayers
            ->filter(fn($tmp) => $tmp->match->draw->team_type === 'doubles')
            ->map(fn($tmp) => $tmp->match->matchPlayers->first(fn($other) => $other->won === $tmp->won && $other->player_id !== $player->id))
            ->filter()
            ->values();

        // Calculate match statistics
        $totalMatches = $courtPlayers->count();
        $wins = $courtPlayers->where('won', true)->count();
        $losses = $courtPlayers->where('won', false)->count();
        $winPercentage = $totalMatches > 0 ? ($wins / $totalMatches) * 100 : 0;

        // Group matches by court type for statistics
        $singlesMatches = $courtPlayers->filter(function($cp) {
            return $cp->court->court_type === 'singles';
        });
        $doublesMatches = $courtPlayers->filter(function($cp) {
            return $cp->court->court_type === 'doubles';
        });

        $singlesWins = $singlesMatches->where('won', true)->count();
        $singlesLosses = $singlesMatches->where('won', false)->count();
        $singlesWinPercentage = $singlesMatches->count() > 0 ? ($singlesWins / $singlesMatches->count()) * 100 : 0;

        $doublesWins = $doublesMatches->where('won', true)->count();
        $doublesLosses = $doublesMatches->where('won', false)->count();
        $doublesWinPercentage = $doublesMatches->count() > 0 ? ($doublesWins / $doublesMatches->count()) * 100 : 0;

        $stats = [
            'total' => [
                'matches' => $totalMatches,
                'wins' => $wins,
                'losses' => $losses,
                'win_percentage' => $winPercentage
            ],
            'singles' => [
                'matches' => $singlesMatches->count(),
                'wins' => $singlesWins,
                'losses' => $singlesLosses,
                'win_percentage' => $singlesWinPercentage
            ],
            'doubles' => [
                'matches' => $doublesMatches->count(),
                'wins' => $doublesWins,
                'losses' => $doublesLosses,
                'win_percentage' => $doublesWinPercentage
            ]
        ];

        // Best win = highest-rated opponent beaten. Worst loss = lowest-rated
        // opponent lost to. League and tournament matches both count, ranked
        // by the opponent's current UTR rating.
        $resultCandidates = $this->extremeMatchCandidates($courtPlayers, $tournamentMatchPlayers, $player->id);
        $bestWins = $this->extremeMatchesByCourtType($resultCandidates, won: true, keepHigher: true);
        $worstLosses = $this->extremeMatchesByCourtType($resultCandidates, won: false, keepHigher: false);

        $ratingSnapshots = \App\Models\PlayerRatingSnapshot::where('player_id', $player->id)
            ->orderBy('snapshot_date')
            ->get(['snapshot_date', 'utr_singles_rating', 'utr_doubles_rating', 'usta_dynamic_rating']);

        // Upcoming / recent matches for any team this player is on. Each shows the weekend
        // of the nearest match in that direction, however far away it is.
        $teamIds = $player->teams->pluck('id');
        $teamMatches = fn() => \App\Models\TennisMatch::where(function ($query) use ($teamIds) {
                $query->whereIn('home_team_id', $teamIds)
                    ->orWhereIn('away_team_id', $teamIds);
            });
        $teamMatchEntry = function ($match) use ($teamIds, $courtPlayers) {
            $isHomeTeam = $teamIds->contains($match->home_team_id);
            return [
                'match' => $match,
                'is_home' => $isHomeTeam,
                'my_team' => $isHomeTeam ? $match->homeTeam : $match->awayTeam,
                'opponent' => $isHomeTeam ? $match->awayTeam : $match->homeTeam,
                'court_player' => $courtPlayers->first(fn($cp) => $cp->court->tennis_match_id === $match->id),
            ];
        };

        // Upcoming: everything from now through the end of the next match's weekend
        $upcomingMatches = collect();
        $upcomingLabel = null;
        if ($nextMatchTime = $teamMatches()->where('start_time', '>=', now())->min('start_time')) {
            [$weekendStart, $weekendEnd] = $this->weekendOf(\Carbon\Carbon::parse($nextMatchTime));
            $upcomingMatches = $teamMatches()
                ->whereBetween('start_time', [now(), $weekendEnd])
                ->with(['homeTeam', 'awayTeam', 'league'])
                ->orderBy('start_time')
                ->get()
                ->map($teamMatchEntry);
            $upcomingLabel = $this->weekendLabel($weekendStart);
        }

        // Recent: the weekend of the most recent past match, up to now
        $recentMatches = collect();
        $recentLabel = null;
        if ($lastMatchTime = $teamMatches()->where('start_time', '<', now())->max('start_time')) {
            $lastMatchTime = \Carbon\Carbon::parse($lastMatchTime);
            [$weekendStart, $weekendEnd] = $this->weekendOf($lastMatchTime);
            $recentMatches = $teamMatches()
                ->whereBetween('start_time', [$weekendStart->copy()->min($lastMatchTime->copy()->startOfDay()), now()->min($weekendEnd)])
                ->with(['homeTeam', 'awayTeam', 'league', 'courts.courtSets'])
                ->orderBy('start_time')
                ->get()
                ->map($teamMatchEntry);
            $recentLabel = $this->weekendLabel($weekendStart);
        }

        $matchRatingPoints = $courtPlayers
            ->filter(fn($cp) => $cp->court->tennisMatch->start_time !== null
                && ($cp->utr_singles_rating !== null || $cp->utr_doubles_rating !== null || $cp->usta_dynamic_rating !== null))
            ->map(fn($cp) => [
                'date'                => \Carbon\Carbon::parse($cp->court->tennisMatch->start_time)->toDateString(),
                'court_type'          => $cp->court->court_type,
                'utr_singles_rating'  => $cp->utr_singles_rating,
                'utr_doubles_rating'  => $cp->utr_doubles_rating,
                'usta_dynamic_rating' => $cp->usta_dynamic_rating,
                'opponents'           => $cp->court->courtPlayers
                    ->where('team_id', '!=', $cp->team_id)
                    ->map(fn($opp) => [
                        'name'               => $opp->player->first_name . ' ' . $opp->player->last_name,
                        'utr_singles_rating' => $opp->utr_singles_rating,
                        'utr_doubles_rating' => $opp->utr_doubles_rating,
                    ])
                    ->values()
                    ->all(),
            ])
            ->sortBy('date')
            ->unique('date')
            ->values();

        return view('players.show', compact('player', 'courtPlayers', 'stats', 'ratingSnapshots', 'matchRatingPoints', 'upcomingMatches', 'upcomingLabel', 'recentMatches', 'recentLabel', 'historyEntries', 'historyTournaments', 'tournamentPartners', 'bestWins', 'worstLosses'));
    }

    /**
     * League and tournament results in one shape for picking best wins / worst losses.
     * Uses the opponent's current UTR rating (singles, or the pair's average for doubles)
     * rather than their rating at match time, since a rating backed by more matches is
     * more accurate than an old snapshot.
     */
    private function extremeMatchCandidates($courtPlayers, $tournamentMatchPlayers, int $playerId): array
    {
        $opponentRating = function ($opponents, string $courtType) {
            if ($courtType === 'singles') {
                return $opponents->first()?->utr_singles_rating;
            }
            $ratings = $opponents->pluck('utr_doubles_rating')->filter()->values();
            return $ratings->isNotEmpty() ? $ratings->avg() : null;
        };

        $candidates = [];

        foreach ($courtPlayers as $cp) {
            $court = $cp->court;
            $match = $court->tennisMatch;
            $opponents = $court->courtPlayers->where('team_id', '!=', $cp->team_id)->pluck('player')->filter()->values();
            if ($opponents->isEmpty()) {
                continue;
            }
            $isHome = $cp->team_id === $match->home_team_id;
            $candidates[] = [
                'court_type' => $court->court_type,
                'won' => (bool) $cp->won,
                'partner' => $court->courtPlayers->first(fn($o) => $o->team_id === $cp->team_id && $o->player_id !== $playerId)?->player,
                'opponents' => $opponents,
                'opponent_rating' => $opponentRating($opponents, $court->court_type),
                'date' => $match->start_time,
                'url' => route('tennis-matches.show', $match->id),
                'label' => null,
                'walkover' => false,
                'sets' => $court->courtSets->sortBy('set_number')
                    ->map(fn($set) => $isHome ? [$set->home_score, $set->away_score, null] : [$set->away_score, $set->home_score, null])
                    ->values()->all(),
            ];
        }

        foreach ($tournamentMatchPlayers as $tmp) {
            $match = $tmp->match;
            $courtType = $match->draw->team_type === 'doubles' ? 'doubles' : 'singles';
            $opponents = $match->matchPlayers->where('won', '!==', $tmp->won)->sortBy('position')->pluck('player')->filter()->values();
            // Walkovers say nothing about level, so skip them
            if ($opponents->isEmpty() || $match->isWalkover()) {
                continue;
            }
            $candidates[] = [
                'court_type' => $courtType,
                'won' => (bool) $tmp->won,
                'partner' => $match->matchPlayers->first(fn($o) => $o->won === $tmp->won && $o->player_id !== $playerId)?->player,
                'opponents' => $opponents,
                'opponent_rating' => $opponentRating($opponents, $courtType),
                'date' => $match->played_on,
                'url' => route('tournaments.show', $match->draw->tournament_id),
                'label' => '🏆 ' . $match->draw->tournament->name . ' · ' . ($match->round_name ?? 'Round robin'),
                'walkover' => false,
                'sets' => $match->sets
                    ->map(fn($set) => $tmp->won
                        ? [$set->winner_score, $set->loser_score, $set->loser_tiebreak]
                        : [$set->loser_score, $set->winner_score, $set->loser_tiebreak])
                    ->values()->all(),
            ];
        }

        return $candidates;
    }

    /**
     * For each court type, find the win/loss against the most extreme-rated
     * opponent (highest for a win, lowest for a loss).
     */
    private function extremeMatchesByCourtType(array $candidates, bool $won, bool $keepHigher): array
    {
        $result = ['singles' => null, 'doubles' => null];

        foreach ($candidates as $candidate) {
            if ($candidate['won'] !== $won || $candidate['opponent_rating'] === null) {
                continue;
            }

            $existing = $result[$candidate['court_type']] ?? null;
            $isMoreExtreme = !$existing || ($keepHigher
                ? $candidate['opponent_rating'] > $existing['opponent_rating']
                : $candidate['opponent_rating'] < $existing['opponent_rating']);

            if ($isMoreExtreme) {
                $result[$candidate['court_type']] = $candidate;
            }
        }

        return $result;
    }

    public function headToHead(Request $request, Player $player)
    {
        $allPlayers = Player::orderBy('last_name')->orderBy('first_name')
            ->where('id', '!=', $player->id)
            ->get(['id', 'first_name', 'last_name', 'utr_singles_rating', 'utr_doubles_rating', 'USTA_dynamic_rating']);

        $opponent      = null;
        $directMatches = collect();
        $commonOpponents = collect();

        if ($opponentId = $request->get('opponent')) {
            $opponent = Player::findOrFail($opponentId);
            $aId = $player->id;
            $bId = $opponent->id;

            // --- League appearances: court_id => CourtPlayer (team_id) ---
            $aCourts = \App\Models\CourtPlayer::where('player_id', $aId)->get(['court_id', 'team_id'])->keyBy('court_id');
            $bCourts = \App\Models\CourtPlayer::where('player_id', $bId)->get(['court_id', 'team_id'])->keyBy('court_id');

            // --- Tournament appearances: tournament_match_id => TournamentMatchPlayer (won) ---
            $aTourn = \App\Models\TournamentMatchPlayer::where('player_id', $aId)->get(['tournament_match_id', 'won'])->keyBy('tournament_match_id');
            $bTourn = \App\Models\TournamentMatchPlayer::where('player_id', $bId)->get(['tournament_match_id', 'won'])->keyBy('tournament_match_id');

            // Direct meetings: same court/match, opposite sides
            $sharedCourtIds = $aCourts->keys()->intersect($bCourts->keys())
                ->filter(fn($id) => $aCourts[$id]->team_id !== $bCourts[$id]->team_id)
                ->values();
            $sharedTournIds = $aTourn->keys()->intersect($bTourn->keys())
                ->filter(fn($id) => $aTourn[$id]->won !== $bTourn[$id]->won)
                ->values();

            $directCourts = \App\Models\Court::whereIn('id', $sharedCourtIds)
                ->with(['tennisMatch', 'courtPlayers', 'courtSets'])
                ->get()
                ->filter(fn($c) => $c->home_score !== null);
            $directTourn = \App\Models\TournamentMatch::whereIn('id', $sharedTournIds)
                ->with(['draw.tournament', 'sets', 'matchPlayers'])
                ->get();

            $directMatches = $directCourts->map(fn($c) => $this->h2hLeagueRow($c, $aId, $bId))
                ->concat($directTourn->map(fn($m) => $this->h2hTournamentRow($m, $aId, $bId)))
                ->sortByDesc(fn($row) => $row['date']?->timestamp ?? 0)
                ->values();

            // Opponents each player has faced, in leagues and tournaments
            $leagueOpponents = function ($courts, array $exclude) {
                return \App\Models\CourtPlayer::whereIn('court_id', $courts->keys())
                    ->whereNotIn('player_id', $exclude)
                    ->get(['court_id', 'player_id', 'team_id'])
                    ->filter(fn($cp) => $cp->team_id !== $courts[$cp->court_id]->team_id)
                    ->pluck('player_id');
            };
            $tournamentOpponents = function ($tourn, array $exclude) {
                return \App\Models\TournamentMatchPlayer::whereIn('tournament_match_id', $tourn->keys())
                    ->whereNotIn('player_id', $exclude)
                    ->get(['tournament_match_id', 'player_id', 'won'])
                    ->filter(fn($mp) => $mp->won !== $tourn[$mp->tournament_match_id]->won)
                    ->pluck('player_id');
            };
            $aOpponentIds = $leagueOpponents($aCourts, [$aId, $bId])->concat($tournamentOpponents($aTourn, [$aId, $bId]))->unique();
            $bOpponentIds = $leagueOpponents($bCourts, [$aId, $bId])->concat($tournamentOpponents($bTourn, [$aId, $bId]))->unique();

            $commonIds = $aOpponentIds->intersect($bOpponentIds)->values();

            if ($commonIds->isNotEmpty()) {
                // League courts where A or B faced a common opponent
                $leagueVs = []; // [subjectId][opponentId] => [courtId, ...]
                foreach (\App\Models\CourtPlayer::whereIn('player_id', $commonIds)->get(['court_id', 'player_id', 'team_id']) as $cp) {
                    foreach ([$aId => $aCourts, $bId => $bCourts] as $subjectId => $courts) {
                        if ($courts->has($cp->court_id) && $courts[$cp->court_id]->team_id !== $cp->team_id) {
                            $leagueVs[$subjectId][$cp->player_id][] = $cp->court_id;
                        }
                    }
                }
                // Tournament matches where A or B faced a common opponent
                $tournVs = []; // [subjectId][opponentId] => [matchId, ...]
                foreach (\App\Models\TournamentMatchPlayer::whereIn('player_id', $commonIds)->get(['tournament_match_id', 'player_id', 'won']) as $mp) {
                    foreach ([$aId => $aTourn, $bId => $bTourn] as $subjectId => $tourn) {
                        if ($tourn->has($mp->tournament_match_id) && $tourn[$mp->tournament_match_id]->won !== $mp->won) {
                            $tournVs[$subjectId][$mp->player_id][] = $mp->tournament_match_id;
                        }
                    }
                }

                $courtIds = collect($leagueVs)->flatten()->unique();
                $commonCourts = \App\Models\Court::whereIn('id', $courtIds)
                    ->with(['tennisMatch', 'courtPlayers', 'courtSets'])
                    ->get()
                    ->filter(fn($c) => $c->home_score !== null)
                    ->keyBy('id');
                $commonTourn = \App\Models\TournamentMatch::whereIn('id', collect($tournVs)->flatten()->unique())
                    ->with(['draw.tournament', 'sets', 'matchPlayers'])
                    ->get()
                    ->keyBy('id');

                $matchesVs = function (int $subjectId, int $oppId) use ($leagueVs, $tournVs, $commonCourts, $commonTourn) {
                    return collect($leagueVs[$subjectId][$oppId] ?? [])->unique()
                        ->map(fn($id) => $commonCourts->get($id))->filter()
                        ->map(fn($c) => $this->h2hLeagueRow($c, $subjectId))
                        ->concat(collect($tournVs[$subjectId][$oppId] ?? [])->unique()
                            ->map(fn($id) => $commonTourn->get($id))->filter()
                            ->map(fn($m) => $this->h2hTournamentRow($m, $subjectId)))
                        ->sortByDesc(fn($row) => $row['date']?->timestamp ?? 0)
                        ->values();
                };

                $commonOpponents = Player::whereIn('id', $commonIds)->orderBy('last_name')->orderBy('first_name')
                    ->get(['id', 'first_name', 'last_name', 'utr_singles_rating'])
                    ->map(function ($opp) use ($aId, $bId, $matchesVs) {
                        $aMatches = $matchesVs($aId, $opp->id);
                        $bMatches = $matchesVs($bId, $opp->id);
                        $aWins = $aMatches->where('won', true)->count();
                        $bWins = $bMatches->where('won', true)->count();
                        return [
                            'player'          => $opp,
                            'player_record'   => ['wins' => $aWins, 'losses' => $aMatches->count() - $aWins],
                            'opponent_record' => ['wins' => $bWins, 'losses' => $bMatches->count() - $bWins],
                            'player_matches'  => $aMatches,
                            'opponent_matches'=> $bMatches,
                        ];
                    });
            }
        }

        return view('players.head-to-head', compact('player', 'opponent', 'allPlayers', 'directMatches', 'commonOpponents'));
    }

    /**
     * A league court as a head-to-head row, from $subjectId's side (and $otherId's, for direct meetings).
     * Sets are [subject games, other games, tiebreak].
     */
    private function h2hLeagueRow(\App\Models\Court $court, int $subjectId, ?int $otherId = null): array
    {
        $match = $court->tennisMatch;
        $subject = $court->courtPlayers->firstWhere('player_id', $subjectId);
        $other = $otherId ? $court->courtPlayers->firstWhere('player_id', $otherId) : null;
        $isHome = $subject && $subject->team_id === $match->home_team_id;
        $ratingField = $court->court_type === 'doubles' ? 'utr_doubles_rating' : 'utr_singles_rating';

        return [
            'source'      => 'league',
            'date'        => $match->start_time ? \Carbon\Carbon::parse($match->start_time) : null,
            'url'         => route('tennis-matches.show', $match->id),
            'label'       => null,
            'walkover'    => false,
            'sets'        => $court->courtSets->sortBy('set_number')
                ->map(fn($set) => $isHome ? [$set->home_score, $set->away_score, null] : [$set->away_score, $set->home_score, null])
                ->values()->all(),
            'won'         => (bool) $subject?->won,
            'rating'      => $subject?->$ratingField,
            'other_won'   => (bool) $other?->won,
            'other_rating'=> $other?->$ratingField,
        ];
    }

    /**
     * A tournament match as a head-to-head row, in the same shape as h2hLeagueRow().
     */
    private function h2hTournamentRow(\App\Models\TournamentMatch $match, int $subjectId, ?int $otherId = null): array
    {
        $subject = $match->matchPlayers->firstWhere('player_id', $subjectId);
        $other = $otherId ? $match->matchPlayers->firstWhere('player_id', $otherId) : null;
        $won = (bool) $subject?->won;
        $ratingField = $match->draw->team_type === 'doubles' ? 'utr_doubles_rating' : 'utr_singles_rating';

        return [
            'source'      => 'tournament',
            'date'        => $match->played_on,
            'url'         => route('tournaments.show', $match->draw->tournament_id),
            'label'       => '🏆 ' . $match->draw->tournament->name . ' · ' . ($match->round_name ?? 'Round robin'),
            'walkover'    => $match->isWalkover(),
            'sets'        => $match->sets
                ->map(fn($set) => $won
                    ? [$set->winner_score, $set->loser_score, $set->loser_tiebreak]
                    : [$set->loser_score, $set->winner_score, $set->loser_tiebreak])
                ->values()->all(),
            'won'         => $won,
            'rating'      => $subject?->$ratingField,
            'other_won'   => (bool) $other?->won,
            'other_rating'=> $other?->$ratingField,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Player $player)
    {
        $player->load('teams');
        $returnUrl = $request->query('return_url');
        return view('players.edit', compact('player', 'returnUrl'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Player $player)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'utr_id' => 'nullable|integer',
            'tennis_record_link' => 'nullable|string|max:500',
            'utr_singles_rating' => 'nullable|numeric',
            'utr_doubles_rating' => 'nullable|numeric',
            'USTA_rating' => 'nullable|numeric',
            'USTA_dynamic_rating' => 'nullable|numeric',
            'tennis_number_singles_rating' => 'nullable|numeric',
            'tennis_number_doubles_rating' => 'nullable|numeric',
            'tennis_number_link' => 'nullable|url|max:500',
        ]);

        // Check if UTR ratings changed and set updated timestamps
        if (isset($validated['utr_singles_rating']) && $validated['utr_singles_rating'] != $player->utr_singles_rating) {
            $validated['utr_singles_updated_at'] = now();
        }
        if (isset($validated['utr_doubles_rating']) && $validated['utr_doubles_rating'] != $player->utr_doubles_rating) {
            $validated['utr_doubles_updated_at'] = now();
        }
        if (isset($validated['USTA_rating']) && $validated['USTA_rating'] != $player->USTA_rating) {
            $validated['usta_rating_updated_at'] = now();
        }

        $player->update($validated);

        // Check if there's a return URL, otherwise go to players index
        $returnUrl = $request->input('return_url');

        if ($returnUrl) {
            return redirect($returnUrl)->with('success', 'Player updated successfully!');
        }

        return redirect()->route('players.index')->with('success', 'Player updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Player $player)
    {
        $playerName = $player->first_name . ' ' . $player->last_name;

        // Detach from all teams
        $player->teams()->detach();

        // Delete the player
        $player->delete();

        return redirect()->route('players.index')->with('success', "Player '{$playerName}' has been deleted successfully.");
    }

    public function updateUtrRatings()
    {
      $jobKey = 'utr_update_' . uniqid();
      UpdateUtrRatingsJob::dispatch([], $jobKey);

      return redirect()->route('players.index')->with([
          'status' => '✅ UTR update job has been dispatched!',
          'utr_job_key' => $jobKey
      ]);
    }

    public function updateUtr(Player $player)
    {
      UpdateUtrRatingsJob::dispatchSync([$player->utr_id]);

      return back();
    }

    public function fetchMissingUtrIds()
    {
        // Check if a job is already running
        if (Cache::has('utr_search_running')) {
            $message = '⏳ UTR ID search is already in progress. Please wait for it to complete.';
            return redirect()->route('players.index')->with('error', $message);
        }

        // Mark job as running
        Cache::put('utr_search_running', true, 300); // 5 minutes

        $job = FetchMissingUtrIdsJob::dispatch();

        return redirect()->route('players.index')->with([
            'status' => '🔍 UTR ID search job has been dispatched!',
            'search_job_id' => $job->getJobId()
        ]);
    }

    public function getUtrSearchProgress(Request $request)
    {
        $jobId = $request->get('job_id');
        if (!$jobId) {
            return response()->json(['error' => 'Job ID required'], 400);
        }

        $progress = Cache::get("utr_search_progress_{$jobId}");
        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($progress);
    }

    public function getUtrUpdateProgress(Request $request)
    {
        $jobKey = $request->get('job_key');
        if (!$jobKey) {
            return response()->json(['error' => 'Job key required'], 400);
        }

        $progress = Cache::get($jobKey);
        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($progress);
    }

    public function searchUtrId(Request $request, Player $player)
    {
        try {
            $utrService = app(\App\Services\UtrService::class);
            $playerName = $player->first_name . ' ' . $player->last_name;

            Log::info("Searching UTR ID for player: {$playerName}", [
                'player_id' => $player->id,
                'first_name' => $player->first_name,
                'last_name' => $player->last_name
            ]);

            $searchResults = $utrService->searchPlayers($playerName, 10);

            // Handle nested structure
            $hits = $searchResults['players']['hits'] ?? $searchResults['hits'] ?? [];

            Log::info("UTR Search Results for {$playerName}", [
                'player_id' => $player->id,
                'total_hits' => count($hits),
                'results' => $searchResults
            ]);

            // Log each result individually for easier reading
            if (count($hits) > 0) {
                foreach ($hits as $index => $hit) {
                    $source = $hit['source'] ?? [];
                    Log::info("UTR Search Result #{$index} for {$playerName}", [
                        'player_id' => $player->id,
                        'utr_id' => $source['id'] ?? 'N/A',
                        'name' => ($source['firstName'] ?? '') . ' ' . ($source['lastName'] ?? ''),
                        'location' => $source['location']['display'] ?? 'N/A',
                        'singles_utr' => $source['singlesUtr'] ?? 0,
                        'doubles_utr' => $source['doublesUtr'] ?? 0,
                        'gender' => $source['gender'] ?? 'N/A',
                        'full_data' => $source
                    ]);
                }
            }

            $returnUrl = $request->query('return_url');
            $redirectUrl = $returnUrl ?: route('players.edit', $player->id);

            return redirect($redirectUrl)->with([
                'status' => "Found " . count($hits) . " UTR profile(s) for {$playerName}",
                'utr_search_results' => $searchResults
            ]);

        } catch (\Exception $e) {
            Log::error("UTR search failed for player {$player->id}: " . $e->getMessage());

            $returnUrl = $request->query('return_url');
            $redirectUrl = $returnUrl ?: route('players.edit', $player->id);

            return redirect($redirectUrl)->with('error', 'Failed to search for UTR ID: ' . $e->getMessage());
        }
    }

    public function syncTrProfile(Request $request, Player $player)
    {
        try {
            // Check if player has a Tennis Record link
            if (!$player->tennis_record_link) {
                $returnUrl = $request->query('return_url');
                $redirectUrl = route('players.edit', $player->id);
                if ($returnUrl) {
                    $redirectUrl .= '?return_url=' . urlencode($returnUrl);
                }
                return redirect($redirectUrl)->with('error', 'Player does not have a Tennis Record link.');
            }

            Log::info("Syncing Tennis Record profile for player: {$player->first_name} {$player->last_name}", [
                'player_id' => $player->id,
                'tennis_record_link' => $player->tennis_record_link
            ]);

            // Fetch the Tennis Record page
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->connectTimeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])
                ->get($player->tennis_record_link);

            if (!$response->successful()) {
                Log::warning("Failed to fetch Tennis Record page for player {$player->id}", [
                    'player_id' => $player->id,
                    'status' => $response->status(),
                    'link' => $player->tennis_record_link
                ]);

                $returnUrl = $request->query('return_url');
                $redirectUrl = route('players.edit', $player->id);
                if ($returnUrl) {
                    $redirectUrl .= '?return_url=' . urlencode($returnUrl);
                }
                return redirect($redirectUrl)->with('error', 'Failed to fetch Tennis Record page.');
            }

            $html = $response->body();

            // Try multiple patterns to find USTA rating
            $patterns = [
                // Pattern for rating in bold span (Tennis Record format) - most common
                '/<span[^>]*font-weight:\s*bold[^>]*>([3-5]\.\d+)\s+([SCMTA])<\/span>/i',
                '/Rating:\s*<[^>]+>([3-5]\.\d+)([SCMTA])<\/[^>]+>/i',
                '/USTA\s+Rating:\s*([3-5]\.\d+)([SCMTA])/i',
                '/<td[^>]*>Rating<\/td>\s*<td[^>]*>([3-5]\.\d+)([SCMTA])<\/td>/i',
                // Generic pattern for rating directly in any table cell (no label)
                '/<td[^>]*>\s*([3-5]\.\d+)\s*([SCMTA])\s*<\/td>/i',
            ];

            $ratingFound = false;
            $rating = null;
            $ratingType = null;

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $rating = floatval($matches[1]);
                    $ratingType = strtoupper($matches[2]);
                    $ratingFound = true;
                    break;
                }
            }

            if ($ratingFound && $rating >= 3.0 && $rating <= 5.0 && in_array($ratingType, ['S', 'C', 'A', 'M', 'T'])) {
                $player->USTA_rating = $rating;
                $player->usta_rating_type = $ratingType;
                $player->usta_rating_updated_at = now();
                $player->save();

                Log::info("Successfully synced Tennis Record profile for player {$player->id}", [
                    'player_id' => $player->id,
                    'player_name' => $player->first_name . ' ' . $player->last_name,
                    'usta_rating' => $rating,
                    'usta_rating_type' => $ratingType
                ]);

                $ratingTypeNames = [
                    'S' => 'Self-rated',
                    'C' => 'Computer rated',
                    'A' => 'Appeal',
                    'M' => 'Medical',
                    'T' => 'Tournament'
                ];

                $returnUrl = $request->query('return_url');
                $redirectUrl = route('players.edit', $player->id);
                if ($returnUrl) {
                    $redirectUrl .= '?return_url=' . urlencode($returnUrl);
                }

                return redirect($redirectUrl)->with('success', "Successfully synced USTA rating: {$rating} ({$ratingTypeNames[$ratingType]})");
            } else {
                Log::warning("Could not find valid USTA rating on Tennis Record page for player {$player->id}", [
                    'player_id' => $player->id,
                    'player_name' => $player->first_name . ' ' . $player->last_name,
                    'link' => $player->tennis_record_link
                ]);

                $returnUrl = $request->query('return_url');
                $redirectUrl = route('players.edit', $player->id);
                if ($returnUrl) {
                    $redirectUrl .= '?return_url=' . urlencode($returnUrl);
                }
                return redirect($redirectUrl)->with('error', 'Could not find valid USTA rating on Tennis Record page.');
            }

        } catch (\Exception $e) {
            Log::error("Error syncing Tennis Record profile for player {$player->id}: " . $e->getMessage(), [
                'player_id' => $player->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $returnUrl = $request->query('return_url');
            $redirectUrl = route('players.edit', $player->id);
            if ($returnUrl) {
                $redirectUrl .= '?return_url=' . urlencode($returnUrl);
            }
            return redirect($redirectUrl)->with('error', 'Failed to sync Tennis Record profile: ' . $e->getMessage());
        }
    }

    /**
     * Saturday 00:00 and Sunday 23:59 of the weekend in the week (Mon-Sun) containing $time.
     */
    private function weekendOf(\Carbon\Carbon $time): array
    {
        $end = $time->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
        return [$end->copy()->subDay()->startOfDay(), $end];
    }

    /**
     * e.g. "Last weekend · Oct 3–Oct 4", or just "Sep 19–Sep 20" when further away.
     */
    private function weekendLabel(\Carbon\Carbon $saturday): string
    {
        $thisSaturday = now()->startOfWeek()->addDays(5)->startOfDay();
        $prefix = match ((int) round($thisSaturday->diffInDays($saturday, false))) {
            0 => 'This weekend · ',
            -7 => 'Last weekend · ',
            7 => 'Next weekend · ',
            default => '',
        };
        $sunday = $saturday->copy()->addDay();
        $year = $sunday->year !== now()->year ? ', ' . $sunday->year : '';
        return $prefix . $saturday->format('M j') . '–' . $sunday->format('M j') . $year;
    }
}
