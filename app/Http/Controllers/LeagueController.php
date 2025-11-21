<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\League;
use App\Models\Team;
use App\Models\Player;

class LeagueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
      $leagues = League::with('teams')->get();
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

        // Get all players from all teams in the league
        $players = collect();
        foreach ($league->teams as $team) {
            foreach ($team->players as $player) {
                // Add team name to player for display
                $player->team_name = $team->name;
                $player->team_id = $team->id;
                $players->push($player);
            }
        }

        return view('leagues.show', compact('league', 'availableTeams', 'players', 'sortField', 'sortDirection'));
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
      ]);

      $league->update($request->only(['name', 'usta_link', 'tennis_record_link']));

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
    public function updateUtr(League $league)
    {
        $league->load('teams.players');

        // Get UTR IDs for all players in all teams in the league who have a UTR ID
        $utrIds = [];
        $teamCount = $league->teams->count();

        foreach ($league->teams as $team) {
            $teamUtrIds = $team->players()
                               ->whereNotNull('utr_id')
                               ->pluck('utr_id')
                               ->toArray();
            $utrIds = array_merge($utrIds, $teamUtrIds);
        }

        // Remove duplicates
        $utrIds = array_unique($utrIds);

        if (empty($utrIds)) {
            return back()->with('error', 'No players with UTR IDs found in this league. Please add UTR IDs to players first.');
        }

        // Dispatch job to update UTRs for all players in the league
        $jobKey = 'utr_update_' . uniqid();
        \App\Jobs\UpdateUtrRatingsJob::dispatch($utrIds, $jobKey);

        $playerCount = count($utrIds);
        $teamText = $teamCount === 1 ? '1 team' : "{$teamCount} teams";
        $playerText = $playerCount === 1 ? '1 player' : "{$playerCount} players";

        $message = "🔄 UTR update started! Updating ratings for {$playerText} across {$teamText} in \"{$league->name}\". This may take a few minutes.";

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

        $jobKey = 'tennis_record_league_' . uniqid();
        \App\Jobs\CreateTeamsFromTennisRecordLeagueJob::dispatch($league->id, $jobKey);

        return back()->with([
            'status' => '🚀 Creating teams from Tennis Record league... This may take several minutes.',
            'tennis_record_league_job_key' => $jobKey
        ]);
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
    public function setPlayerUtrData(Request $request, League $league, Player $player)
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
}
