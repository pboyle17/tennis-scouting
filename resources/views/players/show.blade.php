@extends('layouts.app')

@section('title', $player->first_name . ' ' . $player->last_name)

@section('content')
<div class="container mx-auto p-6">
    <!-- Player Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    {{ $player->first_name }} {{ $player->last_name }}
                </h1>

                <!-- Teams -->
                @if($player->teams->count() > 0)
                    <div class="mb-4">
                        <span class="text-sm font-semibold text-gray-600">Teams:</span>
                        @foreach($player->teams as $team)
                            <a href="{{ route('teams.show', $team->id) }}" class="inline-block bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full mr-2 mb-2 hover:bg-blue-200 transition">
                                {{ $team->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            @env('local')
            <div class="flex space-x-2">
                <a href="{{ route('players.edit', $player->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    Edit Player
                </a>
            </div>
            @endenv
        </div>

        <!-- Current Ratings -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-600 mb-1">UTR Singles</div>
                <div class="text-2xl font-bold text-blue-600">
                    {{ $player->utr_singles_rating ? number_format($player->utr_singles_rating, 2) : 'N/A' }}
                    @if($player->utr_singles_reliable)
                        <span class="text-green-600 text-lg" title="100% Reliable">✓</span>
                    @endif
                </div>
                @if($player->utr_singles_updated_at)
                    <div class="text-xs text-gray-500 mt-1">
                        Updated: {{ $player->utr_singles_updated_at->format('M d, Y') }}
                    </div>
                @endif
            </div>

            <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-600 mb-1">UTR Doubles</div>
                <div class="text-2xl font-bold text-purple-600">
                    {{ $player->utr_doubles_rating ? number_format($player->utr_doubles_rating, 2) : 'N/A' }}
                    @if($player->utr_doubles_reliable)
                        <span class="text-green-600 text-lg" title="100% Reliable">✓</span>
                    @endif
                </div>
                @if($player->utr_doubles_updated_at)
                    <div class="text-xs text-gray-500 mt-1">
                        Updated: {{ $player->utr_doubles_updated_at->format('M d, Y') }}
                    </div>
                @endif
            </div>

            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-600 mb-1">USTA Dynamic</div>
                <div class="text-2xl font-bold text-green-600">
                    {{ $player->USTA_dynamic_rating ? number_format($player->USTA_dynamic_rating, 2) : 'N/A' }}
                </div>
                @if($player->tennis_record_last_sync)
                    <div class="text-xs text-gray-500 mt-1">
                        Last synced: {{ $player->tennis_record_last_sync->format('M d, Y') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- External Profile Links -->
        <div class="flex items-center space-x-4 mt-4">
            @if($player->utr_id)
                <a href="https://app.utrsports.net/profiles/{{ $player->utr_id }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded transition">
                    <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR Profile" class="h-5 w-5 mr-2">
                    <span class="text-sm font-semibold">View UTR Profile</span>
                </a>
            @endif
            @if($player->tennis_record_link)
                <a href="{{ $player->tennis_record_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded transition">
                    <span class="text-xl mr-2">🎾</span>
                    <span class="text-sm font-semibold">View Tennis Record</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Match Statistics -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Match Statistics</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Overall Stats -->
            <div class="border rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Overall</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Matches:</span>
                        <span class="font-semibold">{{ $stats['total']['matches'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Wins:</span>
                        <span class="font-semibold text-green-600">{{ $stats['total']['wins'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Losses:</span>
                        <span class="font-semibold text-red-600">{{ $stats['total']['losses'] }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t">
                        <span class="text-sm font-semibold text-gray-700">Win %:</span>
                        <span class="font-bold text-blue-600">{{ number_format($stats['total']['win_percentage'], 1) }}%</span>
                    </div>
                </div>
            </div>

            <!-- Singles Stats -->
            <div class="border rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Singles</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Matches:</span>
                        <span class="font-semibold">{{ $stats['singles']['matches'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Wins:</span>
                        <span class="font-semibold text-green-600">{{ $stats['singles']['wins'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Losses:</span>
                        <span class="font-semibold text-red-600">{{ $stats['singles']['losses'] }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t">
                        <span class="text-sm font-semibold text-gray-700">Win %:</span>
                        <span class="font-bold text-blue-600">{{ $stats['singles']['matches'] > 0 ? number_format($stats['singles']['win_percentage'], 1) : '0.0' }}%</span>
                    </div>
                </div>
            </div>

            <!-- Doubles Stats -->
            <div class="border rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-3">Doubles</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Matches:</span>
                        <span class="font-semibold">{{ $stats['doubles']['matches'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Wins:</span>
                        <span class="font-semibold text-green-600">{{ $stats['doubles']['wins'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Losses:</span>
                        <span class="font-semibold text-red-600">{{ $stats['doubles']['losses'] }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t">
                        <span class="text-sm font-semibold text-gray-700">Win %:</span>
                        <span class="font-bold text-blue-600">{{ $stats['doubles']['matches'] > 0 ? number_format($stats['doubles']['win_percentage'], 1) : '0.0' }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Match History -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Match History</h2>

        @if($courtPlayers->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Match</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Court</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Partner / Opponent</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Result</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">My UTR</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">My USTA</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Opp UTR</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Opp USTA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @php
                            $previousUtrSingles = null;
                            $previousUtrDoubles = null;
                            $previousUsta = null;
                        @endphp
                        @foreach($courtPlayers as $courtPlayer)
                            @php
                                $match = $courtPlayer->court->tennisMatch;
                                $court = $courtPlayer->court;
                                $isHomeTeam = $courtPlayer->team_id === $match->home_team_id;
                                $opponentTeam = $isHomeTeam ? $match->awayTeam : $match->homeTeam;

                                // Get all court players for this court
                                $allCourtPlayers = $court->courtPlayers;

                                // Find teammate (same team, different player)
                                $teammate = $allCourtPlayers->first(function($cp) use ($courtPlayer, $player) {
                                    return $cp->team_id === $courtPlayer->team_id && $cp->player_id !== $player->id;
                                });

                                // Find opponents (different team)
                                $opponents = $allCourtPlayers->filter(function($cp) use ($courtPlayer) {
                                    return $cp->team_id !== $courtPlayer->team_id;
                                });

                                // Calculate rating changes
                                $utrSinglesChange = null;
                                $utrDoublesChange = null;
                                $ustaChange = null;

                                if ($previousUtrSingles !== null && $courtPlayer->utr_singles_rating !== null) {
                                    $utrSinglesChange = $courtPlayer->utr_singles_rating - $previousUtrSingles;
                                }
                                if ($previousUtrDoubles !== null && $courtPlayer->utr_doubles_rating !== null) {
                                    $utrDoublesChange = $courtPlayer->utr_doubles_rating - $previousUtrDoubles;
                                }
                                if ($previousUsta !== null && $courtPlayer->usta_dynamic_rating !== null) {
                                    $ustaChange = $courtPlayer->usta_dynamic_rating - $previousUsta;
                                }

                                // Update previous values
                                $previousUtrSingles = $courtPlayer->utr_singles_rating;
                                $previousUtrDoubles = $courtPlayer->utr_doubles_rating;
                                $previousUsta = $courtPlayer->usta_dynamic_rating;

                                // Get my team's ratings
                                if ($court->court_type === 'singles') {
                                    $myUtr = $courtPlayer->utr_singles_rating;
                                    $myUsta = $courtPlayer->usta_dynamic_rating;
                                } else {
                                    // For doubles, average player and partner ratings
                                    $myTeamUtrRatings = [];
                                    $myTeamUstaRatings = [];

                                    if ($courtPlayer->utr_doubles_rating) {
                                        $myTeamUtrRatings[] = $courtPlayer->utr_doubles_rating;
                                    }
                                    if ($courtPlayer->usta_dynamic_rating) {
                                        $myTeamUstaRatings[] = $courtPlayer->usta_dynamic_rating;
                                    }

                                    if ($teammate) {
                                        if ($teammate->utr_doubles_rating) {
                                            $myTeamUtrRatings[] = $teammate->utr_doubles_rating;
                                        }
                                        if ($teammate->usta_dynamic_rating) {
                                            $myTeamUstaRatings[] = $teammate->usta_dynamic_rating;
                                        }
                                    }

                                    $myUtr = !empty($myTeamUtrRatings) ? array_sum($myTeamUtrRatings) / count($myTeamUtrRatings) : null;
                                    $myUsta = !empty($myTeamUstaRatings) ? array_sum($myTeamUstaRatings) / count($myTeamUstaRatings) : null;
                                }

                                // Calculate opponent average ratings
                                $opponentUtrRatings = [];
                                $opponentUstaRatings = [];
                                foreach ($opponents as $opponent) {
                                    if ($court->court_type === 'singles' && $opponent->utr_singles_rating) {
                                        $opponentUtrRatings[] = $opponent->utr_singles_rating;
                                    } elseif ($court->court_type === 'doubles' && $opponent->utr_doubles_rating) {
                                        $opponentUtrRatings[] = $opponent->utr_doubles_rating;
                                    }
                                    if ($opponent->usta_dynamic_rating) {
                                        $opponentUstaRatings[] = $opponent->usta_dynamic_rating;
                                    }
                                }
                                $avgOpponentUtr = !empty($opponentUtrRatings) ? array_sum($opponentUtrRatings) / count($opponentUtrRatings) : null;
                                $avgOpponentUsta = !empty($opponentUstaRatings) ? array_sum($opponentUstaRatings) / count($opponentUstaRatings) : null;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $match->start_time ? $match->start_time->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('tennis-matches.show', $match->id) }}" class="text-blue-600 hover:underline">
                                        <div class="font-semibold">{{ $courtPlayer->team->name }}</div>
                                        <div class="text-gray-600">vs {{ $opponentTeam->name }}</div>
                                        @if($match->league)
                                            <div class="text-xs text-gray-500">{{ $match->league->name }}</div>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ ucfirst($court->court_type) }} #{{ $court->court_number }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    @if($court->court_type === 'doubles' && $teammate)
                                        {{-- Doubles: Show partner and opponents --}}
                                        <div class="mb-1">
                                            <span class="text-xs text-gray-500">Partner:</span>
                                            <a href="{{ route('players.show', $teammate->player_id) }}" class="text-blue-600 hover:underline font-semibold">
                                                {{ $teammate->player->first_name }} {{ $teammate->player->last_name }}
                                            </a>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">vs</span>
                                            @foreach($opponents as $index => $opponent)
                                                @if($index > 0) / @endif
                                                <a href="{{ route('players.show', $opponent->player_id) }}" class="text-red-600 hover:underline">
                                                    {{ $opponent->player->first_name }} {{ $opponent->player->last_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @elseif($court->court_type === 'singles')
                                        {{-- Singles: Show opponent only --}}
                                        <div>
                                            <span class="text-xs text-gray-500">vs</span>
                                            @foreach($opponents as $opponent)
                                                <a href="{{ route('players.show', $opponent->player_id) }}" class="text-red-600 hover:underline font-semibold">
                                                    {{ $opponent->player->first_name }} {{ $opponent->player->last_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                {{-- Score --}}
                                <td class="px-4 py-3 text-sm">
                                    @if($court->courtSets && $court->courtSets->count() > 0)
                                        @foreach($court->courtSets->sortBy('set_number') as $set)
                                            <div class="whitespace-nowrap">
                                                @php
                                                    $myScore = $isHomeTeam ? $set->home_score : $set->away_score;
                                                    $oppScore = $isHomeTeam ? $set->away_score : $set->home_score;
                                                @endphp
                                                <span class="{{ $myScore > $oppScore ? 'font-semibold text-green-600' : 'text-gray-700' }}">{{ $myScore }}</span>
                                                <span class="text-gray-700">-</span>
                                                <span class="{{ $oppScore > $myScore ? 'font-semibold text-red-600' : 'text-gray-700' }}">{{ $oppScore }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                {{-- Result --}}
                                <td class="px-4 py-3 text-sm">
                                    @if($courtPlayer->won)
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded font-semibold">Win</span>
                                    @else
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded font-semibold">Loss</span>
                                    @endif
                                </td>
                                {{-- My UTR --}}
                                <td class="px-4 py-3 text-sm">
                                    @if($myUtr)
                                        <div>{{ number_format($myUtr, 2) }}</div>
                                        @if($court->court_type === 'doubles' && $teammate)
                                            <div class="text-xs text-gray-500">avg</div>
                                        @endif
                                        @php
                                            $utrChange = null;
                                            if ($court->court_type === 'singles' && $previousUtrSingles !== null && $courtPlayer->utr_singles_rating !== null) {
                                                $utrChange = $courtPlayer->utr_singles_rating - $previousUtrSingles;
                                            } elseif ($court->court_type === 'doubles' && $previousUtrDoubles !== null && $courtPlayer->utr_doubles_rating !== null) {
                                                $utrChange = $courtPlayer->utr_doubles_rating - $previousUtrDoubles;
                                            }
                                        @endphp
                                        @if($utrChange !== null)
                                            <div class="text-xs {{ $utrChange > 0 ? 'text-green-600' : ($utrChange < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                                {{ $utrChange > 0 ? '+' : '' }}{{ number_format($utrChange, 2) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                {{-- My USTA --}}
                                <td class="px-4 py-3 text-sm">
                                    @if($myUsta)
                                        <div>{{ number_format($myUsta, 2) }}</div>
                                        @if($court->court_type === 'doubles' && $teammate)
                                            <div class="text-xs text-gray-500">avg</div>
                                        @endif
                                        @if($ustaChange !== null)
                                            <div class="text-xs {{ $ustaChange > 0 ? 'text-green-600' : ($ustaChange < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                                {{ $ustaChange > 0 ? '+' : '' }}{{ number_format($ustaChange, 2) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                {{-- Opponent UTR --}}
                                <td class="px-4 py-3 text-sm">
                                    @if($avgOpponentUtr)
                                        <div>{{ number_format($avgOpponentUtr, 2) }}</div>
                                        @if(count($opponentUtrRatings) > 1)
                                            <div class="text-xs text-gray-500">avg</div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                {{-- Opponent USTA --}}
                                <td class="px-4 py-3 text-sm">
                                    @if($avgOpponentUsta)
                                        <div>{{ number_format($avgOpponentUsta, 2) }}</div>
                                        @if(count($opponentUstaRatings) > 1)
                                            <div class="text-xs text-gray-500">avg</div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                No match history available for this player.
            </div>
        @endif
    </div>
</div>
@endsection
