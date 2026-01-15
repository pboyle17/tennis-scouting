<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StringJob;
use App\Models\Racket;

class StringJobController extends Controller
{
    /**
     * Show the form for creating a new string job for a racket.
     */
    public function create(Racket $racket)
    {
        $racket->load('currentStringJob');
        return view('string-jobs.create', compact('racket'));
    }

    /**
     * Store a newly created string job.
     */
    public function store(Request $request, Racket $racket)
    {
        $validated = $request->validate([
            'mains_brand' => 'required|string|max:255',
            'mains_model' => 'nullable|string|max:255',
            'mains_gauge' => 'nullable|string|max:50',
            'mains_tension' => 'required|numeric|min:30|max:80',
            'crosses_brand' => 'required|string|max:255',
            'crosses_model' => 'nullable|string|max:255',
            'crosses_gauge' => 'nullable|string|max:50',
            'crosses_tension' => 'required|numeric|min:30|max:80',
            'stringing_date' => 'required|date',
            'time_played' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Mark all existing string jobs for this racket as not current
        StringJob::where('racket_id', $racket->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Create the new string job as current
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
            'notes' => $validated['notes'] ?? null,
            'is_current' => true,
        ]);

        return redirect()->route('rackets.show', $racket)->with('success', 'String job added successfully!');
    }

    /**
     * Show the form for editing a string job.
     */
    public function edit(StringJob $stringJob)
    {
        $stringJob->load('racket');
        return view('string-jobs.edit', compact('stringJob'));
    }

    /**
     * Update the specified string job.
     */
    public function update(Request $request, StringJob $stringJob)
    {
        $validated = $request->validate([
            'mains_brand' => 'required|string|max:255',
            'mains_model' => 'nullable|string|max:255',
            'mains_gauge' => 'nullable|string|max:50',
            'mains_tension' => 'required|numeric|min:30|max:80',
            'crosses_brand' => 'required|string|max:255',
            'crosses_model' => 'nullable|string|max:255',
            'crosses_gauge' => 'nullable|string|max:50',
            'crosses_tension' => 'required|numeric|min:30|max:80',
            'stringing_date' => 'required|date',
            'time_played' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $stringJob->update([
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
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('rackets.show', $stringJob->racket)->with('success', 'String job updated successfully!');
    }

    /**
     * Remove the specified string job.
     */
    public function destroy(StringJob $stringJob)
    {
        $racket = $stringJob->racket;
        $stringJob->delete();

        return redirect()->route('rackets.show', $racket)->with('success', 'String job deleted successfully.');
    }

    /**
     * Mark a string job as the current one (and unmark all others for the same racket).
     */
    public function setCurrent(StringJob $stringJob)
    {
        // Unmark all other string jobs for this racket
        StringJob::where('racket_id', $stringJob->racket_id)
            ->where('id', '!=', $stringJob->id)
            ->update(['is_current' => false]);

        // Mark this one as current
        $stringJob->update(['is_current' => true]);

        return redirect()->route('rackets.show', $stringJob->racket)->with('success', 'String job marked as current.');
    }

    /**
     * Add playing time to a string job.
     */
    public function addTime(Request $request, StringJob $stringJob)
    {
        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.1|max:100',
        ]);

        $stringJob->time_played = $stringJob->time_played + $validated['hours'];
        $stringJob->save();

        return redirect()->route('rackets.show', $stringJob->racket)->with('success', "Added {$validated['hours']} hours to string job.");
    }
}
