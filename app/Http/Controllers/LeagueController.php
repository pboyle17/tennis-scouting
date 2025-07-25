<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\League;

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
    public function show(string $id)
    {
        //
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
    public function destroy(string $id)
    {
        //
    }
}
