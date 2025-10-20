<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Player;
use App\Jobs\CreateTeamByUstaLinkJob;
use App\Jobs\UpdateUtrRatingsJob;
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

        // Dispatch job to update UTRs for this team's players
        $jobKey = 'utr_update_' . uniqid();
        UpdateUtrRatingsJob::dispatch($utrIds, $jobKey);

        $playerCount = count($utrIds);
        $message = "UTR update job has been dispatched for {$playerCount} player" . ($playerCount > 1 ? 's' : '') . "!";

        return back()->with('status', $message);
    }
}
