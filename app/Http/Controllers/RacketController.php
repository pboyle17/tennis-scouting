<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Racket;
use App\Models\Player;
use App\Models\StringJob;

class RacketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $rackets = Racket::with(['player', 'currentStringJob'])
            ->orderBy($sortField, $sortDirection)
            ->get();

        return view('rackets.index', compact('rackets', 'sortField', 'sortDirection'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $players = Player::orderBy('last_name')->orderBy('first_name')->get();
        return view('rackets.create', compact('players'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $addStringJob = $request->boolean('add_string_job');

        $rules = [
            'player_id' => 'nullable|exists:players,id',
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'weight' => 'nullable|numeric|min:200|max:400',
            'swing_weight' => 'nullable|integer|min:250|max:400',
            'string_pattern' => 'nullable|string|max:50',
            'grip_size' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'add_string_job' => 'nullable|boolean',
            'stringing_date' => 'required_if:add_string_job,true|date',
            'time_played' => 'nullable|numeric|min:0',
            'string_notes' => 'nullable|string',
        ];

        if ($addStringJob) {
            $rules['mains_brand'] = 'required|string|max:255';
            $rules['mains_model'] = 'nullable|string|max:255';
            $rules['mains_gauge'] = 'nullable|string|max:50';
            $rules['mains_tension'] = 'required|numeric|min:30|max:80';
            $rules['crosses_brand'] = 'required|string|max:255';
            $rules['crosses_model'] = 'nullable|string|max:255';
            $rules['crosses_gauge'] = 'nullable|string|max:50';
            $rules['crosses_tension'] = 'required|numeric|min:30|max:80';
        }

        $validated = $request->validate($rules);

        // Create the racket
        $racket = Racket::create([
            'player_id' => $validated['player_id'] ?? null,
            'name' => $validated['name'],
            'brand' => $validated['brand'],
            'model' => $validated['model'],
            'weight' => $validated['weight'] ?? null,
            'swing_weight' => $validated['swing_weight'] ?? null,
            'string_pattern' => $validated['string_pattern'] ?? null,
            'grip_size' => $validated['grip_size'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create initial string job if requested
        if ($addStringJob) {
            StringJob::create([
                'racket_id' => $racket->id,
                'mains_brand' => $validated['mains_brand'],
                'mains_model' => $validated['mains_model'] ?? null,
                'mains_gauge' => $validated['mains_gauge'] ?? null,
                'mains_tension' => $validated['mains_tension'],
                'crosses_brand' => $validated['crosses_brand'],
                'crosses_model' => $validated['crosses_model'] ?? null,
                'crosses_gauge' => $validated['crosses_gauge'] ?? null,
                'crosses_tension' => $validated['crosses_tension'],
                'stringing_date' => $validated['stringing_date'],
                'time_played' => $validated['time_played'] ?? 0,
                'notes' => $validated['string_notes'] ?? null,
                'is_current' => true,
            ]);
        }

        return redirect()->route('rackets.index')->with('success', 'Racket created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Racket $racket)
    {
        $racket->load(['player', 'currentStringJob', 'stringJobs']);

        return view('rackets.show', compact('racket'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Racket $racket)
    {
        $players = Player::orderBy('last_name')->orderBy('first_name')->get();
        return view('rackets.edit', compact('racket', 'players'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Racket $racket)
    {
        $validated = $request->validate([
            'player_id' => 'nullable|exists:players,id',
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'weight' => 'nullable|numeric|min:200|max:400',
            'swing_weight' => 'nullable|integer|min:250|max:400',
            'string_pattern' => 'nullable|string|max:50',
            'grip_size' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $racket->update($validated);

        return redirect()->route('rackets.show', $racket)->with('success', 'Racket updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Racket $racket)
    {
        $racketName = $racket->name;

        // String jobs will be automatically deleted due to cascade delete
        $racket->delete();

        return redirect()->route('rackets.index')->with('success', "Racket '{$racketName}' has been deleted successfully.");
    }
}
