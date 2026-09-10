<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Http\Controllers\Controller;
use App\Jobs\UpdateUtrRatingsJob;
use App\Jobs\FetchMissingUtrIdsJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
      $sortField = $request->get('sort', 'utr_singles_rating');
      $sortDirection = $request->get('direction', 'desc');

      $players = Player::with('teams')->orderBy($sortField, $sortDirection)->get();

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
    public function show(Player $player)
    {
        // Load player with teams and their leagues
        $player->load('teams.league');

        // Get all court player records for this player (match results)
        $courtPlayers = \App\Models\CourtPlayer::where('player_id', $player->id)
            ->with([
                'court.tennisMatch.homeTeam',
                'court.tennisMatch.awayTeam',
                'court.tennisMatch.league',
                'court.courtPlayers.player',
                'court.courtSets',
                'team.league'
            ])
            ->get()
            ->sortByDesc(function($courtPlayer) {
                return $courtPlayer->court->tennisMatch->start_time ?? $courtPlayer->court->tennisMatch->created_at;
            });

        // Calculate match statistics
        $totalMatches = $courtPlayers->count();
        $wins = $courtPlayers->where('won', true)->count();
        $losses = $courtPlayers->where('won', false)->count();
        $winPercentage = $totalMatches > 0 ? ($wins / $totalMatches) * 100 : 0;

        // Group matches by court type for statistics
        $singlesMatches = $courtPlayers->filter(function($cp) {
            return $cp->court->court_type === 'singles';
        });
        $doublesMatches = $courtPlayers->filter(function($cp) {
            return $cp->court->court_type === 'doubles';
        });

        $singlesWins = $singlesMatches->where('won', true)->count();
        $singlesLosses = $singlesMatches->where('won', false)->count();
        $singlesWinPercentage = $singlesMatches->count() > 0 ? ($singlesWins / $singlesMatches->count()) * 100 : 0;

        $doublesWins = $doublesMatches->where('won', true)->count();
        $doublesLosses = $doublesMatches->where('won', false)->count();
        $doublesWinPercentage = $doublesMatches->count() > 0 ? ($doublesWins / $doublesMatches->count()) * 100 : 0;

        $stats = [
            'total' => [
                'matches' => $totalMatches,
                'wins' => $wins,
                'losses' => $losses,
                'win_percentage' => $winPercentage
            ],
            'singles' => [
                'matches' => $singlesMatches->count(),
                'wins' => $singlesWins,
                'losses' => $singlesLosses,
                'win_percentage' => $singlesWinPercentage
            ],
            'doubles' => [
                'matches' => $doublesMatches->count(),
                'wins' => $doublesWins,
                'losses' => $doublesLosses,
                'win_percentage' => $doublesWinPercentage
            ]
        ];

        $ratingSnapshots = \App\Models\PlayerRatingSnapshot::where('player_id', $player->id)
            ->orderBy('snapshot_date')
            ->get(['snapshot_date', 'utr_singles_rating', 'utr_doubles_rating', 'usta_dynamic_rating']);

        $matchRatingPoints = $courtPlayers
            ->filter(fn($cp) => $cp->court->tennisMatch->start_time !== null
                && ($cp->utr_singles_rating !== null || $cp->utr_doubles_rating !== null || $cp->usta_dynamic_rating !== null))
            ->map(fn($cp) => [
                'date'                => \Carbon\Carbon::parse($cp->court->tennisMatch->start_time)->toDateString(),
                'utr_singles_rating'  => $cp->utr_singles_rating,
                'utr_doubles_rating'  => $cp->utr_doubles_rating,
                'usta_dynamic_rating' => $cp->usta_dynamic_rating,
                'opponents'           => $cp->court->courtPlayers
                    ->where('team_id', '!=', $cp->team_id)
                    ->map(fn($opp) => [
                        'name'               => $opp->player->first_name . ' ' . $opp->player->last_name,
                        'utr_singles_rating' => $opp->utr_singles_rating,
                        'utr_doubles_rating' => $opp->utr_doubles_rating,
                    ])
                    ->values()
                    ->all(),
            ])
            ->sortBy('date')
            ->unique('date')
            ->values();

        return view('players.show', compact('player', 'courtPlayers', 'stats', 'ratingSnapshots', 'matchRatingPoints'));
    }

    public function headToHead(Request $request, Player $player)
    {
        $allPlayers = Player::orderBy('last_name')->orderBy('first_name')
            ->where('id', '!=', $player->id)
            ->get(['id', 'first_name', 'last_name', 'utr_singles_rating', 'utr_doubles_rating', 'USTA_dynamic_rating']);

        $opponent      = null;
        $directMatches = collect();
        $commonOpponents = collect();

        if ($opponentId = $request->get('opponent')) {
            $opponent = Player::findOrFail($opponentId);

            // Court appearances keyed by court_id => team_id for each player
            $aCourts = \App\Models\CourtPlayer::where('player_id', $player->id)->get(['court_id', 'team_id'])->keyBy('court_id');
            $bCourts = \App\Models\CourtPlayer::where('player_id', $opponent->id)->get(['court_id', 'team_id'])->keyBy('court_id');

            // Courts shared by both, on opposing teams
            $sharedCourtIds = $aCourts->keys()->intersect($bCourts->keys())
                ->filter(fn($id) => $aCourts[$id]->team_id !== $bCourts[$id]->team_id)
                ->values();

            $directMatches = \App\Models\Court::whereIn('id', $sharedCourtIds)
                ->with(['tennisMatch.homeTeam', 'tennisMatch.awayTeam', 'courtPlayers.player', 'courtSets'])
                ->get()
                ->filter(fn($c) => $c->home_score !== null)
                ->sortByDesc(fn($c) => $c->tennisMatch->start_time)
                ->map(fn($c) => [
                    'court'       => $c,
                    'player_cp'   => $c->courtPlayers->firstWhere('player_id', $player->id),
                    'opponent_cp' => $c->courtPlayers->firstWhere('player_id', $opponent->id),
                ])
                ->values();

            // Opponents of player A (opposing team, excluding player B)
            $aOpponentIds = \App\Models\CourtPlayer::whereIn('court_id', $aCourts->keys())
                ->whereNotIn('player_id', [$player->id, $opponent->id])
                ->get(['court_id', 'player_id', 'team_id'])
                ->filter(fn($cp) => $aCourts->has($cp->court_id) && $cp->team_id !== $aCourts[$cp->court_id]->team_id)
                ->pluck('player_id')->unique();

            // Opponents of player B (opposing team, excluding player A)
            $bOpponentIds = \App\Models\CourtPlayer::whereIn('court_id', $bCourts->keys())
                ->whereNotIn('player_id', [$opponent->id, $player->id])
                ->get(['court_id', 'player_id', 'team_id'])
                ->filter(fn($cp) => $bCourts->has($cp->court_id) && $cp->team_id !== $bCourts[$cp->court_id]->team_id)
                ->pluck('player_id')->unique();

            $commonIds = $aOpponentIds->intersect($bOpponentIds)->values();

            if ($commonIds->isNotEmpty()) {
                $commonCpRows = \App\Models\CourtPlayer::whereIn('player_id', $commonIds)
                    ->get(['court_id', 'player_id', 'team_id', 'won']);

                // For each common opponent, collect court IDs where player A or B faced them
                $aVsCourtIds = []; // commonPlayerId => [courtId, ...]
                $bVsCourtIds = [];
                foreach ($commonCpRows as $cp) {
                    $cid = $cp->court_id;
                    $pid = $cp->player_id;
                    if ($aCourts->has($cid) && $aCourts[$cid]->team_id !== $cp->team_id) {
                        $aVsCourtIds[$pid][] = $cid;
                    }
                    if ($bCourts->has($cid) && $bCourts[$cid]->team_id !== $cp->team_id) {
                        $bVsCourtIds[$pid][] = $cid;
                    }
                }

                // Bulk load all those courts
                $allCommonCourtIds = array_unique(array_merge(
                    ...array_values($aVsCourtIds),
                    ...array_values($bVsCourtIds),
                ));

                $commonCourts = \App\Models\Court::whereIn('id', $allCommonCourtIds)
                    ->with(['tennisMatch', 'courtPlayers.player', 'courtSets'])
                    ->get()
                    ->filter(fn($c) => $c->home_score !== null)
                    ->keyBy('id');

                $matchesVs = function (int $subjectId, array $courtIds) use ($commonCourts, $player, $opponent) {
                    return collect($courtIds)
                        ->map(fn($cid) => $commonCourts->get($cid))
                        ->filter()
                        ->sortByDesc(fn($c) => $c->tennisMatch->start_time)
                        ->map(fn($c) => [
                            'court'      => $c,
                            'subject_cp' => $c->courtPlayers->firstWhere('player_id', $subjectId),
                        ])
                        ->values();
                };

                $commonOpponents = Player::whereIn('id', $commonIds)->orderBy('last_name')->orderBy('first_name')
                    ->get(['id', 'first_name', 'last_name', 'utr_singles_rating'])
                    ->map(function ($opp) use ($player, $opponent, $aVsCourtIds, $bVsCourtIds, $matchesVs) {
                        $aMatches = $matchesVs($player->id,   $aVsCourtIds[$opp->id] ?? []);
                        $bMatches = $matchesVs($opponent->id, $bVsCourtIds[$opp->id] ?? []);
                        $aWins = $aMatches->where('subject_cp.won', true)->count();
                        $bWins = $bMatches->where('subject_cp.won', true)->count();
                        return [
                            'player'          => $opp,
                            'player_record'   => ['wins' => $aWins,   'losses' => $aMatches->count() - $aWins],
                            'opponent_record' => ['wins' => $bWins,   'losses' => $bMatches->count() - $bWins],
                            'player_matches'  => $aMatches,
                            'opponent_matches'=> $bMatches,
                        ];
                    });
            }
        }

        return view('players.head-to-head', compact('player', 'opponent', 'allPlayers', 'directMatches', 'commonOpponents'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Player $player)
    {
        $player->load('teams');
        $returnUrl = $request->query('return_url');
        return view('players.edit', compact('player', 'returnUrl'));
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
            'tennis_record_link' => 'nullable|string|max:500',
            'utr_singles_rating' => 'nullable|numeric',
            'utr_doubles_rating' => 'nullable|numeric',
            'USTA_rating' => 'nullable|numeric',
            'USTA_dynamic_rating' => 'nullable|numeric',
            'tennis_number_singles_rating' => 'nullable|numeric',
            'tennis_number_doubles_rating' => 'nullable|numeric',
            'tennis_number_link' => 'nullable|url|max:500',
        ]);

        // Check if UTR ratings changed and set updated timestamps
        if (isset($validated['utr_singles_rating']) && $validated['utr_singles_rating'] != $player->utr_singles_rating) {
            $validated['utr_singles_updated_at'] = now();
        }
        if (isset($validated['utr_doubles_rating']) && $validated['utr_doubles_rating'] != $player->utr_doubles_rating) {
            $validated['utr_doubles_updated_at'] = now();
        }
        if (isset($validated['USTA_rating']) && $validated['USTA_rating'] != $player->USTA_rating) {
            $validated['usta_rating_updated_at'] = now();
        }

        $player->update($validated);

        // Check if there's a return URL, otherwise go to players index
        $returnUrl = $request->input('return_url');

        if ($returnUrl) {
            return redirect($returnUrl)->with('success', 'Player updated successfully!');
        }

        return redirect()->route('players.index')->with('success', 'Player updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Player $player)
    {
        $playerName = $player->first_name . ' ' . $player->last_name;

        // Detach from all teams
        $player->teams()->detach();

        // Delete the player
        $player->delete();

        return redirect()->route('players.index')->with('success', "Player '{$playerName}' has been deleted successfully.");
    }

    public function updateUtrRatings()
    {
      $jobKey = 'utr_update_' . uniqid();
      UpdateUtrRatingsJob::dispatch([], $jobKey);

      return redirect()->route('players.index')->with([
          'status' => '✅ UTR update job has been dispatched!',
          'utr_job_key' => $jobKey
      ]);
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

    public function getUtrUpdateProgress(Request $request)
    {
        $jobKey = $request->get('job_key');
        if (!$jobKey) {
            return response()->json(['error' => 'Job key required'], 400);
        }

        $progress = Cache::get($jobKey);
        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($progress);
    }

    public function searchUtrId(Request $request, Player $player)
    {
        try {
            $utrService = app(\App\Services\UtrService::class);
            $playerName = $player->first_name . ' ' . $player->last_name;

            Log::info("Searching UTR ID for player: {$playerName}", [
                'player_id' => $player->id,
                'first_name' => $player->first_name,
                'last_name' => $player->last_name
            ]);

            $searchResults = $utrService->searchPlayers($playerName, 10);

            // Handle nested structure
            $hits = $searchResults['players']['hits'] ?? $searchResults['hits'] ?? [];

            Log::info("UTR Search Results for {$playerName}", [
                'player_id' => $player->id,
                'total_hits' => count($hits),
                'results' => $searchResults
            ]);

            // Log each result individually for easier reading
            if (count($hits) > 0) {
                foreach ($hits as $index => $hit) {
                    $source = $hit['source'] ?? [];
                    Log::info("UTR Search Result #{$index} for {$playerName}", [
                        'player_id' => $player->id,
                        'utr_id' => $source['id'] ?? 'N/A',
                        'name' => ($source['firstName'] ?? '') . ' ' . ($source['lastName'] ?? ''),
                        'location' => $source['location']['display'] ?? 'N/A',
                        'singles_utr' => $source['singlesUtr'] ?? 0,
                        'doubles_utr' => $source['doublesUtr'] ?? 0,
                        'gender' => $source['gender'] ?? 'N/A',
                        'full_data' => $source
                    ]);
                }
            }

            $returnUrl = $request->query('return_url');
            $redirectUrl = $returnUrl ?: route('players.edit', $player->id);

            return redirect($redirectUrl)->with([
                'status' => "Found " . count($hits) . " UTR profile(s) for {$playerName}",
                'utr_search_results' => $searchResults
            ]);

        } catch (\Exception $e) {
            Log::error("UTR search failed for player {$player->id}: " . $e->getMessage());

            $returnUrl = $request->query('return_url');
            $redirectUrl = $returnUrl ?: route('players.edit', $player->id);

            return redirect($redirectUrl)->with('error', 'Failed to search for UTR ID: ' . $e->getMessage());
        }
    }

    public function syncTrProfile(Request $request, Player $player)
    {
        try {
            // Check if player has a Tennis Record link
            if (!$player->tennis_record_link) {
                $returnUrl = $request->query('return_url');
                $redirectUrl = route('players.edit', $player->id);
                if ($returnUrl) {
                    $redirectUrl .= '?return_url=' . urlencode($returnUrl);
                }
                return redirect($redirectUrl)->with('error', 'Player does not have a Tennis Record link.');
            }

            Log::info("Syncing Tennis Record profile for player: {$player->first_name} {$player->last_name}", [
                'player_id' => $player->id,
                'tennis_record_link' => $player->tennis_record_link
            ]);

            // Fetch the Tennis Record page
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->connectTimeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])
                ->get($player->tennis_record_link);

            if (!$response->successful()) {
                Log::warning("Failed to fetch Tennis Record page for player {$player->id}", [
                    'player_id' => $player->id,
                    'status' => $response->status(),
                    'link' => $player->tennis_record_link
                ]);

                $returnUrl = $request->query('return_url');
                $redirectUrl = route('players.edit', $player->id);
                if ($returnUrl) {
                    $redirectUrl .= '?return_url=' . urlencode($returnUrl);
                }
                return redirect($redirectUrl)->with('error', 'Failed to fetch Tennis Record page.');
            }

            $html = $response->body();

            // Try multiple patterns to find USTA rating
            $patterns = [
                // Pattern for rating in bold span (Tennis Record format) - most common
                '/<span[^>]*font-weight:\s*bold[^>]*>([3-5]\.\d+)\s+([SCMTA])<\/span>/i',
                '/Rating:\s*<[^>]+>([3-5]\.\d+)([SCMTA])<\/[^>]+>/i',
                '/USTA\s+Rating:\s*([3-5]\.\d+)([SCMTA])/i',
                '/<td[^>]*>Rating<\/td>\s*<td[^>]*>([3-5]\.\d+)([SCMTA])<\/td>/i',
                // Generic pattern for rating directly in any table cell (no label)
                '/<td[^>]*>\s*([3-5]\.\d+)\s*([SCMTA])\s*<\/td>/i',
            ];

            $ratingFound = false;
            $rating = null;
            $ratingType = null;

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $rating = floatval($matches[1]);
                    $ratingType = strtoupper($matches[2]);
                    $ratingFound = true;
                    break;
                }
            }

            if ($ratingFound && $rating >= 3.0 && $rating <= 5.0 && in_array($ratingType, ['S', 'C', 'A', 'M', 'T'])) {
                $player->USTA_rating = $rating;
                $player->usta_rating_type = $ratingType;
                $player->usta_rating_updated_at = now();
                $player->save();

                Log::info("Successfully synced Tennis Record profile for player {$player->id}", [
                    'player_id' => $player->id,
                    'player_name' => $player->first_name . ' ' . $player->last_name,
                    'usta_rating' => $rating,
                    'usta_rating_type' => $ratingType
                ]);

                $ratingTypeNames = [
                    'S' => 'Self-rated',
                    'C' => 'Computer rated',
                    'A' => 'Appeal',
                    'M' => 'Medical',
                    'T' => 'Tournament'
                ];

                $returnUrl = $request->query('return_url');
                $redirectUrl = route('players.edit', $player->id);
                if ($returnUrl) {
                    $redirectUrl .= '?return_url=' . urlencode($returnUrl);
                }

                return redirect($redirectUrl)->with('success', "Successfully synced USTA rating: {$rating} ({$ratingTypeNames[$ratingType]})");
            } else {
                Log::warning("Could not find valid USTA rating on Tennis Record page for player {$player->id}", [
                    'player_id' => $player->id,
                    'player_name' => $player->first_name . ' ' . $player->last_name,
                    'link' => $player->tennis_record_link
                ]);

                $returnUrl = $request->query('return_url');
                $redirectUrl = route('players.edit', $player->id);
                if ($returnUrl) {
                    $redirectUrl .= '?return_url=' . urlencode($returnUrl);
                }
                return redirect($redirectUrl)->with('error', 'Could not find valid USTA rating on Tennis Record page.');
            }

        } catch (\Exception $e) {
            Log::error("Error syncing Tennis Record profile for player {$player->id}: " . $e->getMessage(), [
                'player_id' => $player->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $returnUrl = $request->query('return_url');
            $redirectUrl = route('players.edit', $player->id);
            if ($returnUrl) {
                $redirectUrl .= '?return_url=' . urlencode($returnUrl);
            }
            return redirect($redirectUrl)->with('error', 'Failed to sync Tennis Record profile: ' . $e->getMessage());
        }
    }
}
