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
    public function show(League $league)
    {
        $league->load('teams');

        // Get teams not in this league
        $availableTeams = Team::where('league_id', null)
                             ->orWhere('league_id', '!=', $league->id)
                             ->orderBy('name')
                             ->get();

        return view('leagues.show', compact('league', 'availableTeams'));
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
}
