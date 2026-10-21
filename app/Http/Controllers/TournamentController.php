<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tournament;
use App\Models\Player;

class TournamentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tournaments = Tournament::withCount('players')->get();
        return view('tournaments.index', compact('tournaments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tournaments.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'usta_link' => 'nullable|string|max:255',
            'utr_link' => 'nullable|url|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Tournament::create($validated);

        return redirect()->route('tournaments.index')->with('success', 'Tournament created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Tournament $tournament)
    {
        $sortField = $request->get('sort', 'utr_singles_rating');
        $sortDirection = $request->get('direction', 'desc');

        $tournament->load([
            'players',
            'draws' => fn($q) => $q->orderByRaw("CASE WHEN team_type = 'singles' THEN 0 ELSE 1 END")->orderBy('name'),
            'draws.matches.sets',
            'draws.matches.matchPlayers.player',
        ]);

        // Get players not in this tournament for the add player functionality
        $availablePlayers = Player::whereDoesntHave('tournaments', function($query) use ($tournament) {
            $query->where('tournament_id', $tournament->id);
        })
        ->orderBy('first_name')
        ->orderBy('last_name')
        ->get();

        return view('tournaments.show', compact('tournament', 'availablePlayers', 'sortField', 'sortDirection'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tournament $tournament)
    {
        return view('tournaments.edit', compact('tournament'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tournament $tournament)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'usta_link' => 'nullable|string|max:255',
            'utr_link' => 'nullable|url|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $tournament->update($request->only(['name', 'usta_link', 'utr_link', 'start_date', 'end_date', 'location', 'description']));

        return redirect()->route('tournaments.index')->with('success', 'Tournament updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tournament $tournament)
    {
        $tournamentName = $tournament->name;

        // Detach all players from the tournament (removes relationships, doesn't delete players)
        $tournament->players()->detach();

        // Delete the tournament
        $tournament->delete();

        return redirect()->route('tournaments.index')->with('success', "Tournament '{$tournamentName}' has been deleted successfully.");
    }

    /**
     * Add players to the tournament.
     */
    public function addPlayer(Request $request, Tournament $tournament)
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
            // Check if player is already in this tournament
            if ($tournament->players()->where('player_id', $player->id)->exists()) {
                $skippedPlayers[] = $player->first_name . ' ' . $player->last_name;
            } else {
                $tournament->players()->attach($player->id);
                $addedPlayers[] = $player->first_name . ' ' . $player->last_name;
            }
        }

        $messages = [];
        if (count($addedPlayers) > 0) {
            $playersList = implode(', ', $addedPlayers);
            $messages[] = count($addedPlayers) === 1
                ? "$playersList has been added to the tournament!"
                : count($addedPlayers) . " players have been added: $playersList";
        }

        if (count($skippedPlayers) > 0) {
            $skippedList = implode(', ', $skippedPlayers);
            $messages[] = count($skippedPlayers) === 1
                ? "$skippedList is already in this tournament."
                : "These players are already in this tournament: $skippedList";
        }

        $messageType = count($addedPlayers) > 0 ? 'success' : 'error';
        return back()->with($messageType, implode(' ', $messages));
    }

    /**
     * Remove a player from the tournament.
     */
    public function removePlayer(Tournament $tournament, Player $player)
    {
        $tournament->players()->detach($player->id);

        return back()->with('success', $player->first_name . ' ' . $player->last_name . ' has been removed from the tournament.');
    }

    /**
     * Create a tournament from a UTR event link, then queue a sync of its draws and results.
     */
    public function createFromUtrLink(Request $request, \App\Services\UtrService $utr)
    {
        abort_unless(app()->isLocal(), 404);

        $request->validate(['utr_link' => 'required|url|max:255']);

        $eventId = Tournament::parseUtrEventId($request->utr_link);
        if (!$eventId) {
            return back()->with('error', 'Please provide a UTR event link like https://app.utrsports.net/tournaments/3560084');
        }

        $existing = Tournament::whereNotNull('utr_link')->get()->first(fn($t) => $t->utrEventId() === $eventId);
        if ($existing) {
            return redirect()->route('tournaments.show', $existing->id)
                ->with('error', 'A tournament for this UTR event already exists. Use "Sync from UTR" to refresh it.');
        }

        try {
            $event = $utr->fetchImportedEvent($eventId);
        } catch (\App\Exceptions\UtrRateLimitException $e) {
            return back()->with('error', 'UTR is rate limiting requests right now. Please try again in a few minutes.');
        } catch (\Exception $e) {
            \Log::error('Failed to fetch UTR event for tournament creation: ' . $e->getMessage());
            return back()->with('error', 'Could not load that event from UTR. Check the link, or that the UTR login (JWT) in Configurations is still valid.');
        }

        $tournament = Tournament::create([
            'name' => $event['name'] ?? "UTR event {$eventId}",
            'utr_link' => $request->utr_link,
            'start_date' => isset($event['startDate']) ? substr($event['startDate'], 0, 10) : null,
            'end_date' => isset($event['endDate']) ? substr($event['endDate'], 0, 10) : null,
        ]);

        \App\Jobs\SyncTournamentFromUtrJob::dispatch($tournament);

        return redirect()->route('tournaments.show', $tournament->id)
            ->with('success', "Tournament '{$tournament->name}' created. Syncing draws and results from UTR — refresh in a minute.");
    }

    /**
     * Queue a sync of draws, matches and players from the tournament's UTR event.
     */
    public function syncFromUtr(Tournament $tournament)
    {
        abort_unless(app()->isLocal(), 404);

        if (!$tournament->utrEventId()) {
            return back()->with('error', 'Add a UTR link (e.g. https://app.utrsports.net/tournaments/3560084) before syncing.');
        }

        \App\Jobs\SyncTournamentFromUtrJob::dispatch($tournament);

        return back()->with('success', 'UTR sync queued. Refresh in a minute to see draws and results.');
    }
}
