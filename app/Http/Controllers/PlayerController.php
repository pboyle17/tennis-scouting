<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Http\Controllers\Controller;
use App\Jobs\UpdateUtrRatingsJob;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
      $sortField = $request->get('sort', 'id'); // default sort field
      $sortDirection = $request->get('direction', 'asc'); // default sort direction
  
      $players = Player::orderBy($sortField, $sortDirection)->get();
  
      return view('players.index', compact('players', 'sortField', 'sortDirection'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('players.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'utr_id' => 'nullable|integer',
            'utr_rating' => 'nullable|numeric',
            'USTA_rating' => 'nullable|numeric'
        ]);

        Player::create($validated);

        return redirect()->route('players.index')->with('success', 'Player created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Player $player)
    {
        return view('players.edit', compact('player'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Player $player)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'utr_id' => 'nullable|integer',
            'utr_rating' => 'nullable|numeric',
            'USTA_rating' => 'nullable|numeric'
        ]);

        $player->update($validated);

        return redirect()->route('players.index')->with('success', 'Player updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
      //
    }

    public function updateUtrRatings()
    {
      UpdateUtrRatingsJob::dispatch();

      return redirect()->route('players.index')->with('status', '✅ UTR update job has been dispatched!');
    }
}
