<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tournament;
use App\Models\Player;
use App\Services\UstaTournamentScrapingService;

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

        $tournament->load('players');

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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $tournament->update($request->only(['name', 'usta_link', 'start_date', 'end_date', 'location', 'description']));

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
     * Create a tournament from a USTA link
     */
    public function createFromUstaLink(Request $request)
    {
        $request->validate([
            'usta_link' => 'required|url'
        ]);

        $ustaLink = $request->usta_link;

        // Validate that it's a USTA playtennis.usta.com link
        if (!str_contains($ustaLink, 'playtennis.usta.com')) {
            return back()->with('error', 'Please provide a valid USTA tournament link from playtennis.usta.com');
        }

        try {
            $scrapingService = new UstaTournamentScrapingService();
            $tournamentData = $scrapingService->scrapeTournamentData($ustaLink);

            // Check if tournament with this USTA link already exists
            $existingTournament = Tournament::where('usta_link', $ustaLink)->first();
            if ($existingTournament) {
                return redirect()->route('tournaments.show', $existingTournament->id)
                    ->with('error', 'A tournament with this USTA link already exists.');
            }

            // Create the tournament
            $tournament = Tournament::create([
                'name' => $tournamentData['name'],
                'usta_link' => $tournamentData['usta_link'],
                'start_date' => $tournamentData['start_date'],
                'end_date' => $tournamentData['end_date'],
                'location' => $tournamentData['location'],
                'description' => $tournamentData['description'],
            ]);

            return redirect()->route('tournaments.show', $tournament->id)
                ->with('success', "Tournament '{$tournament->name}' has been created successfully from USTA link!");

        } catch (\Exception $e) {
            \Log::error('Failed to create tournament from USTA link: ' . $e->getMessage());
            return back()->with('error', 'Failed to scrape tournament data from USTA link. Please try again or create the tournament manually.');
        }
    }
}
