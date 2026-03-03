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
            @php $matchTeams = $courtPlayers->map(fn($cp) => $cp->team)->unique('id')->values(); @endphp

            {{-- Team filter --}}
            @if($matchTeams->count() > 1)
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Team:</span>
                    @foreach($matchTeams as $team)
                        <button data-team="{{ $team->id }}" class="team-filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">{{ $team->name }}</button>
                    @endforeach
                </div>
            @endif

            {{-- Court type filter --}}
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Court:</span>
                <button data-court="singles" class="court-filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">Singles</button>
                <button data-court="doubles" class="court-filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">Doubles</button>
            </div>

            {{-- Result filter --}}
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Result:</span>
                <button data-result="win" class="result-filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">Win</button>
                <button data-result="loss" class="result-filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">Loss</button>
            </div>

            {{-- Match stats --}}
            <div class="mb-4 flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm">
                <div class="flex items-baseline gap-1">
                    <span id="stat-wins" class="text-xl font-bold text-green-600">0</span>
                    <span class="text-gray-500">W</span>
                </div>
                <span class="text-gray-300 text-lg">–</span>
                <div class="flex items-baseline gap-1">
                    <span id="stat-losses" class="text-xl font-bold text-red-600">0</span>
                    <span class="text-gray-500">L</span>
                </div>
                <span class="text-gray-300 text-lg">·</span>
                <div class="flex items-baseline gap-1">
                    <span id="stat-total" class="text-xl font-bold text-gray-700">0</span>
                    <span class="text-gray-500">matches</span>
                </div>
                <span class="text-gray-300 text-lg">·</span>
                <div class="flex items-baseline gap-1">
                    <span id="stat-pct" class="text-xl font-bold text-blue-600">0.0</span>
                    <span class="text-gray-500">%</span>
                </div>
            </div>

            {{-- Mobile card view --}}
            <div class="md:hidden space-y-4">
                @foreach($courtPlayers as $courtPlayer)
                    @php
                        $match = $courtPlayer->court->tennisMatch;
                        $court = $courtPlayer->court;
                        $isHomeTeam = $courtPlayer->team_id === $match->home_team_id;
                        $opponentTeam = $isHomeTeam ? $match->awayTeam : $match->homeTeam;
                        $allCourtPlayers = $court->courtPlayers;
                        $teammate = $allCourtPlayers->first(function($cp) use ($courtPlayer, $player) {
                            return $cp->team_id === $courtPlayer->team_id && $cp->player_id !== $player->id;
                        });
                        $opponents = $allCourtPlayers->filter(function($cp) use ($courtPlayer) {
                            return $cp->team_id !== $courtPlayer->team_id;
                        });
                        if ($court->court_type === 'singles') {
                            $myUtr = $courtPlayer->utr_singles_rating;
                            $myUsta = $courtPlayer->usta_dynamic_rating;
                        } else {
                            $myTeamUtrRatings = array_filter([$courtPlayer->utr_doubles_rating, $teammate?->utr_doubles_rating ?? null]);
                            $myTeamUstaRatings = array_filter([$courtPlayer->usta_dynamic_rating, $teammate?->usta_dynamic_rating ?? null]);
                            $myUtr = count($myTeamUtrRatings) ? array_sum($myTeamUtrRatings) / count($myTeamUtrRatings) : null;
                            $myUsta = count($myTeamUstaRatings) ? array_sum($myTeamUstaRatings) / count($myTeamUstaRatings) : null;
                        }
                        $opponentUtrRatings = [];
                        $opponentUstaRatings = [];
                        foreach ($opponents as $opponent) {
                            if ($court->court_type === 'singles' && $opponent->utr_singles_rating) $opponentUtrRatings[] = $opponent->utr_singles_rating;
                            elseif ($court->court_type === 'doubles' && $opponent->utr_doubles_rating) $opponentUtrRatings[] = $opponent->utr_doubles_rating;
                            if ($opponent->usta_dynamic_rating) $opponentUstaRatings[] = $opponent->usta_dynamic_rating;
                        }
                        $avgOpponentUtr = count($opponentUtrRatings) ? array_sum($opponentUtrRatings) / count($opponentUtrRatings) : null;
                        $avgOpponentUsta = count($opponentUstaRatings) ? array_sum($opponentUstaRatings) / count($opponentUstaRatings) : null;
                    @endphp
                    <div onclick="window.location.href='{{ route('tennis-matches.show', $match->id) }}'" class="match-card block bg-gray-50 rounded-lg border border-gray-200 p-4 hover:bg-gray-100 transition cursor-pointer" data-team-id="{{ $courtPlayer->team_id }}" data-court-type="{{ $court->court_type }}" data-result="{{ $courtPlayer->won ? 'win' : 'loss' }}">
                        {{-- Date + Court --}}
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-xs text-gray-500">
                                {{ $match->start_time ? $match->start_time->format('M d, Y') : 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-600 font-medium flex items-center gap-1">
                                @if($match->tennis_record_match_link)
                                    <a href="{{ $match->tennis_record_match_link }}" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation()" class="text-base leading-none">🎾</a>
                                @endif
                                {{ ucfirst($court->court_type) }} #{{ $court->court_number }}
                            </div>
                        </div>

                        {{-- Teams --}}
                        <div class="text-sm font-semibold mb-1">
                            <a href="{{ route('teams.show', $courtPlayer->team->id) }}" onclick="event.stopPropagation()" class="text-gray-800 hover:text-blue-600">{{ $courtPlayer->team->name }}</a>
                        </div>
                        <div class="text-xs text-gray-600 mb-2">vs <a href="{{ route('teams.show', $opponentTeam->id) }}" onclick="event.stopPropagation()" class="hover:text-blue-600">{{ $opponentTeam->name }}</a></div>

                        {{-- Player matchup --}}
                        @if($court->court_type === 'singles')
                            @php $playerUtr = $courtPlayer->utr_singles_rating; @endphp
                            <div class="text-xs mb-3 flex flex-wrap items-center gap-x-1">
                                <span class="font-medium text-blue-600">{{ $player->first_name }} {{ $player->last_name }}</span>
                                @if($playerUtr)<span class="text-gray-400">({{ number_format($playerUtr, 2) }})</span>@endif
                                <span class="text-gray-400">vs</span>
                                @foreach($opponents as $i => $opp)
                                    @if(!$loop->first)<span class="text-gray-400">/</span>@endif
                                    @php $oppUtr = $opp->utr_singles_rating; @endphp
                                    <a href="{{ route('players.show', $opp->player_id) }}" onclick="event.stopPropagation()" class="font-medium text-red-600 hover:underline">{{ $opp->player->first_name }} {{ $opp->player->last_name }}</a>
                                    @if($oppUtr)<span class="text-gray-400">({{ number_format($oppUtr, 2) }})</span>@endif
                                @endforeach
                            </div>
                        @else
                            @php $playerUtr = $courtPlayer->utr_doubles_rating; @endphp
                            <div class="text-xs mb-1 flex flex-wrap items-center gap-x-1">
                                <span class="font-medium text-blue-600">{{ $player->first_name }} {{ $player->last_name }}</span>
                                @if($playerUtr)<span class="text-gray-400">({{ number_format($playerUtr, 2) }})</span>@endif
                                @if($teammate)
                                    <span class="text-gray-400">/</span>
                                    <a href="{{ route('players.show', $teammate->player_id) }}" onclick="event.stopPropagation()" class="font-medium text-blue-600 hover:underline">{{ $teammate->player->first_name }} {{ $teammate->player->last_name }}</a>
                                    @if($teammate->utr_doubles_rating)<span class="text-gray-400">({{ number_format($teammate->utr_doubles_rating, 2) }})</span>@endif
                                @endif
                            </div>
                            @if($opponents->count())
                                <div class="text-xs mb-3 flex flex-wrap items-center gap-x-1">
                                    <span class="text-gray-500">vs</span>
                                    @foreach($opponents as $i => $opp)
                                        @if(!$loop->first)<span class="text-gray-400">/</span>@endif
                                        @php $oppUtr = $opp->utr_doubles_rating; @endphp
                                        <a href="{{ route('players.show', $opp->player_id) }}" onclick="event.stopPropagation()" class="font-medium text-red-600 hover:underline">{{ $opp->player->first_name }} {{ $opp->player->last_name }}</a>
                                        @if($oppUtr)<span class="text-gray-400">({{ number_format($oppUtr, 2) }})</span>@endif
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        {{-- Score + Result --}}
                        <div class="flex items-center gap-3">
                            <div class="text-sm font-semibold">
                                @if($court->courtSets && $court->courtSets->count() > 0)
                                    @foreach($court->courtSets->sortBy('set_number') as $set)
                                        @php $ms = $isHomeTeam ? $set->home_score : $set->away_score; $os = $isHomeTeam ? $set->away_score : $set->home_score; @endphp
                                        <span class="{{ $ms > $os ? 'text-green-600' : 'text-gray-700' }}">{{ $ms }}</span><span class="text-gray-400">-</span><span class="{{ $os > $ms ? 'text-red-600' : 'text-gray-700' }}">{{ $os }}</span>
                                        @if(!$loop->last) <span class="text-gray-300 mx-1">|</span> @endif
                                    @endforeach
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                            @if($courtPlayer->won !== null)
                                <span class="{{ $courtPlayer->won ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} text-xs px-2 py-0.5 rounded font-semibold">
                                    {{ $courtPlayer->won ? 'Win' : 'Loss' }}
                                </span>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- Desktop table view --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Match</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Court</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Players</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Result</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Links</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
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
                            @endphp
                            <tr class="match-row hover:bg-gray-50" data-team-id="{{ $courtPlayer->team_id }}" data-court-type="{{ $court->court_type }}" data-result="{{ $courtPlayer->won ? 'win' : 'loss' }}">
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
                                    @if($court->court_type === 'doubles')
                                        {{-- Doubles: my side then opponents --}}
                                        <div class="mb-1">
                                            <span class="font-semibold text-blue-600">{{ $player->first_name }} {{ $player->last_name }}</span>
                                            @if($courtPlayer->utr_doubles_rating)<span class="text-xs text-gray-400">({{ number_format($courtPlayer->utr_doubles_rating, 2) }})</span>@endif
                                            @if($teammate)
                                                / <a href="{{ route('players.show', $teammate->player_id) }}" class="text-blue-600 hover:underline font-semibold">{{ $teammate->player->first_name }} {{ $teammate->player->last_name }}</a>
                                                @if($teammate->utr_doubles_rating)<span class="text-xs text-gray-400">({{ number_format($teammate->utr_doubles_rating, 2) }})</span>@endif
                                            @endif
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500">vs</span>
                                            @foreach($opponents as $opponent)
                                                @if(!$loop->first) / @endif
                                                <a href="{{ route('players.show', $opponent->player_id) }}" class="text-red-600 hover:underline">{{ $opponent->player->first_name }} {{ $opponent->player->last_name }}</a>
                                                @if($opponent->utr_doubles_rating)<span class="text-xs text-gray-400">({{ number_format($opponent->utr_doubles_rating, 2) }})</span>@endif
                                            @endforeach
                                        </div>
                                    @elseif($court->court_type === 'singles')
                                        {{-- Singles: player vs opponent --}}
                                        <div>
                                            <span class="font-semibold text-blue-600">{{ $player->first_name }} {{ $player->last_name }}</span>
                                            @if($courtPlayer->utr_singles_rating)<span class="text-xs text-gray-400">({{ number_format($courtPlayer->utr_singles_rating, 2) }})</span>@endif
                                            <span class="text-xs text-gray-500 mx-1">vs</span>
                                            @foreach($opponents as $opponent)
                                                <a href="{{ route('players.show', $opponent->player_id) }}" class="text-red-600 hover:underline font-semibold">{{ $opponent->player->first_name }} {{ $opponent->player->last_name }}</a>
                                                @if($opponent->utr_singles_rating)<span class="text-xs text-gray-400">({{ number_format($opponent->utr_singles_rating, 2) }})</span>@endif
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
                                {{-- Tennis Record Link --}}
                                <td class="px-4 py-3 text-sm text-center">
                                    @if($match->tennis_record_match_link)
                                        <a href="{{ $match->tennis_record_match_link }}" target="_blank" rel="noopener noreferrer" class="text-2xl hover:opacity-70 transition-opacity" title="View on Tennis Record">
                                            🎾
                                        </a>
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

<script>
(function () {
    var activeTeams = new Set();
    var activeCourts = new Set();
    var activeResults = new Set();

    function applyFilters() {
        var wins = 0, losses = 0;
        document.querySelectorAll('.match-card').forEach(function (el) {
            var teamMatch = activeTeams.size === 0 || activeTeams.has(el.dataset.teamId);
            var courtMatch = activeCourts.size === 0 || activeCourts.has(el.dataset.courtType);
            var resultMatch = activeResults.size === 0 || activeResults.has(el.dataset.result);
            var visible = teamMatch && courtMatch && resultMatch;
            el.style.display = visible ? '' : 'none';
            if (visible) { el.dataset.result === 'win' ? wins++ : losses++; }
        });
        document.querySelectorAll('.match-row').forEach(function (el) {
            var teamMatch = activeTeams.size === 0 || activeTeams.has(el.dataset.teamId);
            var courtMatch = activeCourts.size === 0 || activeCourts.has(el.dataset.courtType);
            var resultMatch = activeResults.size === 0 || activeResults.has(el.dataset.result);
            el.style.display = (teamMatch && courtMatch && resultMatch) ? '' : 'none';
        });
        var total = wins + losses;
        document.getElementById('stat-wins').textContent = wins;
        document.getElementById('stat-losses').textContent = losses;
        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-pct').textContent = total > 0 ? (wins / total * 100).toFixed(1) : '0.0';
    }

    function toggleFilter(set, value, btn) {
        if (set.has(value)) {
            set.delete(value);
            btn.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
            btn.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
        } else {
            set.add(value);
            btn.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
            btn.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        }
        applyFilters();
    }

    document.querySelectorAll('.result-filter-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { toggleFilter(activeResults, this.dataset.result, this); });
    });

    document.querySelectorAll('.court-filter-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { toggleFilter(activeCourts, this.dataset.court, this); });
    });

    document.querySelectorAll('.team-filter-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { toggleFilter(activeTeams, this.dataset.team, this); });
    });

    applyFilters();
})();
</script>
@endsection
