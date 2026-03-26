<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\League;
use App\Models\Team;
use App\Models\Player;
use App\Jobs\SyncTeamFromTennisRecordJob;
use Illuminate\Support\Facades\Cache;

class LeagueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
      $showInactive = $request->boolean('show_inactive');
      $query = League::with('teams')->orderByDesc('active');
      if (!$showInactive) {
          $query->where('active', true);
      }
      $leagues = $query->get();

      // Calculate court averages for all leagues in a single query
      $courtAverages = \DB::table('leagues')
          ->join('teams', 'leagues.id', '=', 'teams.league_id')
          ->join('tennis_matches', function($join) {
              $join->on('teams.id', '=', 'tennis_matches.home_team_id')
                   ->orOn('teams.id', '=', 'tennis_matches.away_team_id');
          })
          ->join('courts', 'tennis_matches.id', '=', 'courts.tennis_match_id')
          ->join('court_players', 'courts.id', '=', 'court_players.court_id')
          ->select(
              'leagues.id as league_id',
              'courts.court_type',
              'courts.court_number',
              \DB::raw('AVG(CASE WHEN court_players.utr_singles_rating > 0 THEN court_players.utr_singles_rating END) as avg_utr_singles'),
              \DB::raw('AVG(CASE WHEN court_players.utr_doubles_rating > 0 THEN court_players.utr_doubles_rating END) as avg_utr_doubles'),
              \DB::raw('AVG(CASE WHEN court_players.usta_dynamic_rating > 0 THEN court_players.usta_dynamic_rating END) as avg_usta_dynamic')
          )
          ->groupBy('leagues.id', 'courts.court_type', 'courts.court_number')
          ->get();

      // Map court averages to leagues
      $leagueAveragesMap = [];
      foreach ($courtAverages as $avg) {
          $key = ($avg->court_type === 'singles' ? 's' : 'd') . $avg->court_number;
          if (!isset($leagueAveragesMap[$avg->league_id])) {
              $leagueAveragesMap[$avg->league_id] = ['s1' => null, 's2' => null, 'd1' => null, 'd2' => null, 'd3' => null];
          }
          if (in_array($key, ['s1', 's2', 'd1', 'd2', 'd3'])) {
              $leagueAveragesMap[$avg->league_id][$key] = [
                  'utr' => $avg->court_type === 'singles' ? $avg->avg_utr_singles : $avg->avg_utr_doubles,
                  'usta' => $avg->avg_usta_dynamic,
              ];
          }
      }

      // Assign court averages to each league
      foreach ($leagues as $league) {
          $league->courtAverages = $leagueAveragesMap[$league->id] ?? ['s1' => null, 's2' => null, 'd1' => null, 'd2' => null, 'd3' => null];
      }

      return view('leagues.index', compact('leagues'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
      return view('leagues.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
          'name' => 'required|string|max:255',
          'usta_link' => 'nullable|string|max:255',
          'tennis_record_link' => 'nullable|string|max:255',
          'NTRP_rating' => 'nullable|numeric|min:1.0|max:7.0',
      ]);

      League::create($validated);

      return redirect()->route('leagues.index')->with('success', 'League created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, League $league)
    {
        $sortField = $request->get('sort', 'utr_singles_rating');
        $sortDirection = $request->get('direction', 'desc');

        $league->load('teams.players');

        // Get teams not in this league
        $availableTeams = Team::where('league_id', null)
                             ->orWhere('league_id', '!=', $league->id)
                             ->orderBy('name')
                             ->get();

        // Get all matches for teams in this league
        $teamIds = $league->teams->pluck('id');
        $matches = \App\Models\TennisMatch::where(function($query) use ($teamIds) {
                $query->whereIn('home_team_id', $teamIds)
                      ->orWhereIn('away_team_id', $teamIds);
            })
            ->orderBy('start_time', 'asc')
            ->with(['homeTeam', 'awayTeam', 'courts.courtSets', 'courts.courtPlayers'])
            ->get();

        // Compute team standings
        $teamStandings = [];
        foreach ($league->teams as $team) {
            $teamStandings[$team->id] = [
                'team' => $team,
                'wins' => 0, 'losses' => 0,
                'indiv_wins' => 0, 'indiv_losses' => 0,
                'sets_won' => 0, 'sets_lost' => 0,
                'games_won' => 0, 'games_lost' => 0,
                'games_won_pct' => 0, 'games_lost_pct' => 0,
                'defaults' => 0,
            ];
        }

        foreach ($matches as $match) {
            if ($match->home_score === null || $match->away_score === null) continue;

            $hid = $match->home_team_id;
            $aid = $match->away_team_id;

            if (isset($teamStandings[$hid]) && isset($teamStandings[$aid])) {
                if ($match->home_score > $match->away_score) {
                    $teamStandings[$hid]['wins']++;
                    $teamStandings[$aid]['losses']++;
                } elseif ($match->away_score > $match->home_score) {
                    $teamStandings[$aid]['wins']++;
                    $teamStandings[$hid]['losses']++;
                }
            }

            foreach ($match->courts as $court) {
                if ($court->home_score === null || $court->away_score === null) continue;

                if (isset($teamStandings[$hid]) && isset($teamStandings[$aid])) {
                    if ($court->home_score > $court->away_score) {
                        $teamStandings[$hid]['indiv_wins']++;
                        $teamStandings[$aid]['indiv_losses']++;
                    } else {
                        $teamStandings[$aid]['indiv_wins']++;
                        $teamStandings[$hid]['indiv_losses']++;
                    }
                }

                if ($court->is_default) {
                    if (isset($teamStandings[$hid]) && $court->courtPlayers->where('team_id', $hid)->isEmpty()) {
                        $teamStandings[$hid]['defaults']++;
                    }
                    if (isset($teamStandings[$aid]) && $court->courtPlayers->where('team_id', $aid)->isEmpty()) {
                        $teamStandings[$aid]['defaults']++;
                    }
                }

                foreach ($court->courtSets as $set) {
                    $isHome = isset($teamStandings[$hid]);
                    $isAway = isset($teamStandings[$aid]);

                    if (!$court->is_default) {
                        if ($set->home_score > $set->away_score) {
                            if ($isHome) $teamStandings[$hid]['sets_won']++;
                            if ($isAway) $teamStandings[$aid]['sets_lost']++;
                        } elseif ($set->away_score > $set->home_score) {
                            if ($isAway) $teamStandings[$aid]['sets_won']++;
                            if ($isHome) $teamStandings[$hid]['sets_lost']++;
                        }
                    }

                    if ($isHome) {
                        $teamStandings[$hid]['games_won'] += $set->home_score;
                        $teamStandings[$hid]['games_lost'] += $set->away_score;
                        if (!$court->is_default) {
                            $teamStandings[$hid]['games_won_pct'] += $set->home_score;
                            $teamStandings[$hid]['games_lost_pct'] += $set->away_score;
                        }
                    }
                    if ($isAway) {
                        $teamStandings[$aid]['games_won'] += $set->away_score;
                        $teamStandings[$aid]['games_lost'] += $set->home_score;
                        if (!$court->is_default) {
                            $teamStandings[$aid]['games_won_pct'] += $set->away_score;
                            $teamStandings[$aid]['games_lost_pct'] += $set->home_score;
                        }
                    }
                }
            }
        }

        usort($teamStandings, function ($a, $b) {
            if ($b['wins'] !== $a['wins']) return $b['wins'] - $a['wins'];
            $aTotal = $a['games_won'] + $a['games_lost'];
            $bTotal = $b['games_won'] + $b['games_lost'];
            $aPct = $aTotal > 0 ? $a['games_won'] / $aTotal : 0;
            $bPct = $bTotal > 0 ? $b['games_won'] / $bTotal : 0;
            return $bPct <=> $aPct;
        });

        // Compute singles/doubles W-L records per player for this league
        $matchIds = $matches->pluck('id');
        $courtPlayerStats = \App\Models\CourtPlayer::whereHas('court', fn($q) => $q->whereIn('tennis_match_id', $matchIds))
            ->with('court:id,court_type')
            ->get(['player_id', 'won', 'court_id']);

        $playerRecords = [];
        foreach ($courtPlayerStats as $cp) {
            $pid = $cp->player_id;
            $type = $cp->court->court_type;
            $playerRecords[$pid][$type]['wins'] = ($playerRecords[$pid][$type]['wins'] ?? 0) + ($cp->won ? 1 : 0);
            $playerRecords[$pid][$type]['losses'] = ($playerRecords[$pid][$type]['losses'] ?? 0) + ($cp->won ? 0 : 1);
        }

        // Get all players from all teams in the league
        $players = collect();
        foreach ($league->teams as $team) {
            foreach ($team->players as $player) {
                $player->team_name = $team->name;
                $player->team_id = $team->id;
                $player->singles_wins = $playerRecords[$player->id]['singles']['wins'] ?? 0;
                $player->singles_losses = $playerRecords[$player->id]['singles']['losses'] ?? 0;
                $player->doubles_wins = $playerRecords[$player->id]['doubles']['wins'] ?? 0;
                $player->doubles_losses = $playerRecords[$player->id]['doubles']['losses'] ?? 0;
                $players->push($player);
            }
        }

        // Calculate average ratings by court position
        $courtStats = $this->calculateCourtStats($league);

        // Calculate lineup comparison data
        $leagueLineupData = $this->calculateLeagueLineupData($league);
        $leagueDoublesLineupData = $this->calculateLeagueDoublesLineupData($league);

        return view('leagues.show', compact('league', 'availableTeams', 'players', 'sortField', 'sortDirection', 'matches', 'courtStats', 'leagueLineupData', 'leagueDoublesLineupData', 'teamStandings'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(League $league)
    {
      return view('leagues.edit', compact('league'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, League $league)
    {
        $request->validate([
          'name' => 'required|string|max:255',
          'usta_link' => 'nullable|string|max:255',
          'tennis_record_link' => 'nullable|string|max:255',
          'NTRP_rating' => 'nullable|numeric|min:1.0|max:7.0',
          'is_combo' => 'nullable|boolean',
      ]);

      $data = $request->only(['name', 'usta_link', 'tennis_record_link', 'NTRP_rating']);
      $data['is_combo'] = $request->has('is_combo') ? true : false;
      $data['active'] = $request->has('active') ? true : false;

      $league->update($data);

      return redirect()->route('leagues.index')->with('success', 'League updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(League $league)
    {
        $leagueName = $league->name;

        // Set league_id to null for all teams in this league
        $league->teams()->update(['league_id' => null]);

        // Delete the league
        $league->delete();

        return redirect()->route('leagues.index')->with('success', "League '{$leagueName}' has been deleted successfully.");
    }

    /**
     * Add teams to the league.
     */
    public function addTeams(Request $request, League $league)
    {
        $request->validate([
            'team_ids' => 'required|array|min:1',
            'team_ids.*' => 'exists:teams,id'
        ]);

        $teamIds = $request->team_ids;
        $teams = Team::whereIn('id', $teamIds)->get();

        $addedTeams = [];
        $skippedTeams = [];

        foreach ($teams as $team) {
            // Check if team is already in this league
            if ($team->league_id === $league->id) {
                $skippedTeams[] = $team->name;
            } else {
                $team->league_id = $league->id;
                $team->save();
                $addedTeams[] = $team->name;
            }
        }

        $messages = [];
        if (count($addedTeams) > 0) {
            $teamsList = implode(', ', $addedTeams);
            $messages[] = count($addedTeams) === 1
                ? "$teamsList has been added to the league!"
                : count($addedTeams) . " teams have been added: $teamsList";
        }

        if (count($skippedTeams) > 0) {
            $skippedList = implode(', ', $skippedTeams);
            $messages[] = count($skippedTeams) === 1
                ? "$skippedList is already in this league."
                : "These teams are already in this league: $skippedList";
        }

        $messageType = count($addedTeams) > 0 ? 'success' : 'error';
        return back()->with($messageType, implode(' ', $messages));
    }

    /**
     * Remove a team from the league.
     */
    public function removeTeam(League $league, Team $team)
    {
        $team->league_id = null;
        $team->save();

        return back()->with('success', $team->name . ' has been removed from the league.');
    }

    /**
     * Update UTR ratings for all players in the league
     */
    public function updateUtr(Request $request, League $league)
    {
        // Get team IDs from request (filtered teams) or all teams
        $teamIds = $request->input('team_ids') ? explode(',', $request->input('team_ids')) : null;

        // Get teams to update UTRs for
        $query = $league->teams();
        if ($teamIds) {
            $query->whereIn('id', $teamIds);
        }
        $teamsToUpdate = $query->get();

        // Get UTR IDs for all players in filtered teams who have a UTR ID
        $utrIds = [];
        $teamCount = $teamsToUpdate->count();

        foreach ($teamsToUpdate as $team) {
            $teamUtrIds = $team->players()
                               ->whereNotNull('utr_id')
                               ->pluck('utr_id')
                               ->toArray();
            $utrIds = array_merge($utrIds, $teamUtrIds);
        }

        // Remove duplicates
        $utrIds = array_unique($utrIds);

        if (empty($utrIds)) {
            return back()->with('error', 'No players with UTR IDs found in the selected teams. Please add UTR IDs to players first.');
        }

        // Dispatch a single job to process all players sequentially (throttled to 10/min)
        $jobKey = 'utr_update_' . uniqid();
        \App\Jobs\UpdateUtrRatingsJob::dispatch($utrIds, $jobKey);

        $league->utr_last_updated_at = now();
        $league->save();

        $playerCount = count($utrIds);
        $teamText = $teamCount === 1 ? '1 team' : "{$teamCount} teams";
        $playerText = $playerCount === 1 ? '1 player' : "{$playerCount} players";

        $message = "UTR update started! Updating ratings for {$playerText} across {$teamText} in \"{$league->name}\". This may take a few minutes.";

        return back()->with('status', $message);
    }

    /**
     * Create teams from Tennis Record league link
     */
    public function createTeamsFromLeague(League $league)
    {
        if (!$league->tennis_record_link) {
            return back()->with('error', 'This league does not have a Tennis Record link.');
        }

        // Check if a job is already running for this league
        if (\Illuminate\Support\Facades\Cache::has('tennis_record_league_creation_running_' . $league->id)) {
            return back()->with('error', '⏳ Team creation from league is already in progress. Please wait for it to complete.');
        }

        // Mark job as running
        \Illuminate\Support\Facades\Cache::put('tennis_record_league_creation_running_' . $league->id, true, 1800); // 30 minutes

        \App\Jobs\CreateTeamsFromTennisRecordLeagueJob::dispatch($league->id);

        return back()->with('status', '🚀 Creating teams from Tennis Record league... This may take several minutes.');
    }

    /**
     * Get Tennis Record league team creation progress
     */
    public function getLeagueCreationProgress(Request $request)
    {
        $jobKey = $request->get('job_key');
        if (!$jobKey) {
            return response()->json(['error' => 'Job key required'], 400);
        }

        $progress = \Illuminate\Support\Facades\Cache::get("tennis_record_league_creation_progress_{$jobKey}");
        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($progress);
    }

    /**
     * Find missing UTR IDs for players on a specific team in the league
     */
    public function findMissingUtrIdsForTeam(League $league, Team $team)
    {
        // Verify the team belongs to the league
        if ($team->league_id !== $league->id) {
            return back()->with('error', 'This team does not belong to this league.');
        }

        $team->load('players');

        // Get players without UTR IDs
        $playersWithoutUtrIds = $team->players()
                                     ->whereNull('utr_id')
                                     ->get();

        if ($playersWithoutUtrIds->isEmpty()) {
            return back()->with('status', "All players on {$team->name} already have UTR IDs!");
        }

        // Search for UTR profiles for each player
        $utrService = app(\App\Services\UtrService::class);
        $searchResults = [];

        foreach ($playersWithoutUtrIds as $player) {
            try {
                $playerName = $player->first_name . ' ' . $player->last_name;
                $results = $utrService->searchPlayers($playerName, 10);

                // Handle nested structure
                $hits = $results['players']['hits'] ?? $results['hits'] ?? [];

                if (!empty($hits)) {
                    // Check if there's exactly one result with matching names - auto-save it
                    if (count($hits) === 1) {
                        $source = $hits[0]['source'] ?? [];
                        $firstName = strtolower(trim($source['firstName'] ?? ''));
                        $lastName = strtolower(trim($source['lastName'] ?? ''));
                        $playerFirstName = strtolower(trim($player->first_name));
                        $playerLastName = strtolower(trim($player->last_name));

                        if ($firstName === $playerFirstName && $lastName === $playerLastName) {
                            // Auto-save the UTR data
                            $player->utr_id = $source['id'] ?? null;
                            $player->utr_singles_rating = $source['singlesUtr'] ?? null;
                            $player->utr_doubles_rating = $source['doublesUtr'] ?? null;

                            // Set reliability flags - only true if reliability is exactly 100
                            $player->utr_singles_reliable = isset($source['ratingProgressSingles']) && $source['ratingProgressSingles'] == 100;
                            $player->utr_doubles_reliable = isset($source['ratingProgressDoubles']) && $source['ratingProgressDoubles'] == 100;

                            // Set updated timestamps
                            $player->utr_singles_updated_at = now();
                            $player->utr_doubles_updated_at = now();

                            $player->save();

                            \Illuminate\Support\Facades\Log::info("Auto-selected and saved UTR data for {$playerName}", [
                                'player_id' => $player->id,
                                'utr_id' => $player->utr_id,
                                'singles' => $player->utr_singles_rating,
                                'doubles' => $player->utr_doubles_rating
                            ]);
                        }
                    }

                    // Still add to search results to show in UI
                    $searchResults[] = [
                        'player' => [
                            'id' => $player->id,
                            'first_name' => $player->first_name,
                            'last_name' => $player->last_name,
                            'team_name' => $team->name,
                            'team_id' => $team->id
                        ],
                        'results' => $hits
                    ];
                }

                \Illuminate\Support\Facades\Log::info("UTR Search for team player: {$playerName}", [
                    'player_id' => $player->id,
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'league_id' => $league->id,
                    'results_count' => count($hits)
                ]);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("UTR search failed for player {$player->id}: " . $e->getMessage());
            }
        }

        return back()->with([
            'utr_search_results' => $searchResults,
            'status' => 'Found ' . count($searchResults) . ' player(s) from ' . $team->name . ' with potential UTR matches. Review and select the correct profiles below.'
        ]);
    }

    /**
     * Set UTR data for a player from league search
     */
    public function setPlayerUtrData(Request $request, Player $player)
    {
        $request->validate([
            'utr_id' => 'required|integer',
            'singles_utr' => 'nullable|numeric',
            'doubles_utr' => 'nullable|numeric',
            'singles_reliability' => 'nullable|numeric',
            'doubles_reliability' => 'nullable|numeric'
        ]);

        $player->utr_id = $request->utr_id;
        $player->utr_singles_rating = $request->singles_utr;
        $player->utr_doubles_rating = $request->doubles_utr;

        // Set reliability flags - only true if reliability is exactly 100
        $player->utr_singles_reliable = $request->singles_reliability == 100;
        $player->utr_doubles_reliable = $request->doubles_reliability == 100;

        $player->save();

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "UTR data saved for {$player->first_name} {$player->last_name}!",
                'player_id' => $player->id,
                'player_name' => "{$player->first_name} {$player->last_name}"
            ]);
        }

        return back()->with('success', "UTR data saved for {$player->first_name} {$player->last_name}!");
    }

    /**
     * Sync all teams in the league from Tennis Record
     */
    public function syncAllTeamsFromTennisRecord(Request $request, League $league)
    {
        // Get team IDs from request (filtered teams) or all teams
        $teamIds = $request->input('team_ids') ? explode(',', $request->input('team_ids')) : null;

        // Get teams to sync
        $query = $league->teams()->whereNotNull('tennis_record_link');
        if ($teamIds) {
            $query->whereIn('id', $teamIds);
        }
        $teamsToSync = $query->get();

        if ($teamsToSync->isEmpty()) {
            return back()->with('error', 'No teams with Tennis Record links found to sync.');
        }

        // Dispatch sync jobs for each team
        foreach ($teamsToSync as $team) {
            SyncTeamFromTennisRecordJob::dispatch($team);
        }

        $league->teams_last_synced_at = now();
        $league->save();

        return back()->with('success', "Syncing {$teamsToSync->count()} team(s) from Tennis Record. This may take a few minutes.");
    }

    /**
     * Sync Tennis Record profiles to update USTA ratings
     */
    public function syncTrProfiles(Request $request, League $league)
    {
        try {
            // Get team IDs from request (filtered teams) or all teams
            $teamIds = $request->input('team_ids') ? explode(',', $request->input('team_ids')) : null;

            // Dispatch the job
            \App\Jobs\SyncTrProfilesJob::dispatch($league, $teamIds);

            return back()->with('status', '✅ Tennis Record profile sync job has been dispatched!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Tennis Record profile sync dispatch failed: " . $e->getMessage(), [
                'league_id' => $league->id,
                'error' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to dispatch sync job: ' . $e->getMessage());
        }
    }

    /**
     * Get Tennis Record profile sync progress
     */
    public function getTrSyncProgress(Request $request)
    {
        $jobKey = $request->get('job_key');
        if (!$jobKey) {
            return response()->json(['error' => 'Job key required'], 400);
        }

        $progress = Cache::get("tr_profiles_sync_progress_{$jobKey}");
        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($progress);
    }

    /**
     * Sync team matches from Tennis Record league page
     */
    public function syncTeamMatches(Request $request, League $league)
    {
        try {
            // Ensure league has Tennis Record link
            if (!$league->tennis_record_link) {
                return back()->with('error', 'League does not have a Tennis Record link.');
            }

            // Dispatch the job
            \App\Jobs\SyncTeamMatchesJob::dispatch($league);

            return back()->with('status', '✅ Team matches sync job has been dispatched!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Team matches sync dispatch failed: " . $e->getMessage(), [
                'league_id' => $league->id,
                'error' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to dispatch sync job: ' . $e->getMessage());
        }
    }

    /**
     * Sync match details for all league matches from Tennis Record
     */
    public function syncMatchDetails(League $league)
    {
        // Get all matches for this league
        $teamIds = $league->teams->pluck('id');
        $matches = \App\Models\TennisMatch::where(function($query) use ($teamIds) {
            $query->whereIn('home_team_id', $teamIds)
                  ->orWhereIn('away_team_id', $teamIds);
        })
        ->whereNotNull('tennis_record_match_link')
        ->get();

        if ($matches->isEmpty()) {
            return back()->with('error', 'No matches with Tennis Record links found for this league.');
        }

        // Dispatch a sync job for each match
        $jobCount = 0;
        foreach ($matches as $match) {
            \App\Jobs\SyncMatchFromTennisRecordJob::dispatch($match);
            $jobCount++;
        }

        return back()->with('status', "🎾 Dispatched {$jobCount} match detail sync jobs. This may take a few minutes.");
    }

    /**
     * Calculate average ratings by court position for the league
     */
    protected function calculateCourtStats(League $league)
    {
        $teamIds = $league->teams->pluck('id');

        // Get all match IDs for this league
        $matchIds = \App\Models\TennisMatch::where(function($query) use ($teamIds) {
            $query->whereIn('home_team_id', $teamIds)
                  ->orWhereIn('away_team_id', $teamIds);
        })->pluck('id');

        // Get all courts for these matches
        $courts = \App\Models\Court::whereIn('tennis_match_id', $matchIds)
            ->with('courtPlayers')
            ->get();

        $stats = [];

        // Group courts by type and number
        $courtGroups = $courts->groupBy(function($court) {
            return $court->court_type . '_' . $court->court_number;
        });

        foreach ($courtGroups as $key => $courtsInGroup) {
            list($type, $number) = explode('_', $key);

            // Get all court players for this court position
            $allCourtPlayers = $courtsInGroup->flatMap(function($court) {
                return $court->courtPlayers;
            });

            // Calculate averages
            $avgUtrSingles = null;
            $avgUtrDoubles = null;
            $avgUstaDynamic = null;

            if ($type === 'singles') {
                $avgUtrSingles = $allCourtPlayers->whereNotNull('utr_singles_rating')->where('utr_singles_rating', '>', 0)->avg('utr_singles_rating');
            } else {
                $avgUtrDoubles = $allCourtPlayers->whereNotNull('utr_doubles_rating')->where('utr_doubles_rating', '>', 0)->avg('utr_doubles_rating');
            }

            $avgUstaDynamic = $allCourtPlayers->whereNotNull('usta_dynamic_rating')->where('usta_dynamic_rating', '>', 0)->avg('usta_dynamic_rating');

            $stats[] = [
                'court_type' => $type,
                'court_number' => $number,
                'avg_utr_singles' => $avgUtrSingles,
                'avg_utr_doubles' => $avgUtrDoubles,
                'avg_usta_dynamic' => $avgUstaDynamic,
                'player_count' => $allCourtPlayers->count(),
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

    /**
     * Calculate league lineup comparison data (top 6 singles players per team)
     */
    protected function calculateLeagueLineupData($league)
    {
        $teams = $league->teams()->with('players')->get();
        $lineupData = [];

        foreach ($teams as $team) {
            // Get all players with either rating
            $allPlayers = $team->players()
                ->where(function($query) {
                    $query->whereNotNull('utr_singles_rating')
                          ->orWhereNotNull('USTA_dynamic_rating');
                })
                ->get();

            $players = [];
            foreach ($allPlayers as $player) {
                $players[] = [
                    'id' => $player->id,
                    'name' => $player->first_name . ' ' . $player->last_name,
                    'utr_singles' => $player->utr_singles_rating,
                    'usta_dynamic' => $player->USTA_dynamic_rating,
                    'utr_singles_reliable' => $player->utr_singles_reliable,
                    'usta_rating' => $player->USTA_rating,
                ];
            }

            $lineupData[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'players' => $players,
            ];
        }

        return $lineupData;
    }

    /**
     * Calculate league doubles lineup comparison data (top 8 doubles players per team)
     */
    protected function calculateLeagueDoublesLineupData($league)
    {
        $teams = $league->teams()->with('players')->get();
        $lineupData = [];

        foreach ($teams as $team) {
            // Get all players with either rating
            $allPlayers = $team->players()
                ->where(function($query) {
                    $query->whereNotNull('utr_doubles_rating')
                          ->orWhereNotNull('USTA_dynamic_rating');
                })
                ->get();

            $players = [];
            foreach ($allPlayers as $player) {
                $players[] = [
                    'id' => $player->id,
                    'name' => $player->first_name . ' ' . $player->last_name,
                    'utr_doubles' => $player->utr_doubles_rating,
                    'usta_dynamic' => $player->USTA_dynamic_rating,
                    'utr_doubles_reliable' => $player->utr_doubles_reliable,
                    'usta_rating' => $player->USTA_rating,
                ];
            }

            $lineupData[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'players' => $players,
            ];
        }

        return $lineupData;
    }
}
