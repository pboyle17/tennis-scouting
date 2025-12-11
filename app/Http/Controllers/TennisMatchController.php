<?php

namespace App\Http\Controllers;

use App\Models\TennisMatch;
use App\Models\Team;
use Illuminate\Http\Request;

class TennisMatchController extends Controller
{
    /**
     * Show the form for editing the specified match.
     */
    public function edit(TennisMatch $match)
    {
        $match->load(['homeTeam', 'awayTeam', 'league']);

        // Get all teams in the league for the dropdowns
        $teams = $match->league->teams;

        return view('tennis-matches.edit', compact('match', 'teams'));
    }

    /**
     * Update the specified match in storage.
     */
    public function update(Request $request, TennisMatch $match)
    {
        $validated = $request->validate([
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id|different:home_team_id',
            'start_time' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'home_score' => 'nullable|integer|min:0',
            'away_score' => 'nullable|integer|min:0',
        ]);

        $match->update($validated);

        // Redirect back to the team page
        return redirect()->route('teams.show', $match->homeTeam->id)
            ->with('success', 'Match updated successfully.');
    }

    /**
     * Remove the specified match from storage.
     */
    public function destroy(TennisMatch $match)
    {
        $teamId = $match->home_team_id;
        $match->delete();

        return redirect()->route('teams.show', $teamId)
            ->with('success', 'Match deleted successfully.');
    }

    /**
     * Update the match score.
     */
    public function updateScore(Request $request, TennisMatch $match)
    {
        $validated = $request->validate([
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
        ]);

        $match->update($validated);

        // Remove this conflict from cache
        if ($match->league) {
            $conflictsKey = "score_conflicts_league_{$match->league_id}";
            $conflicts = \Illuminate\Support\Facades\Cache::get($conflictsKey, []);

            // Remove the conflict for this match
            $conflicts = array_filter($conflicts, function($conflict) use ($match) {
                return $conflict['match_id'] != $match->id;
            });

            // Update cache
            if (count($conflicts) > 0) {
                \Illuminate\Support\Facades\Cache::put($conflictsKey, array_values($conflicts), 3600);
            } else {
                \Illuminate\Support\Facades\Cache::forget($conflictsKey);
            }
        }

        return redirect()->back()->with('success', 'Match score updated successfully.');
    }
}
