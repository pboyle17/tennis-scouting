<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Player;
use App\Jobs\CreateTeamByUstaLinkJob;
use App\Jobs\CreateTeamByTennisRecordLinkJob;
use App\Jobs\UpdateUtrRatingsJob;
use App\Jobs\FetchMissingUtrIdsJob;
use Illuminate\Support\Facades\Cache;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teams = Team::with('players')->get();
        return view('teams.index', compact('teams'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('teams.create');
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
        ]);

        Team::create($validated);

        return redirect()->route('teams.index')->with('success', 'Team created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Team $team)
    {
        $sortField = $request->get('sort', 'utr_singles_rating');
        $sortDirection = $request->get('direction', 'desc');

        $team->load([
            'players.courtPlayers.court.tennisMatch',
            'league'
        ]);

        // Get players not on this team for the add player functionality
        $availablePlayers = Player::whereNotIn('id', $team->players->pluck('id'))
                                  ->orderBy('first_name')
                                  ->orderBy('last_name')
                                  ->get();

        // Get matches for this team (where team is either home or away)
        $matches = \App\Models\TennisMatch::where(function($query) use ($team) {
            $query->where('home_team_id', $team->id)
                  ->orWhere('away_team_id', $team->id);
        })
        ->with(['homeTeam', 'awayTeam', 'league'])
        ->orderBy('start_time', 'asc')
        ->get();

        // Check for score conflicts from cache (set during sync)
        $scoreConflicts = [];
        if ($team->league) {
            $conflictsKey = "score_conflicts_league_{$team->league->id}";
            $scoreConflicts = \Illuminate\Support\Facades\Cache::get($conflictsKey, []);
        }

        // Calculate court stats for this team
        $courtStats = $this->calculateTeamCourtStats($team);

        // Calculate league court stats for comparison
        $leagueCourtStats = null;
        if ($team->league) {
            $leagueCourtStats = $this->calculateLeagueCourtStats($team->league);
        }

        // Calculate league lineup comparison data
        $leagueLineupData = null;
        if ($team->league) {
            $leagueLineupData = $this->calculateLeagueLineupData($team->league);
        }

        // Calculate league doubles lineup comparison data
        $leagueDoublesLineupData = null;
        if ($team->league) {
            $leagueDoublesLineupData = $this->calculateLeagueDoublesLineupData($team->league);
        }

        return view('teams.show', compact('team', 'availablePlayers', 'sortField', 'sortDirection', 'matches', 'scoreConflicts', 'courtStats', 'leagueCourtStats', 'leagueLineupData', 'leagueDoublesLineupData'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        $team->load('league');
        return view('teams.edit', compact('team'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'usta_link' => 'nullable|string|max:255',
            'tennis_record_link' => 'nullable|string|max:255',
        ]);

        $team->update($request->only(['name', 'usta_link', 'tennis_record_link']));

        return redirect()->route('teams.index')->with('success', 'Team updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $teamName = $team->name;

        // Detach all players from the team (removes relationships, doesn't delete players)
        $team->players()->detach();

        // Delete the team
        $team->delete();

        return redirect()->route('teams.index')->with('success', "Team '{$teamName}' has been deleted successfully.");
    }

    /**
     * Add players to the team.
     */
    public function addPlayer(Request $request, Team $team)
    {
        $request->validate([
            'player_ids' => 'required|array|min:1',
            'player_ids.*' => 'exists:players,id'
        ]);

        $playerIds = $request->player_ids;
        $players = Player::whereIn('id', $playerIds)->get();

        $addedPlayers = [];
        $skippedPlayers = [];

        foreach ($players as $player) {
            // Check if player is already on this team
            if ($team->players()->where('player_id', $player->id)->exists()) {
                $skippedPlayers[] = $player->first_name . ' ' . $player->last_name;
            } else {
                $team->players()->attach($player->id);
                $addedPlayers[] = $player->first_name . ' ' . $player->last_name;
            }
        }

        $messages = [];
        if (count($addedPlayers) > 0) {
            $playersList = implode(', ', $addedPlayers);
            $messages[] = count($addedPlayers) === 1
                ? "$playersList has been added to the team!"
                : count($addedPlayers) . " players have been added: $playersList";
        }

        if (count($skippedPlayers) > 0) {
            $skippedList = implode(', ', $skippedPlayers);
            $messages[] = count($skippedPlayers) === 1
                ? "$skippedList is already on this team."
                : "These players are already on this team: $skippedList";
        }

        $messageType = count($addedPlayers) > 0 ? 'success' : 'error';
        return back()->with($messageType, implode(' ', $messages));
    }

    /**
     * Remove a player from the team.
     */
    public function removePlayer(Team $team, Player $player)
    {
        $team->players()->detach($player->id);

        return back()->with('success', $player->first_name . ' ' . $player->last_name . ' has been removed from the team.');
    }

    /**
     * Create team from USTA link
     */
    public function createFromUstaLink(Request $request)
    {
        $request->validate([
            'usta_link' => 'required|url|regex:/tennislink\.usta\.com/'
        ]);

        // Check if a job is already running
        if (Cache::has('usta_team_creation_running')) {
            $message = '⏳ USTA team creation is already in progress. Please wait for it to complete.';

            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 409);
            }

            return redirect()->route('teams.index')->with('error', $message);
        }

        // Mark job as running
        Cache::put('usta_team_creation_running', true, 600); // 10 minutes

        $job = CreateTeamByUstaLinkJob::dispatch($request->usta_link);

        $message = '🚀 Creating team from USTA link... This may take a few minutes.';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $message,
                'job_id' => $job->getJobId()
            ]);
        }

        return redirect()->route('teams.index')->with([
            'status' => $message,
            'usta_job_id' => $job->getJobId()
        ]);
    }

    /**
     * Get USTA team creation progress
     */
    public function getUstaCreationProgress(Request $request)
    {
        $jobId = $request->get('job_id');
        if (!$jobId) {
            return response()->json(['error' => 'Job ID required'], 400);
        }

        $progress = Cache::get("usta_team_creation_progress_{$jobId}");
        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($progress);
    }

    /**
     * Create team from Tennis Record link
     */
    public function createFromTennisRecordLink(Request $request)
    {
        $request->validate([
            'tennis_record_link' => 'required|url|regex:/tennisrecord\.com/'
        ]);

        // Check if a job is already running
        if (Cache::has('tennis_record_team_creation_running')) {
            $message = '⏳ Tennis Record team creation is already in progress. Please wait for it to complete.';

            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 409);
            }

            return redirect()->route('teams.index')->with('error', $message);
        }

        // Mark job as running
        Cache::put('tennis_record_team_creation_running', true, 600); // 10 minutes

        CreateTeamByTennisRecordLinkJob::dispatch($request->tennis_record_link);

        $message = '🚀 Creating team from Tennis Record link... This may take a few minutes.';

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $message
            ]);
        }

        return redirect()->route('teams.index')->with('status', $message);
    }

    /**
     * Get Tennis Record team creation progress
     */
    public function getTennisRecordCreationProgress(Request $request)
    {
        $jobKey = $request->get('job_key');
        if (!$jobKey) {
            return response()->json(['error' => 'Job key required'], 400);
        }

        $progress = Cache::get("tennis_record_team_creation_progress_{$jobKey}");
        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($progress);
    }

    /**
     * Sync team from Tennis Record link
     */
    public function syncFromTennisRecord(Team $team)
    {
        if (!$team->tennis_record_link) {
            return back()->with('error', 'This team does not have a Tennis Record link.');
        }

        // Check if a sync job is already running for this team
        if (Cache::has('tennis_record_team_sync_running_' . $team->id)) {
            return back()->with('error', '⏳ Team sync is already in progress. Please wait for it to complete.');
        }

        // Mark job as running
        Cache::put('tennis_record_team_sync_running_' . $team->id, true, 600); // 10 minutes

        \App\Jobs\SyncTeamFromTennisRecordJob::dispatch($team);

        return back()->with('status', '🔄 Syncing team from Tennis Record... This may take a few minutes.');
    }

    /**
     * Get Tennis Record team sync progress
     */
    public function getTennisRecordSyncProgress(Request $request)
    {
        $jobKey = $request->get('job_key');
        if (!$jobKey) {
            return response()->json(['error' => 'Job key required'], 400);
        }

        $progress = Cache::get("tennis_record_team_sync_progress_{$jobKey}");
        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($progress);
    }

    /**
     * Sync team matches from Tennis Record league page for this specific team
     */
    public function syncTeamMatches(Team $team)
    {
        try {
            // Ensure team has a league
            if (!$team->league) {
                return back()->with('error', 'Team is not associated with a league.');
            }

            // Ensure league has Tennis Record link
            if (!$team->league->tennis_record_link) {
                return back()->with('error', 'League does not have a Tennis Record link.');
            }

            // Dispatch the job with team filter
            \App\Jobs\SyncTeamMatchesJob::dispatch($team->league, $team->id);

            return back()->with('status', '✅ Team matches sync job has been dispatched!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Team matches sync dispatch failed: " . $e->getMessage(), [
                'team_id' => $team->id,
                'error' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to dispatch sync job: ' . $e->getMessage());
        }
    }

    /**
     * Sync Tennis Record profiles for players on this team
     */
    public function syncTrProfiles(Team $team)
    {
        try {
            // Ensure team has a league
            if (!$team->league) {
                return back()->with('error', 'Team is not associated with a league.');
            }

            // Dispatch the job with team filter
            \App\Jobs\SyncTrProfilesJob::dispatch($team->league, [$team->id]);

            return back()->with('status', '✅ Tennis Record profile sync job has been dispatched for this team!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("TR profile sync dispatch failed: " . $e->getMessage(), [
                'team_id' => $team->id,
                'error' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to dispatch sync job: ' . $e->getMessage());
        }
    }

    /**
     * Update UTR ratings for all players on the team
     */
    public function updateUtr(Team $team)
    {
        $team->load('players');

        // Get UTR IDs for all players on the team who have a UTR ID
        $utrIds = $team->players()
                       ->whereNotNull('utr_id')
                       ->pluck('utr_id')
                       ->toArray();

        if (empty($utrIds)) {
            return back()->with('error', 'No players with UTR IDs found on this team.');
        }

        // Batch UTR IDs into groups of 20
        $batches = array_chunk($utrIds, 20);

        // Dispatch a job for each batch
        foreach ($batches as $batch) {
            $jobKey = 'utr_update_' . uniqid();
            UpdateUtrRatingsJob::dispatch($batch, $jobKey);
        }

        $playerCount = count($utrIds);
        $batchCount = count($batches);
        $message = "UTR update job" . ($batchCount > 1 ? 's have' : ' has') . " been dispatched for {$playerCount} player" . ($playerCount > 1 ? 's' : '') . " in {$batchCount} batch" . ($batchCount > 1 ? 'es' : '') . "!";

        return back()->with('status', $message);
    }

    /**
     * Find missing UTR IDs for players on the team
     */
    public function findMissingUtrIds(Team $team)
    {
        $team->load('players');

        // Get players without UTR IDs
        $playersWithoutUtrIds = $team->players()
                                     ->whereNull('utr_id')
                                     ->get();

        if ($playersWithoutUtrIds->isEmpty()) {
            return back()->with('status', 'All players on this team already have UTR IDs!');
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
                            'last_name' => $player->last_name
                        ],
                        'results' => $hits
                    ];
                }

                \Illuminate\Support\Facades\Log::info("UTR Search for team player: {$playerName}", [
                    'player_id' => $player->id,
                    'team_id' => $team->id,
                    'results_count' => count($hits)
                ]);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("UTR search failed for player {$player->id}: " . $e->getMessage());
            }
        }

        return back()->with([
            'utr_search_results' => $searchResults,
            'status' => 'Found ' . count($searchResults) . ' player(s) with potential UTR matches. Review and select the correct profiles below.'
        ]);
    }

    /**
     * Set UTR data for a player from team search
     */
    public function setPlayerUtrData(Request $request, Team $team, Player $player)
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

        // Set updated timestamps
        $player->utr_singles_updated_at = now();
        $player->utr_doubles_updated_at = now();

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
     * Sync match details for all team matches from Tennis Record
     */
    public function syncMatchDetails(Team $team)
    {
        // Get all matches for this team
        $matches = \App\Models\TennisMatch::where(function($query) use ($team) {
            $query->where('home_team_id', $team->id)
                  ->orWhere('away_team_id', $team->id);
        })
        ->whereNotNull('tennis_record_match_link')
        ->get();

        if ($matches->isEmpty()) {
            return back()->with('error', 'No matches with Tennis Record links found for this team.');
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

    /**
     * Calculate average ratings by court position for the league
     */
    protected function calculateLeagueCourtStats($league)
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
                $avgUtrSingles = $allCourtPlayers->whereNotNull('utr_singles_rating')->avg('utr_singles_rating');
            } else {
                $avgUtrDoubles = $allCourtPlayers->whereNotNull('utr_doubles_rating')->avg('utr_doubles_rating');
            }

            $avgUstaDynamic = $allCourtPlayers->whereNotNull('usta_dynamic_rating')->avg('usta_dynamic_rating');

            $stats[] = [
                'court_type' => $type,
                'court_number' => $number,
                'avg_utr_singles' => $avgUtrSingles,
                'avg_utr_doubles' => $avgUtrDoubles,
                'avg_usta_dynamic' => $avgUstaDynamic,
            ];
        }

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
                    'name' => $player->first_name . ' ' . $player->last_name,
                    'utr_singles' => $player->utr_singles_rating,
                    'usta_dynamic' => $player->USTA_dynamic_rating,
                    'utr_singles_reliable' => $player->utr_singles_reliable,
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

    protected function calculateLeagueDoublesLineupData($league)
    {
        $teams = $league->teams()->with('players')->get();
        $lineupData = [];

        foreach ($teams as $team) {
            // Get all players with either doubles rating
            $allPlayers = $team->players()
                ->where(function($query) {
                    $query->whereNotNull('utr_doubles_rating')
                          ->orWhereNotNull('USTA_dynamic_rating');
                })
                ->get();

            $players = [];
            foreach ($allPlayers as $player) {
                $players[] = [
                    'name' => $player->first_name . ' ' . $player->last_name,
                    'utr_doubles' => $player->utr_doubles_rating,
                    'usta_dynamic' => $player->USTA_dynamic_rating,
                    'utr_doubles_reliable' => $player->utr_doubles_reliable,
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
