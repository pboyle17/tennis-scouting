<?php

namespace App\Http\Controllers;

use App\Models\TennisMatch;
use App\Models\Team;
use Illuminate\Http\Request;

class TennisMatchController extends Controller
{
    /**
     * Display the specified match.
     */
    public function show(TennisMatch $match)
    {
        $match->load([
            'homeTeam',
            'awayTeam',
            'league',
            'courts.courtPlayers.player',
            'courts.courtSets'
        ]);

        // Calculate court stats for both teams
        $homeCourtStats = $this->calculateTeamCourtStats($match->homeTeam);
        $awayCourtStats = $this->calculateTeamCourtStats($match->awayTeam);

        return view('tennis-matches.show', compact('match', 'homeCourtStats', 'awayCourtStats'));
    }

    /**
     * Show the form for editing the specified match.
     */
    public function edit(TennisMatch $match)
    {
        $match->load(['homeTeam', 'awayTeam', 'league']);

        // Get all teams in the league for the dropdowns
        $teams = $match->league->teams;

        return view('tennis-matches.edit', compact('match', 'teams'));
    }

    /**
     * Update the specified match in storage.
     */
    public function update(Request $request, TennisMatch $match)
    {
        $validated = $request->validate([
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id|different:home_team_id',
            'start_time' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'home_score' => 'nullable|integer|min:0',
            'away_score' => 'nullable|integer|min:0',
        ]);

        $match->update($validated);

        // Redirect back to the team page
        return redirect()->route('teams.show', $match->homeTeam->id)
            ->with('success', 'Match updated successfully.');
    }

    /**
     * Remove the specified match from storage.
     */
    public function destroy(TennisMatch $match)
    {
        $teamId = $match->home_team_id;
        $match->delete();

        return redirect()->route('teams.show', $teamId)
            ->with('success', 'Match deleted successfully.');
    }

    /**
     * Update the match score.
     */
    public function updateScore(Request $request, TennisMatch $match)
    {
        $validated = $request->validate([
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
        ]);

        $match->update($validated);

        // Remove this conflict from cache
        if ($match->league) {
            $conflictsKey = "score_conflicts_league_{$match->league_id}";
            $conflicts = \Illuminate\Support\Facades\Cache::get($conflictsKey, []);

            // Remove the conflict for this match
            $conflicts = array_filter($conflicts, function($conflict) use ($match) {
                return $conflict['match_id'] != $match->id;
            });

            // Update cache
            if (count($conflicts) > 0) {
                \Illuminate\Support\Facades\Cache::put($conflictsKey, array_values($conflicts), 3600);
            } else {
                \Illuminate\Support\Facades\Cache::forget($conflictsKey);
            }
        }

        return redirect()->back()->with('success', 'Match score updated successfully.');
    }

    /**
     * Sync match details from Tennis Record
     */
    public function syncFromTennisRecord(TennisMatch $match)
    {
        if (!$match->tennis_record_match_link) {
            return redirect()->back()->with('error', 'This match does not have a Tennis Record link.');
        }

        try {
            \App\Jobs\SyncMatchFromTennisRecordJob::dispatch($match);

            return redirect()->back()->with('status', '🎾 Syncing match details from Tennis Record... This may take a moment.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to dispatch match sync job: {$e->getMessage()}", [
                'match_id' => $match->id,
                'error' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to start sync job: ' . $e->getMessage());
        }
    }

    /**
     * Calculate court statistics for a specific team
     */
    protected function calculateTeamCourtStats(Team $team)
    {
        // Get all match IDs for this team
        $matchIds = \App\Models\TennisMatch::where(function($query) use ($team) {
            $query->where('home_team_id', $team->id)
                  ->orWhere('away_team_id', $team->id);
        })->pluck('id');

        // Get all courts for these matches
        $courts = \App\Models\Court::whereIn('tennis_match_id', $matchIds)
            ->with('courtPlayers.player')
            ->get();

        $stats = [];

        // Group courts by type and number
        $courtGroups = $courts->groupBy(function($court) {
            return $court->court_type . '_' . $court->court_number;
        });

        foreach ($courtGroups as $key => $courtsInGroup) {
            list($type, $number) = explode('_', $key);

            // Get all court players for this court position (only for this team)
            $allCourtPlayers = $courtsInGroup->flatMap(function($court) use ($team) {
                return $court->courtPlayers->where('team_id', $team->id);
            });

            // Calculate averages
            $avgUtrSingles = null;
            $avgUtrDoubles = null;
            $avgUstaDynamic = null;

            if ($type === 'singles') {
                $avgUtrSingles = $allCourtPlayers->whereNotNull('utr_singles_rating')->avg('utr_singles_rating');
            } else {
                $avgUtrDoubles = $allCourtPlayers->whereNotNull('utr_doubles_rating')->avg('utr_doubles_rating');
            }

            $avgUstaDynamic = $allCourtPlayers->whereNotNull('usta_dynamic_rating')->avg('usta_dynamic_rating');

            // Calculate player-level stats
            $playerStats = [];

            if ($type === 'singles') {
                // For singles, show individual players
                $playerGroups = $allCourtPlayers->groupBy('player_id');

                foreach ($playerGroups as $playerId => $playerCourtAppearances) {
                    $player = $playerCourtAppearances->first()->player;
                    if (!$player) continue;

                    $wins = $playerCourtAppearances->where('won', true)->count();
                    $losses = $playerCourtAppearances->where('won', false)->count();
                    $total = $wins + $losses;

                    $playerAvgUtr = $playerCourtAppearances->whereNotNull('utr_singles_rating')->avg('utr_singles_rating');
                    $playerAvgUsta = $playerCourtAppearances->whereNotNull('usta_dynamic_rating')->avg('usta_dynamic_rating');

                    // Calculate average opponent ratings
                    $opponentUtrRatings = [];
                    $opponentUstaRatings = [];

                    foreach ($playerCourtAppearances as $courtPlayer) {
                        $court = $courtPlayer->court;
                        if (!$court) continue;

                        $opponentPlayers = $court->courtPlayers->where('team_id', '!=', $team->id);
                        foreach ($opponentPlayers as $opponent) {
                            if ($opponent->utr_singles_rating) {
                                $opponentUtrRatings[] = $opponent->utr_singles_rating;
                            }
                            if ($opponent->usta_dynamic_rating) {
                                $opponentUstaRatings[] = $opponent->usta_dynamic_rating;
                            }
                        }
                    }

                    $avgOpponentUtr = !empty($opponentUtrRatings) ? array_sum($opponentUtrRatings) / count($opponentUtrRatings) : null;
                    $avgOpponentUsta = !empty($opponentUstaRatings) ? array_sum($opponentUstaRatings) / count($opponentUstaRatings) : null;

                    $playerStats[] = [
                        'player_id' => $playerId,
                        'player_name' => $player->first_name . ' ' . $player->last_name,
                        'wins' => $wins,
                        'losses' => $losses,
                        'total' => $total,
                        'win_percentage' => $total > 0 ? ($wins / $total) * 100 : 0,
                        'avg_utr' => $playerAvgUtr,
                        'avg_usta' => $playerAvgUsta,
                        'avg_opponent_utr' => $avgOpponentUtr,
                        'avg_opponent_usta' => $avgOpponentUsta,
                        'is_team' => false,
                    ];
                }
            } else {
                // For doubles, group by court to find teams (pairs)
                $doublesTeams = [];

                foreach ($courtsInGroup as $court) {
                    $teamPlayers = $court->courtPlayers->where('team_id', $team->id)->sortBy('player_id');
                    if ($teamPlayers->count() < 2) continue;

                    // Create a unique team identifier based on sorted player IDs
                    $playerIds = $teamPlayers->pluck('player_id')->sort()->values()->toArray();
                    $teamKey = implode('_', $playerIds);

                    if (!isset($doublesTeams[$teamKey])) {
                        $doublesTeams[$teamKey] = [
                            'player_ids' => $playerIds,
                            'players' => $teamPlayers->pluck('player')->all(),
                            'appearances' => [],
                        ];
                    }

                    $doublesTeams[$teamKey]['appearances'][] = [
                        'court' => $court,
                        'won' => $teamPlayers->first()->won,
                        'team_players' => $teamPlayers,
                    ];
                }

                foreach ($doublesTeams as $teamKey => $teamData) {
                    $players = collect($teamData['players']);
                    $appearances = $teamData['appearances'];

                    $wins = collect($appearances)->where('won', true)->count();
                    $losses = collect($appearances)->where('won', false)->count();
                    $total = $wins + $losses;

                    // Calculate average ratings for the team
                    $allTeamUtrRatings = [];
                    $allTeamUstaRatings = [];
                    $opponentUtrRatings = [];
                    $opponentUstaRatings = [];

                    foreach ($appearances as $appearance) {
                        foreach ($appearance['team_players'] as $courtPlayer) {
                            if ($courtPlayer->utr_doubles_rating) {
                                $allTeamUtrRatings[] = $courtPlayer->utr_doubles_rating;
                            }
                            if ($courtPlayer->usta_dynamic_rating) {
                                $allTeamUstaRatings[] = $courtPlayer->usta_dynamic_rating;
                            }
                        }

                        $opponentPlayers = $appearance['court']->courtPlayers->where('team_id', '!=', $team->id);
                        foreach ($opponentPlayers as $opponent) {
                            if ($opponent->utr_doubles_rating) {
                                $opponentUtrRatings[] = $opponent->utr_doubles_rating;
                            }
                            if ($opponent->usta_dynamic_rating) {
                                $opponentUstaRatings[] = $opponent->usta_dynamic_rating;
                            }
                        }
                    }

                    $avgTeamUtr = !empty($allTeamUtrRatings) ? array_sum($allTeamUtrRatings) / count($allTeamUtrRatings) : null;
                    $avgTeamUsta = !empty($allTeamUstaRatings) ? array_sum($allTeamUstaRatings) / count($allTeamUstaRatings) : null;
                    $avgOpponentUtr = !empty($opponentUtrRatings) ? array_sum($opponentUtrRatings) / count($opponentUtrRatings) : null;
                    $avgOpponentUsta = !empty($opponentUstaRatings) ? array_sum($opponentUstaRatings) / count($opponentUstaRatings) : null;

                    $playerNames = $players->map(function($p) {
                        return $p->first_name . ' ' . $p->last_name;
                    })->toArray();

                    $playerStats[] = [
                        'player_id' => $teamKey,
                        'player_ids' => $teamData['player_ids'],
                        'player_name' => implode(' / ', $playerNames),
                        'wins' => $wins,
                        'losses' => $losses,
                        'total' => $total,
                        'win_percentage' => $total > 0 ? ($wins / $total) * 100 : 0,
                        'avg_utr' => $avgTeamUtr,
                        'avg_usta' => $avgTeamUsta,
                        'avg_opponent_utr' => $avgOpponentUtr,
                        'avg_opponent_usta' => $avgOpponentUsta,
                        'is_team' => true,
                    ];
                }
            }

            // Sort players by total matches (most matches first)
            usort($playerStats, function($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            // Calculate court position win percentage
            $courtWins = 0;
            $courtLosses = 0;
            foreach ($courtsInGroup as $court) {
                $teamPlayersOnCourt = $court->courtPlayers->where('team_id', $team->id);
                if ($teamPlayersOnCourt->count() > 0) {
                    // Check if this team won this court
                    $won = $teamPlayersOnCourt->first()->won;
                    if ($won) {
                        $courtWins++;
                    } else {
                        $courtLosses++;
                    }
                }
            }
            $courtTotal = $courtWins + $courtLosses;
            $courtWinPercentage = $courtTotal > 0 ? ($courtWins / $courtTotal) * 100 : null;

            $stats[] = [
                'court_type' => $type,
                'court_number' => $number,
                'avg_utr_singles' => $avgUtrSingles,
                'avg_utr_doubles' => $avgUtrDoubles,
                'avg_usta_dynamic' => $avgUstaDynamic,
                'player_count' => $allCourtPlayers->count(),
                'court_wins' => $courtWins,
                'court_losses' => $courtLosses,
                'court_win_percentage' => $courtWinPercentage,
                'players' => $playerStats,
            ];
        }

        // Sort by court type (singles first) then by number
        usort($stats, function($a, $b) {
            if ($a['court_type'] !== $b['court_type']) {
                return $a['court_type'] === 'singles' ? -1 : 1;
            }
            return $a['court_number'] <=> $b['court_number'];
        });

        return $stats;
    }
}
