<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Player;

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
    public function show(Team $team)
    {
        $team->load('players');

        // Get players not on this team for the add player functionality
        $availablePlayers = Player::whereNotIn('id', $team->players->pluck('id'))
                                  ->orderBy('first_name')
                                  ->orderBy('last_name')
                                  ->get();

        return view('teams.show', compact('team', 'availablePlayers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
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
    public function destroy(string $id)
    {
        //
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
}
