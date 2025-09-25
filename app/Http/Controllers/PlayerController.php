<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Http\Controllers\Controller;
use App\Jobs\UpdateUtrRatingsJob;
use App\Jobs\FetchMissingUtrIdsJob;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
      $sortField = $request->get('sort', 'utr_singles_rating');
      $sortDirection = $request->get('direction', 'desc');

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
            'utr_singles_rating' => 'nullable|numeric',
            'utr_doubles_rating' => 'nullable|numeric',
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

    public function updateUtr(Player $player)
    {
      UpdateUtrRatingsJob::dispatchSync([$player->utr_id]);

      return back();
    }

    public function fetchMissingUtrIds()
    {
        // Check if a job is already running
        if (Cache::has('utr_search_running')) {
            $message = '⏳ UTR ID search is already in progress. Please wait for it to complete.';
            return redirect()->route('players.index')->with('error', $message);
        }

        // Mark job as running
        Cache::put('utr_search_running', true, 300); // 5 minutes

        $job = FetchMissingUtrIdsJob::dispatch();

        return redirect()->route('players.index')->with([
            'status' => '🔍 UTR ID search job has been dispatched!',
            'search_job_id' => $job->getJobId()
        ]);
    }

    public function getUtrSearchProgress(Request $request)
    {
        $jobId = $request->get('job_id');
        if (!$jobId) {
            return response()->json(['error' => 'Job ID required'], 400);
        }

        $progress = Cache::get("utr_search_progress_{$jobId}");
        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($progress);
    }
}
