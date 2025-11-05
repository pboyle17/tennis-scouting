<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\League;
use App\Models\Team;

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
}
