<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    /**
     * Remove the specified court from storage.
     */
    public function destroy(Court $court)
    {
        $matchId = $court->tennis_match_id;
        $court->delete();

        return redirect()->route('tennis-matches.show', $matchId)
            ->with('success', 'Court deleted successfully.');
    }
}
