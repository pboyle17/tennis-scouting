@extends('layouts.app')

@section('title', $team->name)

@section('content')
<div class="scroll-smooth" style="scroll-padding-top: 5rem;">
    <div class="container mx-auto px-4 py-6 md:p-6">
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-3xl md:text-3xl font-bold text-gray-800">
                    <a href="{{ route('teams.show', $team->id) }}" class="hover:text-blue-600 transition-colors cursor-pointer">
                        {{ $team->name }}
                    </a>
                </h1>
                <div class="flex space-x-2">
                @env('local')
                    <form method="POST" action="{{ route('teams.destroy', $team->id) }}" onsubmit="return confirm('Are you sure you want to delete this team? This will not delete the players, only remove them from this team.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded">
                            🗑️ Delete Team
                        </button>
                    </form>
                @endenv
            </div>
            </div>

            <!-- League Pill -->
            @if($team->league)
                <div>
                    <span class="text-sm font-semibold text-gray-600">League:</span>
                    <a href="{{ route('leagues.show', $team->league->id) }}" class="inline-block bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full ml-2 hover:bg-blue-200 transition">
                        {{ $team->league->name }}
                    </a>
                </div>
            @endif
        </div>

    </div>

    <!-- Section Navigation Tabs -->
    <div class="sticky top-14 z-40 bg-gray-100 shadow-sm mb-6" style="position: -webkit-sticky; position: sticky;">
        <div class="container mx-auto px-4 md:px-6">
            <div class="flex justify-center border-b border-gray-200">
                <nav class="flex space-x-8">
                    <a href="#court-averages" class="py-3 px-1 border-b-2 border-transparent hover:border-blue-500 text-gray-600 hover:text-blue-600 font-medium transition">
                        Insights
                    </a>
                    <a href="#players" class="py-3 px-1 border-b-2 border-transparent hover:border-blue-500 text-gray-600 hover:text-blue-600 font-medium transition">
                        Players
                    </a>
                    <a href="#matches" class="py-3 px-1 border-b-2 border-transparent hover:border-blue-500 text-gray-600 hover:text-blue-600 font-medium transition">
                        Matches
                    </a>
                </nav>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sections = ['court-averages', 'players', 'matches'];
            var tabLinks = {};
            sections.forEach(function (id) {
                tabLinks[id] = document.querySelector('nav a[href="#' + id + '"]');
            });

            var activeClasses = ['border-blue-500', 'text-blue-600'];
            var inactiveClasses = ['border-transparent', 'text-gray-600'];

            function setActiveTab(id) {
                sections.forEach(function (s) {
                    var el = tabLinks[s];
                    if (!el) return;
                    if (s === id) {
                        el.classList.remove(...inactiveClasses);
                        el.classList.add(...activeClasses);
                    } else {
                        el.classList.remove(...activeClasses);
                        el.classList.add(...inactiveClasses);
                    }
                });
            }

            // Set active immediately on click
            sections.forEach(function (id) {
                if (tabLinks[id]) tabLinks[id].addEventListener('click', function () { setActiveTab(id); });
            });

            var stickyTabBar = document.querySelector('.sticky.top-14');
            function updateActiveTab() {
                // If at the bottom of the page, activate the last section
                if ((window.innerHeight + window.scrollY) >= document.body.scrollHeight - 10) {
                    setActiveTab(sections[sections.length - 1]);
                    return;
                }
                var offset = stickyTabBar ? stickyTabBar.getBoundingClientRect().bottom + 4 : 110;
                var active = null;
                for (var i = sections.length - 1; i >= 0; i--) {
                    var anchor = document.getElementById(sections[i]);
                    if (anchor && anchor.getBoundingClientRect().top <= offset) {
                        active = sections[i];
                        break;
                    }
                }
                if (active) setActiveTab(active);
                else sections.forEach(function (s) {
                    var el = tabLinks[s];
                    if (el) { el.classList.remove(...activeClasses); el.classList.add(...inactiveClasses); }
                });
            }
            window.addEventListener('scroll', updateActiveTab, { passive: true });
            updateActiveTab();
        });
    </script>

    <div class="container mx-auto px-4 md:p-6">

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if(session('status'))
        <div class="bg-blue-100 text-blue-700 p-2 rounded mb-4">
            {{ session('status') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-2 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if(session('utr_search_results'))
        <div class="mb-6">
            <!-- Success notification area -->
            <div id="utr-success-notifications" class="mb-4"></div>

            @foreach(session('utr_search_results') as $searchResult)
                @php
                    $player = $searchResult['player'];
                    $results = $searchResult['results'];
                @endphp

                <div class="bg-white p-6 rounded-lg shadow mb-4" id="player-results-{{ $player['id'] }}">
                    <h3 class="text-lg font-semibold mb-4">
                        UTR ID Search Results for {{ $player['first_name'] }} {{ $player['last_name'] }}
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Location</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Singles UTR</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Doubles UTR</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">UTR ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($results as $hit)
                                    @php
                                        $source = $hit['source'] ?? [];
                                        $firstName = $source['firstName'] ?? '';
                                        $lastName = $source['lastName'] ?? '';
                                        $location = $source['location']['display'] ?? '';
                                        $singlesUtr = $source['singlesUtr'] ?? 0;
                                        $doublesUtr = $source['doublesUtr'] ?? 0;
                                        $utrId = $source['id'] ?? '';

                                        // Check if this is a single result with matching names (auto-selected)
                                        $isSingleMatch = count($results) === 1 &&
                                                        strtolower(trim($firstName)) === strtolower(trim($player['first_name'])) &&
                                                        strtolower(trim($lastName)) === strtolower(trim($player['last_name']));
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-sm">{{ $firstName }} {{ $lastName }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $location }}</td>
                                        <td class="px-4 py-2 text-sm">{{ number_format($singlesUtr, 2) }}</td>
                                        <td class="px-4 py-2 text-sm">{{ number_format($doublesUtr, 2) }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <a href="https://app.utrsports.net/profiles/{{ $utrId }}" target="_blank" class="text-blue-600 hover:underline">
                                                {{ $utrId }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            @if($isSingleMatch)
                                                <span class="bg-gray-400 text-white text-xs px-3 py-1 rounded cursor-not-allowed">
                                                    Auto-Selected
                                                </span>
                                            @else
                                                <form class="utr-selection-form" data-player-id="{{ $player['id'] }}" data-player-name="{{ $player['first_name'] }} {{ $player['last_name'] }}" data-action="{{ route('teams.setPlayerUtrData', ['team' => $team->id, 'player' => $player['id']]) }}" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="utr_id" value="{{ $utrId }}">
                                                    <input type="hidden" name="singles_utr" value="{{ $singlesUtr }}">
                                                    <input type="hidden" name="doubles_utr" value="{{ $doublesUtr }}">
                                                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-1 rounded">
                                                        Use This
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif


    @if(!empty($courtStats))
        <div id="court-averages" class="relative -top-20 invisible h-0"></div>
        <div class="max-w-4xl mx-auto mb-6 bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">Court Position Averages</h3>
            <p class="text-sm text-gray-500 mb-3 hidden md:block">Click on any row to see player details</p>
            <p class="text-sm text-gray-500 mb-3 md:hidden">Tap on a card to see player details</p>
            @if($leagueCourtStats)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-4">
                    <p class="text-sm text-gray-700">
                        <span class="font-semibold">League Comparison:</span> Numbers in parentheses show your team's difference from the league average.
                        <span class="text-green-600 font-semibold">Green (+)</span> means above average,
                        <span class="text-red-600 font-semibold">Red (-)</span> means below average.
                    </p>
                </div>
            @endif

            <!-- Mobile Card View -->
            <div class="md:hidden space-y-3">
                @foreach($courtStats as $index => $stat)
                    @php
                        // Get league average for this court position
                        $leagueAvgUtr = null;
                        $leagueAvgUsta = null;
                        if ($leagueCourtStats) {
                            $leagueStat = collect($leagueCourtStats)
                                ->where('court_type', $stat['court_type'])
                                ->where('court_number', $stat['court_number'])
                                ->first();
                            if ($leagueStat) {
                                $leagueAvgUtr = $stat['court_type'] === 'singles' ? $leagueStat['avg_utr_singles'] : $leagueStat['avg_utr_doubles'];
                                $leagueAvgUsta = $leagueStat['avg_usta_dynamic'];
                            }
                        }

                        $teamUtr = $stat['court_type'] === 'singles' ? $stat['avg_utr_singles'] : $stat['avg_utr_doubles'];
                        $teamUsta = $stat['avg_usta_dynamic'];
                        $utrDiff = $teamUtr && $leagueAvgUtr ? $teamUtr - $leagueAvgUtr : null;
                        $ustaDiff = $teamUsta && $leagueAvgUsta ? $teamUsta - $leagueAvgUsta : null;
                    @endphp

                    <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden court-card-mobile" data-court-index="{{ $index }}">
                        <div class="p-4 cursor-pointer" onclick="toggleCourtDetailsMobile({{ $index }})">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="font-semibold text-gray-800">
                                    <span class="inline-block transition-transform duration-200 court-arrow-mobile">▶</span>
                                    {{ ucfirst($stat['court_type']) }} #{{ $stat['court_number'] }}
                                </h4>
                                <span class="text-sm font-semibold {{ ($stat['court_win_percentage'] ?? 0) >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                    @if($stat['court_win_percentage'] !== null)
                                        {{ number_format($stat['court_win_percentage'], 1) }}%
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600">Avg UTR:</span>
                                    <span class="font-medium text-gray-800 ml-1">
                                        @if($stat['court_type'] === 'singles' && $stat['avg_utr_singles'])
                                            {{ number_format($stat['avg_utr_singles'], 2) }}
                                            @if($utrDiff !== null)
                                                <span class="{{ $utrDiff >= 0 ? 'text-green-600' : 'text-red-600' }} text-xs">({{ $utrDiff >= 0 ? '+' : '' }}{{ number_format($utrDiff, 2) }})</span>
                                            @endif
                                        @elseif($stat['court_type'] === 'doubles' && $stat['avg_utr_doubles'])
                                            {{ number_format($stat['avg_utr_doubles'], 2) }}
                                            @if($utrDiff !== null)
                                                <span class="{{ $utrDiff >= 0 ? 'text-green-600' : 'text-red-600' }} text-xs">({{ $utrDiff >= 0 ? '+' : '' }}{{ number_format($utrDiff, 2) }})</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Avg USTA:</span>
                                    <span class="font-medium text-gray-800 ml-1">
                                        @if($stat['avg_usta_dynamic'])
                                            {{ number_format($stat['avg_usta_dynamic'], 2) }}
                                            @if($ustaDiff !== null)
                                                <span class="{{ $ustaDiff >= 0 ? 'text-green-600' : 'text-red-600' }} text-xs">({{ $ustaDiff >= 0 ? '+' : '' }}{{ number_format($ustaDiff, 2) }})</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="court-details-mobile hidden px-4 pb-4 bg-white border-t border-gray-200" data-court-index="{{ $index }}">
                            @if(!empty($stat['players']))
                                <h5 class="text-sm font-semibold text-gray-700 mb-3 mt-3">Player Performance</h5>
                                <div class="space-y-2">
                                    @foreach($stat['players'] as $playerStat)
                                        <div class="bg-gray-50 rounded p-2 text-xs">
                                            <div class="font-semibold text-gray-800 mb-1">
                                                {{ $playerStat['player_name'] ?? 'Unknown' }}
                                                @if(!empty($playerStat['current_utr']))
                                                    <span class="text-gray-400 font-normal">({{ number_format($playerStat['current_utr'], 2) }})</span>
                                                @elseif(!empty($playerStat['current_utrs']))
                                                    <span class="text-gray-400 font-normal">({{ implode(' / ', array_map(fn($u) => $u ? number_format($u, 2) : '-', $playerStat['current_utrs'])) }})</span>
                                                @endif
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 text-gray-600">
                                                <div>Record:
                                                    @if(!($playerStat['is_team'] ?? true) && isset($playerStat['player_id']))
                                                        <a href="{{ route('players.show', $playerStat['player_id']) }}?court={{ $stat['court_type'] }}&line={{ $stat['court_number'] }}&team={{ $team->id }}#match-history" class="font-medium hover:underline text-blue-600">{{ $playerStat['wins'] ?? 0 }}-{{ $playerStat['losses'] ?? 0 }}</a>
                                                    @elseif(($playerStat['is_team'] ?? false) && !empty($playerStat['player_ids'][0]))
                                                        <a href="{{ route('players.show', $playerStat['player_ids'][0]) }}?court={{ $stat['court_type'] }}&line={{ $stat['court_number'] }}&team={{ $team->id }}&partner={{ $playerStat['player_ids'][1] ?? '' }}#match-history" class="font-medium hover:underline text-blue-600">{{ $playerStat['wins'] ?? 0 }}-{{ $playerStat['losses'] ?? 0 }}</a>
                                                    @else
                                                        <span class="font-medium">{{ $playerStat['wins'] ?? 0 }}-{{ $playerStat['losses'] ?? 0 }}</span>
                                                    @endif
                                                </div>
                                                <div>Win %: <span class="font-medium {{ ($playerStat['win_percentage'] ?? 0) >= 50 ? 'text-green-600' : 'text-red-600' }}">{{ isset($playerStat['win_percentage']) ? number_format($playerStat['win_percentage'], 1) : '0.0' }}%</span></div>
                                                <div>Avg UTR: <span class="font-medium">{{ isset($playerStat['avg_utr']) && $playerStat['avg_utr'] ? number_format($playerStat['avg_utr'], 2) : '-' }}</span></div>
                                                <div>Avg USTA: <span class="font-medium">{{ isset($playerStat['avg_usta_dynamic']) && $playerStat['avg_usta_dynamic'] ? number_format($playerStat['avg_usta_dynamic'], 2) : '-' }}</span></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 italic mt-3">No player data available</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Court Position</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Avg UTR</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Avg USTA</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Win %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courtStats as $index => $stat)
                            @php
                                // Get league average for this court position
                                $leagueAvgUtr = null;
                                $leagueAvgUsta = null;
                                if ($leagueCourtStats) {
                                    $leagueStat = collect($leagueCourtStats)
                                        ->where('court_type', $stat['court_type'])
                                        ->where('court_number', $stat['court_number'])
                                        ->first();
                                    if ($leagueStat) {
                                        $leagueAvgUtr = $stat['court_type'] === 'singles' ? $leagueStat['avg_utr_singles'] : $leagueStat['avg_utr_doubles'];
                                        $leagueAvgUsta = $leagueStat['avg_usta_dynamic'];
                                    }
                                }

                                $teamUtr = $stat['court_type'] === 'singles' ? $stat['avg_utr_singles'] : $stat['avg_utr_doubles'];
                                $teamUsta = $stat['avg_usta_dynamic'];
                                $utrDiff = $teamUtr && $leagueAvgUtr ? $teamUtr - $leagueAvgUtr : null;
                                $ustaDiff = $teamUsta && $leagueAvgUsta ? $teamUsta - $leagueAvgUsta : null;
                            @endphp
                            <tr class="hover:bg-gray-50 cursor-pointer border-t border-gray-200 court-row" data-court-index="{{ $index }}">
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    <span class="inline-block w-4 transition-transform duration-200">▶</span>
                                    {{ ucfirst($stat['court_type']) }} #{{ $stat['court_number'] }}
                                </td>
                                <td class="px-4 py-2 text-sm text-center text-gray-700">
                                    @if($stat['court_type'] === 'singles' && $stat['avg_utr_singles'])
                                        {{ number_format($stat['avg_utr_singles'], 2) }}
                                        @if($utrDiff !== null)
                                            <span class="{{ $utrDiff >= 0 ? 'text-green-600' : 'text-red-600' }}">({{ $utrDiff >= 0 ? '+' : '' }}{{ number_format($utrDiff, 2) }})</span>
                                        @endif
                                    @elseif($stat['court_type'] === 'doubles' && $stat['avg_utr_doubles'])
                                        {{ number_format($stat['avg_utr_doubles'], 2) }}
                                        @if($utrDiff !== null)
                                            <span class="{{ $utrDiff >= 0 ? 'text-green-600' : 'text-red-600' }}">({{ $utrDiff >= 0 ? '+' : '' }}{{ number_format($utrDiff, 2) }})</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-center text-gray-700">
                                    @if($stat['avg_usta_dynamic'])
                                        {{ number_format($stat['avg_usta_dynamic'], 2) }}
                                        @if($ustaDiff !== null)
                                            <span class="{{ $ustaDiff >= 0 ? 'text-green-600' : 'text-red-600' }}">({{ $ustaDiff >= 0 ? '+' : '' }}{{ number_format($ustaDiff, 2) }})</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-center">
                                    @if($stat['court_win_percentage'] !== null)
                                        <span class="font-semibold {{ $stat['court_win_percentage'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($stat['court_win_percentage'], 1) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="court-details hidden" data-court-index="{{ $index }}">
                                <td colspan="4" class="px-4 py-3 bg-gray-50">
                                    @if(!empty($stat['players']))
                                        <div class="ml-8">
                                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Player Performance at {{ ucfirst($stat['court_type']) }} #{{ $stat['court_number'] }}</h4>
                                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Player</th>
                                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">Record</th>
                                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">Win %</th>
                                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">Avg UTR</th>
                                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">Avg USTA</th>
                                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">Avg Opp UTR</th>
                                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">Avg Opp USTA</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    @foreach($stat['players'] as $player)
                                                        <tr class="hover:bg-gray-100">
                                                            <td class="px-3 py-2 text-gray-700">
                                                                @if($player['is_team'])
                                                                    {{-- For doubles teams, create links for each player --}}
                                                                    @php
                                                                        $names = explode(' / ', $player['player_name']);
                                                                        $ids = $player['player_ids'];
                                                                        $currentUtrs = $player['current_utrs'] ?? [];
                                                                    @endphp
                                                                    @foreach($names as $index => $name)
                                                                        @if($index > 0) / @endif
                                                                        <a href="{{ route('players.show', $ids[$index]) }}" class="text-blue-600 hover:underline">{{ $name }}</a>@if(!empty($currentUtrs[$index]))<span class="text-xs text-gray-400 font-normal ml-0.5">({{ number_format($currentUtrs[$index], 2) }})</span>@endif
                                                                    @endforeach
                                                                @else
                                                                    {{-- For singles players, single link --}}
                                                                    <a href="{{ route('players.show', $player['player_id']) }}" class="text-blue-600 hover:underline">{{ $player['player_name'] }}</a>@if(!empty($player['current_utr']))<span class="text-xs text-gray-400 font-normal ml-0.5">({{ number_format($player['current_utr'], 2) }})</span>@endif
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-center text-gray-700">
                                                                @if(!$player['is_team'])
                                                                    <a href="{{ route('players.show', $player['player_id']) }}?court={{ $stat['court_type'] }}&line={{ $stat['court_number'] }}&team={{ $team->id }}#match-history" class="hover:underline">
                                                                        <span class="text-green-600 font-semibold">{{ $player['wins'] }}</span>-<span class="text-red-600 font-semibold">{{ $player['losses'] }}</span>
                                                                    </a>
                                                                @else
                                                                    <a href="{{ route('players.show', $player['player_ids'][0]) }}?court={{ $stat['court_type'] }}&line={{ $stat['court_number'] }}&team={{ $team->id }}&partner={{ $player['player_ids'][1] ?? '' }}#match-history" class="hover:underline">
                                                                        <span class="text-green-600 font-semibold">{{ $player['wins'] }}</span>-<span class="text-red-600 font-semibold">{{ $player['losses'] }}</span>
                                                                    </a>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-center text-gray-700">
                                                                @if($player['total'] > 0)
                                                                    <span class="font-semibold {{ $player['win_percentage'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                                                        {{ number_format($player['win_percentage'], 1) }}%
                                                                    </span>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-center text-gray-700">
                                                                @if($player['avg_utr'])
                                                                    {{ number_format($player['avg_utr'], 2) }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-center text-gray-700">
                                                                @if($player['avg_usta'])
                                                                    {{ number_format($player['avg_usta'], 2) }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-center text-gray-700">
                                                                @if($player['avg_opponent_utr'])
                                                                    {{ number_format($player['avg_opponent_utr'], 2) }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-center text-gray-700">
                                                                @if($player['avg_opponent_usta'])
                                                                    {{ number_format($player['avg_opponent_usta'], 2) }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-gray-500 text-sm ml-8">No player data available for this court position.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    @endif

    <!-- League Lineup Comparison -->
    @if($leagueLineupData && count($leagueLineupData) > 0)
        <div class="hidden md:block max-w-4xl mx-auto mb-6 bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Singles Lineup vs League</h3>
                <div class="flex items-center space-x-4">
                    <label class="flex items-center space-x-2 text-sm cursor-pointer">
                        <input type="checkbox" id="verifiedOnlyFilter" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="font-semibold text-green-600">✓ Verified UTR Only</span>
                    </label>
                    <div class="flex space-x-2">
                        <button id="toggleUTR" class="px-4 py-2 bg-blue-500 text-white rounded text-sm font-semibold">
                            UTR
                        </button>
                        <button id="toggleUSTA" class="px-4 py-2 bg-gray-300 text-gray-700 rounded text-sm font-semibold">
                            USTA
                        </button>
                    </div>
                </div>
            </div>

            <div id="lineupChart" class="mt-4 overflow-x-auto">
                <!-- Chart will be rendered here -->
            </div>
        </div>
    @endif

    @if($leagueDoublesLineupData && count($leagueDoublesLineupData) > 0)
        <div class="hidden md:block max-w-4xl mx-auto mb-6 bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Doubles Lineup vs League (Top 8)</h3>
                <div class="flex items-center space-x-4">
                    <label class="flex items-center space-x-2 text-sm cursor-pointer">
                        <input type="checkbox" id="doublesVerifiedOnlyFilter" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="font-semibold text-green-600">✓ Verified UTR Only</span>
                    </label>
                    <div class="flex space-x-2">
                        <button id="toggleDoublesUTR" class="px-4 py-2 bg-blue-500 text-white rounded text-sm font-semibold">
                            UTR
                        </button>
                        <button id="toggleDoublesUSTA" class="px-4 py-2 bg-gray-300 text-gray-700 rounded text-sm font-semibold">
                            USTA
                        </button>
                    </div>
                </div>
            </div>

            <div id="doublesLineupChart" class="mt-4 overflow-x-auto">
                <!-- Chart will be rendered here -->
            </div>
        </div>
    @endif

    <div class="mb-4 flex justify-between items-center px-2 md:px-6">
        <div class="text-sm text-gray-600">
            <strong>{{ $team->players->count() }}</strong> players on this team
        </div>

        <div class="flex space-x-2">
            @if($team->players->count() > 0)
                @php
                    $playersWithoutUtrId = $team->players->whereNull('utr_id')->count();
                @endphp

                @if($playersWithoutUtrId > 0)
                    <form method="POST" action="{{ route('teams.findMissingUtrIds', $team->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                            🔍 Find Missing UTR IDs ({{ $playersWithoutUtrId }})
                        </button>
                    </form>
                @endif

                @env('local')
                    <form method="POST" action="{{ route('teams.updateUtr', $team->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
                            🔄 Update All UTRs
                        </button>
                    </form>
                @endenv
            @endif

            @if($team->usta_link)
                <a href="{{ $team->usta_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    <img src="{{ asset('images/usta_logo.png') }}" alt="USTA" class="h-4 w-6 mr-2">
                    View USTA
                </a>
            @endif

            @if($team->tennis_record_link)
                <a href="{{ $team->tennis_record_link }}" target="_blank" rel="noopener noreferrer" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                    🎾 Tennis Record Link
                </a>
                @env('local')
                    <form method="POST" action="{{ route('teams.syncFromTennisRecord', $team->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded">
                            🔄 Sync from Tennis Record
                        </button>
                    </form>
                    <form method="POST" action="{{ route('teams.syncTrProfiles', $team->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 px-4 rounded">
                            📋 Sync TR Profiles
                        </button>
                    </form>
                @endenv
            @endif
        </div>
    </div>

    <!-- Add Player Section -->
    @if($availablePlayers->count() > 0 && app()->environment() !== 'production')
        <div class="mb-6 px-2 md:px-6">
            <button type="button" id="toggleAddPlayerBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                + Add Players to Team
            </button>
        </div>

        <div id="addPlayerSection" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 hidden">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">Add Player to Team</h3>
                <button type="button" id="closeAddPlayerBtn" class="text-gray-500 hover:text-gray-700 text-xl font-bold">
                    &times;
                </button>
            </div>
            <form method="POST" action="{{ route('teams.addPlayer', $team->id) }}">
                @csrf
                <div class="flex-1">
                    <div class="flex justify-between items-center mb-2">
                        <label for="playerSearch" class="block text-sm font-medium text-gray-700">Search Players</label>
                        <div class="text-xs text-gray-500 space-x-2">
                            <button type="button" id="selectAllBtn" class="text-blue-600 hover:text-blue-800">Select All Visible</button>
                            <span>|</span>
                            <button type="button" id="clearAllBtn" class="text-blue-600 hover:text-blue-800">Clear All</button>
                        </div>
                    </div>
                    <input type="text" id="playerSearch" placeholder="Type to search players..."
                           class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mb-3">

                    <div id="playerList" class="border rounded px-3 py-2 h-48 overflow-y-auto bg-white">
                        @foreach($availablePlayers as $player)
                            <label class="flex items-center py-2 hover:bg-gray-50 cursor-pointer player-option"
                                   data-name="{{ strtolower($player->first_name . ' ' . $player->last_name) }}">
                                <input type="checkbox" name="player_ids[]" value="{{ $player->id }}"
                                       class="mr-3 text-blue-600 rounded focus:ring-blue-500">
                                <span class="flex-1">
                                    {{ $player->first_name }} {{ $player->last_name }}
                                    @if($player->utr_singles_rating)
                                        <span class="text-sm text-gray-500">(UTR: {{ $player->utr_singles_rating }})</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-2 text-xs text-gray-600">
                        <span id="selectedCount">0</span> player(s) selected
                    </div>
                </div>

                <div class="flex flex-col space-y-2 ml-4">
                    <button type="submit" id="addPlayersBtn" disabled
                            class="bg-green-500 hover:bg-green-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-2 px-4 rounded">
                        + Add Selected
                    </button>
                </div>
            </form>
        </div>
    @elseif($availablePlayers->count() === 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-yellow-800">All players have been added to this team!</p>
        </div>
    @endif

    <div id="players" class="relative -top-20 invisible h-0"></div>
    @if($team->players->count() > 0)
        <div class="mb-4 flex items-center space-x-2 px-2 md:px-6">
            <input
                id="playerTableSearch"
                type="text"
                placeholder="Search by name…"
                class="border rounded px-3 py-2 w-64"
            />
            <button
                id="clearPlayerTableSearch"
                type="button"
                class="hidden bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-3 rounded"
            >
                ✖ Clear
            </button>
        </div>

        <!-- Mobile Filters and Sort -->
        <div class="md:hidden mb-4 space-y-3 px-2">
            <!-- Filter Buttons -->
            <div class="flex items-center gap-3 text-sm">
                <span class="text-gray-600 font-medium">Filters:</span>
                @if($team->league && !$team->league->is_combo)
                    <button id="filterPromotedMobile" class="cursor-pointer text-2xl {{ request('promoted') ? '' : 'grayscale opacity-50' }}" title="Filter promoted players">🏅</button>
                    <button id="filterPlayingUpMobile" class="cursor-pointer text-2xl {{ request('playing_up') ? '' : 'grayscale opacity-50' }}" title="Filter players playing up">⚔️</button>
                @endif
                <button id="filterSinglesReliableMobile" class="cursor-pointer text-xl font-bold {{ request('singles_verified') ? 'text-green-600' : 'text-gray-400' }}" title="Filter verified singles ratings">S✓</button>
                <button id="filterDoublesReliableMobile" class="cursor-pointer text-xl font-bold {{ request('doubles_verified') ? 'text-green-600' : 'text-gray-400' }}" title="Filter verified doubles ratings">D✓</button>
            </div>

            <!-- Sort Controls -->
            <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-600 font-medium">Sort:</span>
                <select id="mobileSortField" class="border rounded px-2 py-1 text-sm">
                    <option value="first_name" {{ $sortField === 'first_name' ? 'selected' : '' }}>Name</option>
                    <option value="utr_singles_rating" {{ $sortField === 'utr_singles_rating' ? 'selected' : '' }}>UTR Singles</option>
                    <option value="utr_doubles_rating" {{ $sortField === 'utr_doubles_rating' ? 'selected' : '' }}>UTR Doubles</option>
                    <option value="USTA_dynamic_rating" {{ $sortField === 'USTA_dynamic_rating' ? 'selected' : '' }}>USTA Rating</option>
                </select>
                <button id="mobileSortDirection" class="border rounded px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200" data-direction="{{ $sortDirection }}">
                    {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                </button>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div id="playerCards" class="md:hidden space-y-4 px-2">
            @foreach ($sortDirection === 'asc' ? $team->players->sortBy($sortField) : $team->players->sortByDesc($sortField) as $player)
                @php
                    $isPromoted = $team->league && $team->league->NTRP_rating && $player->USTA_rating && $player->USTA_rating > $team->league->NTRP_rating;
                    $isPlayingUp = $team->league && $team->league->NTRP_rating && $player->USTA_rating && $player->USTA_rating < $team->league->NTRP_rating;

                    // Calculate singles record
                    $singlesWins = 0;
                    $singlesLosses = 0;
                    foreach ($player->courtPlayers as $courtPlayer) {
                        if ($courtPlayer->court->court_type === 'singles') {
                            $court = $courtPlayer->court;
                            if ($team->league && $court->tennisMatch->league_id !== $team->league->id) continue;
                            $isHomeTeam = $court->tennisMatch->home_team_id === $team->id;
                            if ($isHomeTeam && $court->home_score > $court->away_score) $singlesWins++;
                            elseif ($isHomeTeam && $court->home_score < $court->away_score) $singlesLosses++;
                            elseif (!$isHomeTeam && $court->away_score > $court->home_score) $singlesWins++;
                            elseif (!$isHomeTeam && $court->away_score < $court->home_score) $singlesLosses++;
                        }
                    }

                    // Calculate doubles record
                    $doublesWins = 0;
                    $doublesLosses = 0;
                    foreach ($player->courtPlayers as $courtPlayer) {
                        if ($courtPlayer->court->court_type === 'doubles') {
                            $court = $courtPlayer->court;
                            if ($team->league && $court->tennisMatch->league_id !== $team->league->id) continue;
                            $isHomeTeam = $court->tennisMatch->home_team_id === $team->id;
                            if ($isHomeTeam && $court->home_score > $court->away_score) $doublesWins++;
                            elseif ($isHomeTeam && $court->home_score < $court->away_score) $doublesLosses++;
                            elseif (!$isHomeTeam && $court->away_score > $court->home_score) $doublesWins++;
                            elseif (!$isHomeTeam && $court->away_score < $court->home_score) $doublesLosses++;
                        }
                    }

                    $ratingClass = '';
                    if ($team->league && $team->league->NTRP_rating && $player->USTA_dynamic_rating) {
                        if ($player->USTA_dynamic_rating >= $team->league->NTRP_rating) {
                            $ratingClass = 'text-green-600 font-semibold';
                        } elseif ($team->league->NTRP_rating > 3.0 && $player->USTA_dynamic_rating <= $team->league->NTRP_rating - 0.5) {
                            $ratingClass = 'text-amber-600 font-semibold';
                        }
                    }
                @endphp

                <div class="bg-white rounded-lg shadow p-4" data-name="{{ strtolower($player->first_name . ' ' . $player->last_name) }}" data-singles-reliable="{{ $player->utr_singles_reliable ? '1' : '0' }}" data-doubles-reliable="{{ $player->utr_doubles_reliable ? '1' : '0' }}" data-promoted="{{ $isPromoted ? '1' : '0' }}" data-playing-up="{{ $isPlayingUp ? '1' : '0' }}">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <a href="{{ route('players.show', $player->id) }}" class="text-lg font-semibold text-blue-600 hover:underline">
                                {{ $player->first_name }} {{ $player->last_name }}
                            </a>
                            @if($team->league && !$team->league->is_combo)
                                @if($isPromoted)
                                    <span class="text-yellow-500 ml-1" title="Promoted to {{ number_format($player->USTA_rating, 1) }}">🏅</span>
                                @endif
                                @if($isPlayingUp)
                                    <span class="ml-1" title="Playing up from {{ number_format($player->USTA_rating, 1) }}">⚔️</span>
                                @endif
                            @endif
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($player->utr_id || $player->tennis_record_link)
                                <button onclick="openPlayerLinksModal('{{ $player->id }}', '{{ $player->first_name }} {{ $player->last_name }}', '{{ $player->utr_id }}', '{{ $player->tennis_record_link }}')" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded">
                                    🔗 Links
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="font-semibold text-gray-600">UTR Singles:</span>
                            <span class="text-gray-700 ml-1">
                                @if($player->utr_singles_rating)
                                    {{ number_format($player->utr_singles_rating, 2) }}
                                    @if($player->utr_singles_reliable)
                                        <span class="text-green-600 font-bold">✓</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600">UTR Doubles:</span>
                            <span class="text-gray-700 ml-1">
                                @if($player->utr_doubles_rating)
                                    {{ number_format($player->utr_doubles_rating, 2) }}
                                    @if($player->utr_doubles_reliable)
                                        <span class="text-green-600 font-bold">✓</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600">USTA Rating:</span>
                            <span class="text-gray-700 ml-1 {{ $ratingClass }}">
                                {{ $player->USTA_dynamic_rating ?? '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600">Singles:</span>
                            <span class="text-gray-700 ml-1">
                                @if($singlesWins > 0 || $singlesLosses > 0)
                                    <a href="{{ route('players.show', $player->id) }}?court=singles&team={{ $team->id }}#match-history" class="hover:underline text-blue-600">{{ $singlesWins }}-{{ $singlesLosses }}</a>
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="col-span-2">
                            <span class="font-semibold text-gray-600">Doubles:</span>
                            <span class="text-gray-700 ml-1">
                                @if($doublesWins > 0 || $doublesLosses > 0)
                                    <a href="{{ route('players.show', $player->id) }}?court=doubles&team={{ $team->id }}#match-history" class="hover:underline text-blue-600">{{ $doublesWins }}-{{ $doublesLosses }}</a>
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block bg-white rounded-lg shadow md:mx-6">
            <table id="playersTable" class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <div class="flex items-center gap-2">
                                @if($team->league && !$team->league->is_combo)
                                    <span id="filterPromoted" class="cursor-pointer text-lg {{ request('promoted') ? '' : 'grayscale opacity-50' }}" title="Filter promoted players">🏅</span>
                                    <span id="filterPlayingUp" class="cursor-pointer text-lg {{ request('playing_up') ? '' : 'grayscale opacity-50' }}" title="Filter players playing up">⚔️</span>
                                @endif
                                <a href="{{ route('teams.show', ['team' => $team->id, 'sort' => 'first_name', 'direction' => ($sortField === 'first_name' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                    Name
                                    @if($sortField === 'first_name' || $sortField === 'last_name')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </div>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <div class="flex items-center gap-2">
                                <span id="filterSinglesReliable" class="cursor-pointer text-lg font-bold {{ request('singles_verified') ? 'text-green-600' : 'text-gray-400' }}" onclick="event.stopPropagation()" title="Filter verified ratings">✓</span>
                                <a href="{{ route('teams.show', ['team' => $team->id, 'sort' => 'utr_singles_rating', 'direction' => ($sortField === 'utr_singles_rating' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                    UTR Singles Rating
                                    @if($sortField === 'utr_singles_rating')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </div>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <div class="flex items-center gap-2">
                                <span id="filterDoublesReliable" class="cursor-pointer text-lg font-bold {{ request('doubles_verified') ? 'text-green-600' : 'text-gray-400' }}" onclick="event.stopPropagation()" title="Filter verified ratings">✓</span>
                                <a href="{{ route('teams.show', ['team' => $team->id, 'sort' => 'utr_doubles_rating', 'direction' => ($sortField === 'utr_doubles_rating' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                    UTR Doubles Rating
                                    @if($sortField === 'utr_doubles_rating')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </div>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('teams.show', ['team' => $team->id, 'sort' => 'USTA_dynamic_rating', 'direction' => ($sortField === 'USTA_dynamic_rating' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                USTA Dynamic Rating
                                @if($sortField === 'USTA_dynamic_rating')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Singles Record</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Doubles Record</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Player Links</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($sortDirection === 'asc' ? $team->players->sortBy($sortField) : $team->players->sortByDesc($sortField) as $player)
                        @php
                            $isPromoted = $team->league && $team->league->NTRP_rating && $player->USTA_rating && $player->USTA_rating > $team->league->NTRP_rating;
                            $isPlayingUp = $team->league && $team->league->NTRP_rating && $player->USTA_rating && $player->USTA_rating < $team->league->NTRP_rating;
                        @endphp
                        <tr ondblclick="window.location='{{ route('players.edit', $player->id) }}?return_url={{ urlencode(route('teams.show', $team->id)) }}'" class="hover:bg-gray-50 cursor-pointer" data-name="{{ strtolower($player->first_name . ' ' . $player->last_name) }}" data-singles-reliable="{{ $player->utr_singles_reliable ? '1' : '0' }}" data-doubles-reliable="{{ $player->utr_doubles_reliable ? '1' : '0' }}" data-promoted="{{ $isPromoted ? '1' : '0' }}" data-playing-up="{{ $isPlayingUp ? '1' : '0' }}">
                            <td class="px-4 py-2 text-sm text-gray-700">
                                <a href="{{ route('players.show', $player->id) }}" class="text-blue-600 hover:underline font-semibold">
                                    {{ $player->first_name }} {{ $player->last_name }}
                                </a>
                                @if($team->league && !$team->league->is_combo)
                                    @if($isPromoted)
                                        <span class="relative inline-block group">
                                            <span class="text-yellow-500">🏅</span>
                                            <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2
                                                        opacity-0 group-hover:opacity-100 transition pointer-events-none
                                                        bg-gray-800 text-white text-xs rounded py-1 px-2
                                                        whitespace-nowrap z-50">
                                                Promoted to {{ number_format($player->USTA_rating, 1) }}
                                            </div>
                                        </span>
                                    @endif
                                    @if($isPlayingUp)
                                        <span class="relative inline-block group">
                                            <span>⚔️</span>
                                            <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2
                                                        opacity-0 group-hover:opacity-100 transition pointer-events-none
                                                        bg-gray-800 text-white text-xs rounded py-1 px-2
                                                        whitespace-nowrap z-50">
                                                Playing up from {{ number_format($player->USTA_rating, 1) }}
                                            </div>
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                @if($player->utr_singles_rating)
                                    <div class="relative inline-block group">
                                        <span>{{ number_format($player->utr_singles_rating, 2) }}</span>
                                        @if($player->utr_singles_reliable)
                                            <span class="text-green-600 font-bold" title="100% Reliable">✓</span>
                                        @endif
                                        @if($player->utr_singles_updated_at)
                                            <!-- Tooltip -->
                                            <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2
                                                        opacity-0 group-hover:opacity-100 transition pointer-events-none
                                                        bg-gray-800 text-white text-xs rounded py-1 px-2
                                                        whitespace-nowrap z-50">
                                                Last updated: {{ $player->utr_singles_updated_at->format('M d, Y h:i A') }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                @if($player->utr_doubles_rating)
                                    <div class="relative inline-block group">
                                        <span>{{ number_format($player->utr_doubles_rating, 2) }}</span>
                                        @if($player->utr_doubles_reliable)
                                            <span class="text-green-600 font-bold" title="100% Reliable">✓</span>
                                        @endif
                                        @if($player->utr_doubles_updated_at)
                                            <!-- Tooltip -->
                                            <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2
                                                        opacity-0 group-hover:opacity-100 transition pointer-events-none
                                                        bg-gray-800 text-white text-xs rounded py-1 px-2
                                                        whitespace-nowrap z-50">
                                                Last updated: {{ $player->utr_doubles_updated_at->format('M d, Y h:i A') }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                @if($player->USTA_dynamic_rating)
                                    <div class="relative inline-block group">
                                        @php
                                            $ratingClass = '';
                                            if ($team->league && $team->league->NTRP_rating) {
                                                if ($player->USTA_dynamic_rating >= $team->league->NTRP_rating) {
                                                    $ratingClass = 'text-green-600 font-semibold';
                                                } elseif ($team->league->NTRP_rating > 3.0 && $player->USTA_dynamic_rating <= $team->league->NTRP_rating - 0.5) {
                                                    $ratingClass = 'text-amber-600 font-semibold';
                                                }
                                            }
                                        @endphp
                                        <span class="{{ $ratingClass }}">{{ $player->USTA_dynamic_rating }}</span>
                                        @if($player->tennis_record_last_sync)
                                            <!-- Tooltip -->
                                            <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2
                                                        opacity-0 group-hover:opacity-100 transition pointer-events-none
                                                        bg-gray-800 text-white text-xs rounded py-1 px-2
                                                        whitespace-nowrap z-50">
                                                Last synced: {{ $player->tennis_record_last_sync->format('M d, Y h:i A') }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-center text-gray-700">
                                @php
                                    // Calculate singles record (wins-losses) for this league only
                                    $singlesWins = 0;
                                    $singlesLosses = 0;

                                    foreach ($player->courtPlayers as $courtPlayer) {
                                        if ($courtPlayer->court->court_type === 'singles') {
                                            $court = $courtPlayer->court;

                                            // Only count matches in this team's league
                                            if ($team->league && $court->tennisMatch->league_id !== $team->league->id) {
                                                continue;
                                            }

                                            $isHomeTeam = $court->tennisMatch->home_team_id === $team->id;

                                            if ($isHomeTeam && $court->home_score > $court->away_score) {
                                                $singlesWins++;
                                            } elseif ($isHomeTeam && $court->home_score < $court->away_score) {
                                                $singlesLosses++;
                                            } elseif (!$isHomeTeam && $court->away_score > $court->home_score) {
                                                $singlesWins++;
                                            } elseif (!$isHomeTeam && $court->away_score < $court->home_score) {
                                                $singlesLosses++;
                                            }
                                        }
                                    }
                                @endphp
                                @if($singlesWins > 0 || $singlesLosses > 0)
                                    <a href="{{ route('players.show', $player->id) }}?court=singles&team={{ $team->id }}#match-history" class="font-semibold hover:underline text-blue-600">{{ $singlesWins }}-{{ $singlesLosses }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-center text-gray-700">
                                @php
                                    // Calculate doubles record (wins-losses) for this league only
                                    $doublesWins = 0;
                                    $doublesLosses = 0;

                                    foreach ($player->courtPlayers as $courtPlayer) {
                                        if ($courtPlayer->court->court_type === 'doubles') {
                                            $court = $courtPlayer->court;

                                            // Only count matches in this team's league
                                            if ($team->league && $court->tennisMatch->league_id !== $team->league->id) {
                                                continue;
                                            }

                                            $isHomeTeam = $court->tennisMatch->home_team_id === $team->id;

                                            if ($isHomeTeam && $court->home_score > $court->away_score) {
                                                $doublesWins++;
                                            } elseif ($isHomeTeam && $court->home_score < $court->away_score) {
                                                $doublesLosses++;
                                            } elseif (!$isHomeTeam && $court->away_score > $court->home_score) {
                                                $doublesWins++;
                                            } elseif (!$isHomeTeam && $court->away_score < $court->home_score) {
                                                $doublesLosses++;
                                            }
                                        }
                                    }
                                @endphp
                                @if($doublesWins > 0 || $doublesLosses > 0)
                                    <a href="{{ route('players.show', $player->id) }}?court=doubles&team={{ $team->id }}#match-history" class="font-semibold hover:underline text-blue-600">{{ $doublesWins }}-{{ $doublesLosses }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    @if($player->utr_id)
                                        <div class="relative inline-block group">
                                            <a href="https://app.utrsports.net/profiles/{{ $player->utr_id }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center">
                                                <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR Profile" class="h-5 w-5">
                                            </a>
                                            @if($player->utr_singles_updated_at)
                                                <!-- Tooltip -->
                                                <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2
                                                            opacity-0 group-hover:opacity-100 transition pointer-events-none
                                                            bg-gray-800 text-white text-xs rounded py-1 px-2
                                                            whitespace-nowrap z-50">
                                                    Updated: {{ $player->utr_singles_updated_at->format('M d, Y h:i A') }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    @if($player->tennis_record_link)
                                        <div class="relative inline-block group">
                                            <a href="{{ $player->tennis_record_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-green-600 hover:text-green-800">
                                                <span class="text-xl leading-none">🎾</span>
                                            </a>
                                            @if($player->tennis_record_last_sync)
                                                <!-- Tooltip -->
                                                <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2
                                                            opacity-0 group-hover:opacity-100 transition pointer-events-none
                                                            bg-gray-800 text-white text-xs rounded py-1 px-2
                                                            whitespace-nowrap z-50">
                                                    Last synced: {{ $player->tennis_record_last_sync->format('M d, Y h:i A') }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
            <div class="text-gray-500 text-lg mb-2">No players assigned to this team yet</div>
            <p class="text-gray-400 text-sm">Players can be assigned to teams from the player edit page</p>
        </div>
    @endif

    <!-- Score Conflicts -->
    @if(!empty($scoreConflicts))
        <div class="mt-8">
            @foreach($scoreConflicts as $conflict)
                <div class="bg-yellow-50 border border-yellow-200 p-6 rounded-lg shadow mb-4">
                    <h3 class="text-lg font-semibold mb-4 text-yellow-800">
                        ⚠️ Score Conflict Detected
                    </h3>
                    <p class="text-sm text-gray-700 mb-4">
                        Match: <strong>{{ $conflict['home_team_name'] }}</strong> vs <strong>{{ $conflict['away_team_name'] }}</strong> on {{ \Carbon\Carbon::parse($conflict['start_time'])->format('M d, Y g:i A') }}
                    </p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Source</th>
                                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Score</th>
                                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm font-semibold">Current (Database)</td>
                                    <td class="px-4 py-2 text-sm text-center">{{ $conflict['current_home_score'] }} - {{ $conflict['current_away_score'] }}</td>
                                    <td class="px-4 py-2 text-sm text-center">
                                        <span class="text-gray-500 text-xs">No action needed</span>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50 bg-green-50">
                                    <td class="px-4 py-2 text-sm font-semibold">Tennis Record (New)</td>
                                    <td class="px-4 py-2 text-sm text-center">{{ $conflict['new_home_score'] }} - {{ $conflict['new_away_score'] }}</td>
                                    <td class="px-4 py-2 text-sm text-center">
                                        <form method="POST" action="{{ route('tennis-matches.updateScore', $conflict['match_id']) }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="home_score" value="{{ $conflict['new_home_score'] }}">
                                            <input type="hidden" name="away_score" value="{{ $conflict['new_away_score'] }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-1 rounded">
                                                Use This Score
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Matches Table -->
    <div class="mt-8">
        <div id="matches" class="relative -top-20 invisible h-0"></div>
        <div class="flex justify-between items-center mb-4 px-2 md:px-6">
            <h2 class="text-2xl font-bold text-gray-800">Team Matches</h2>
            <div class="flex space-x-2">
                @env('local')
                    @if($team->league && $team->league->tennis_record_link)
                        <form method="POST" action="{{ route('teams.syncTeamMatches', $team->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
                                📅 Sync Team Matches
                            </button>
                        </form>
                    @endif
                    @if($team->league && $team->league->tennis_record_link)
                        <form method="POST" action="{{ route('teams.syncMatchDetails', $team->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
                                🎾 Sync Match Details
                            </button>
                        </form>
                    @endif
                @endenv
            </div>
        </div>

        @if($matches->count() > 0)
            <!-- Mobile Card View -->
            <div class="md:hidden space-y-4 px-2">
                @foreach($matches as $index => $match)
                    @php
                        $isHomeTeam = $match->home_team_id === $team->id;
                        $opponent = $isHomeTeam ? $match->awayTeam : $match->homeTeam;
                        $isUnplayed = ($match->home_score === null || $match->away_score === null) ||
                                      ($match->home_score === 0 && $match->away_score === 0);
                        $currentScore = $isHomeTeam ? $match->home_score : $match->away_score;
                        $opponentScore = $isHomeTeam ? $match->away_score : $match->home_score;
                    @endphp

                    <div class="bg-white rounded-lg shadow p-4 {{ $isUnplayed ? 'opacity-75' : '' }} cursor-pointer hover:bg-gray-50 transition" onclick="window.location='{{ route('tennis-matches.show', $match->id) }}'">
                        {{-- Match number + date --}}
                        <div class="text-xs text-gray-500 font-semibold mb-1">Match #{{ $index + 1 }}</div>
                        <div class="text-sm {{ $isUnplayed ? 'text-gray-500' : 'text-gray-700' }} mb-2">
                            @if($match->start_time)
                                {{ $match->start_time->format('M d, Y') }}
                                <span class="text-xs {{ $isUnplayed ? 'text-gray-400' : 'text-gray-500' }}">{{ $match->start_time->format('g:i A') }}</span>
                            @else
                                <span class="text-gray-400">TBD</span>
                            @endif
                        </div>

                        {{-- Score --}}
                        <div class="mb-3">
                            @if($match->home_score !== null && $match->away_score !== null)
                                <div class="font-bold text-lg">
                                    @if($isUnplayed)
                                        <span class="text-gray-400">{{ $currentScore }} - {{ $opponentScore }}</span>
                                    @else
                                        <span class="{{ $currentScore > $opponentScore ? 'text-green-600' : 'text-gray-900' }}">{{ $currentScore }}</span>
                                        <span class="text-gray-900"> - </span>
                                        <span class="{{ $opponentScore > $currentScore ? 'text-green-600' : 'text-gray-900' }}">{{ $opponentScore }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400 italic text-sm">Not played</span>
                            @endif
                        </div>

                        <div class="border-t border-gray-200 pt-3 space-y-1 text-sm">
                            {{-- Opponent --}}
                            <div>
                                <span class="text-xs text-gray-500">Opponent:</span>
                                <a href="{{ route('teams.show', $opponent->id) }}" onclick="event.stopPropagation()" class="ml-1 {{ $isUnplayed ? 'text-gray-500' : 'text-blue-600' }} font-semibold hover:underline">
                                    {{ $opponent->name }}
                                </a>
                            </div>
                            {{-- Location --}}
                            <div class="text-xs text-gray-600">
                                <span class="text-gray-500">Location:</span>
                                <span class="ml-1">{{ $match->location ?? '-' }}</span>
                            </div>
                            {{-- Actions --}}
                            @if($match->tennis_record_match_link || app()->environment('local'))
                                <div class="flex items-center space-x-3 pt-1">
                                    @if($match->tennis_record_match_link)
                                        <a href="{{ $match->tennis_record_match_link }}" onclick="event.stopPropagation()" target="_blank" rel="noopener noreferrer" class="text-2xl">🎾</a>
                                    @endif
                                    @env('local')
                                        <a href="{{ route('tennis-matches.edit', $match->id) }}" onclick="event.stopPropagation()" class="text-blue-600 text-lg">✏️</a>
                                        <form method="POST" action="{{ route('tennis-matches.destroy', $match->id) }}" style="display:inline;" onclick="event.stopPropagation()" onsubmit="return confirm('Are you sure you want to delete this match?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 text-lg">🗑️</button>
                                        </form>
                                    @endenv
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block bg-white rounded-lg shadow md:mx-6">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">#</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Date & Time</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Opponent</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Score</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Location</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Match</th>
                            @env('local')
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Actions</th>
                            @endenv
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($matches as $index => $match)
                            @php
                                $isHomeTeam = $match->home_team_id === $team->id;
                                $opponent = $isHomeTeam ? $match->awayTeam : $match->homeTeam;
                                $isUnplayed = ($match->home_score === null || $match->away_score === null) ||
                                              ($match->home_score === 0 && $match->away_score === 0);
                                $rowClass = $isUnplayed ? 'bg-gray-50 text-gray-500 hover:bg-gray-100' : 'hover:bg-gray-50';
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="px-4 py-2 text-sm {{ $isUnplayed ? 'text-gray-500' : 'text-gray-700' }} font-semibold">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-4 py-2 text-sm {{ $isUnplayed ? 'text-gray-500' : 'text-gray-700' }}">
                                    @if($match->start_time)
                                        {{ $match->start_time->format('M d, Y') }}
                                        <div class="text-xs {{ $isUnplayed ? 'text-gray-400' : 'text-gray-500' }}">{{ $match->start_time->format('g:i A') }}</div>
                                    @else
                                        <span class="text-gray-400">TBD</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <a href="{{ route('teams.show', $opponent->id) }}" class="{{ $isUnplayed ? 'text-gray-400 hover:text-gray-600' : 'text-blue-600 hover:underline' }}">
                                        {{ $opponent->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm text-center">
                                    @if($match->home_score !== null && $match->away_score !== null)
                                        @php
                                            $currentScore = $isHomeTeam ? $match->home_score : $match->away_score;
                                            $opponentScore = $isHomeTeam ? $match->away_score : $match->home_score;
                                        @endphp
                                        <a href="{{ route('tennis-matches.show', $match->id) }}" class="font-semibold hover:underline">
                                            @if($isUnplayed)
                                                <span class="text-gray-400">{{ $currentScore }} - {{ $opponentScore }}</span>
                                            @else
                                                <span class="{{ $currentScore > $opponentScore ? 'text-green-600' : 'text-gray-900' }}">{{ $currentScore }}</span>
                                                <span class="text-gray-900"> - </span>
                                                <span class="{{ $opponentScore > $currentScore ? 'text-green-600' : 'text-gray-900' }}">{{ $opponentScore }}</span>
                                            @endif
                                        </a>
                                    @else
                                        <a href="{{ route('tennis-matches.show', $match->id) }}" class="text-gray-400 italic hover:text-gray-600">Not played</a>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    {{ $match->location ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-center">
                                    @if($match->tennis_record_match_link)
                                        <a href="{{ $match->tennis_record_match_link }}" target="_blank" rel="noopener noreferrer" class="text-2xl hover:opacity-70 transition-opacity" title="View on Tennis Record">
                                            🎾
                                        </a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                @env('local')
                                <td class="px-4 py-2 text-sm text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('tennis-matches.edit', $match->id) }}" class="text-blue-600 hover:text-blue-800">
                                            ✏️
                                        </a>
                                        <form method="POST" action="{{ route('tennis-matches.destroy', $match->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this match?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                @endenv
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        @else
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                <div class="text-gray-500 text-lg mb-2">No matches scheduled yet</div>
                <p class="text-gray-400 text-sm">Use the "Sync Team Matches" button to fetch matches from Tennis Record</p>
            </div>
        @endif
    </div>
    </div>
</div>

<!-- Player Links Modal -->
<div id="playerLinksModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 id="playerLinksModalTitle" class="text-lg font-medium text-gray-900">Player Links</h3>
            <button onclick="closePlayerLinksModal()" class="text-gray-400 hover:text-gray-600">
                <span class="sr-only">Close</span>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div id="playerLinksContent" class="space-y-3">
            <!-- Links will be inserted here -->
        </div>

        <div class="mt-4 flex justify-end">
            <button onclick="closePlayerLinksModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    // Player Links Modal Functions
    function openPlayerLinksModal(playerId, playerName, utrId, tennisRecordLink) {
        const modal = document.getElementById('playerLinksModal');
        const title = document.getElementById('playerLinksModalTitle');
        const content = document.getElementById('playerLinksContent');

        title.textContent = playerName + ' - Links';

        let linksHtml = '';

        // Player Page Link
        linksHtml += `
            <a href="{{ url('/players') }}/${playerId}" class="flex items-center space-x-3 p-3 bg-blue-50 hover:bg-blue-100 rounded border border-blue-200 transition">
                <span class="text-3xl">👤</span>
                <div>
                    <div class="font-semibold text-gray-800">Player Profile</div>
                    <div class="text-xs text-gray-500">View full profile</div>
                </div>
            </a>
        `;

        if (utrId) {
            linksHtml += `
                <a href="https://app.utrsports.net/profiles/${utrId}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-3 p-3 bg-gray-50 hover:bg-gray-100 rounded border border-gray-200 transition">
                    <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR Profile" class="h-8 w-8">
                    <div>
                        <div class="font-semibold text-gray-800">UTR Profile</div>
                        <div class="text-xs text-gray-500">View on UTR Sports</div>
                    </div>
                </a>
            `;
        }

        if (tennisRecordLink) {
            linksHtml += `
                <a href="${tennisRecordLink}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-3 p-3 bg-gray-50 hover:bg-gray-100 rounded border border-gray-200 transition">
                    <span class="text-3xl">🎾</span>
                    <div>
                        <div class="font-semibold text-gray-800">Tennis Record</div>
                        <div class="text-xs text-gray-500">View match history</div>
                    </div>
                </a>
            `;
        }

        content.innerHTML = linksHtml;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePlayerLinksModal() {
        const modal = document.getElementById('playerLinksModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal when clicking outside
    document.getElementById('playerLinksModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePlayerLinksModal();
        }
    });

    // Mobile Court Details Toggle
    function toggleCourtDetailsMobile(courtIndex) {
        const card = document.querySelector(`.court-card-mobile[data-court-index="${courtIndex}"]`);
        const detailsDiv = document.querySelector(`.court-details-mobile[data-court-index="${courtIndex}"]`);
        const arrow = card.querySelector('.court-arrow-mobile');

        if (detailsDiv && arrow) {
            detailsDiv.classList.toggle('hidden');

            // Rotate arrow
            if (detailsDiv.classList.contains('hidden')) {
                arrow.style.transform = 'rotate(0deg)';
            } else {
                arrow.style.transform = 'rotate(90deg)';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Court Position Expand/Collapse (Desktop)
        const courtRows = document.querySelectorAll('.court-row');
        courtRows.forEach(row => {
            row.addEventListener('click', function() {
                const courtIndex = this.dataset.courtIndex;
                const detailsRow = document.querySelector(`.court-details[data-court-index="${courtIndex}"]`);
                const arrow = this.querySelector('span');

                if (detailsRow) {
                    detailsRow.classList.toggle('hidden');

                    // Rotate arrow
                    if (detailsRow.classList.contains('hidden')) {
                        arrow.style.transform = 'rotate(0deg)';
                    } else {
                        arrow.style.transform = 'rotate(90deg)';
                    }
                }
            });
        });

        // Toggle Add Player Section
        const toggleAddPlayerBtn = document.getElementById('toggleAddPlayerBtn');
        const closeAddPlayerBtn = document.getElementById('closeAddPlayerBtn');
        const addPlayerSection = document.getElementById('addPlayerSection');

        if (toggleAddPlayerBtn) {
            toggleAddPlayerBtn.addEventListener('click', function() {
                addPlayerSection.classList.remove('hidden');
                toggleAddPlayerBtn.classList.add('hidden');
            });
        }

        if (closeAddPlayerBtn) {
            closeAddPlayerBtn.addEventListener('click', function() {
                addPlayerSection.classList.add('hidden');
                toggleAddPlayerBtn.classList.remove('hidden');
            });
        }

        const searchInput = document.getElementById('playerSearch');
        const playerOptions = document.querySelectorAll('.player-option');
        const checkboxes = document.querySelectorAll('input[name="player_ids[]"]');
        const addPlayersBtn = document.getElementById('addPlayersBtn');
        const selectedCount = document.getElementById('selectedCount');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const clearAllBtn = document.getElementById('clearAllBtn');

        if (searchInput && playerOptions.length > 0) {
            // Update selected count and button state
            function updateUI() {
                const checkedBoxes = document.querySelectorAll('input[name="player_ids[]"]:checked');
                selectedCount.textContent = checkedBoxes.length;
                addPlayersBtn.disabled = checkedBoxes.length === 0;
            }

            // Filter players based on search
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();

                playerOptions.forEach(option => {
                    const playerName = option.dataset.name || '';
                    if (playerName.includes(searchTerm)) {
                        option.style.display = 'flex';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });

            // Update UI when checkboxes change
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateUI);
            });

            // Select all visible players
            selectAllBtn.addEventListener('click', function() {
                const visibleOptions = Array.from(playerOptions).filter(option =>
                    option.style.display !== 'none'
                );

                visibleOptions.forEach(option => {
                    const checkbox = option.querySelector('input[type="checkbox"]');
                    checkbox.checked = true;
                });

                updateUI();
            });

            // Clear all selections
            clearAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateUI();
            });

            // Initialize UI
            updateUI();
        }

        // Player table search functionality
        (function () {
            const input = document.getElementById('playerTableSearch');
            const clearBtn = document.getElementById('clearPlayerTableSearch');
            const playersTable = document.getElementById('playersTable');
            const playerCards = document.getElementById('playerCards');

            if (!input || !clearBtn) return;

            const rows = playersTable ? Array.from(playersTable.querySelectorAll('tbody tr[data-name]')) : [];
            const cards = playerCards ? Array.from(playerCards.querySelectorAll('[data-name]')) : [];
            let t;

            function applyFilter(term) {
                const q = term.trim().toLowerCase();

                // Filter table rows
                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const show = !q || name.includes(q);
                    row.style.display = show ? '' : 'none';
                });

                // Filter mobile cards
                cards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    const show = !q || name.includes(q);
                    card.style.display = show ? '' : 'none';
                });

                // Show clear button only when there's an active filter
                clearBtn.classList.toggle('hidden', q.length === 0);
            }

            function debouncedFilter() {
                clearTimeout(t);
                t = setTimeout(() => applyFilter(input.value), 150);
            }

            input.addEventListener('input', debouncedFilter);
            clearBtn.addEventListener('click', () => {
                input.value = '';
                applyFilter('');
                input.focus();
            });
        })();

        // Handle AJAX form submissions for UTR selection
        const utrForms = document.querySelectorAll('.utr-selection-form');
        const notificationsArea = document.getElementById('utr-success-notifications');

        utrForms.forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const playerId = this.dataset.playerId;
                const playerName = this.dataset.playerName;
                const actionUrl = this.dataset.action;

                try {
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();
                    console.log('Response:', response.status, data);

                    if (response.ok && data.success) {
                        // Show success notification
                        const notification = document.createElement('div');
                        notification.className = 'bg-green-100 text-green-700 p-2 rounded mb-2 transition-opacity duration-500';
                        notification.textContent = data.message;
                        notificationsArea.appendChild(notification);

                        // Fade out notification after 3 seconds
                        setTimeout(() => {
                            notification.style.opacity = '0';
                            setTimeout(() => notification.remove(), 500);
                        }, 3000);

                        // Fade out and remove the player's results section
                        const playerResultsDiv = document.getElementById('player-results-' + playerId);
                        if (playerResultsDiv) {
                            playerResultsDiv.style.transition = 'opacity 0.5s';
                            playerResultsDiv.style.opacity = '0';
                            setTimeout(() => playerResultsDiv.remove(), 500);
                        }
                    } else {
                        // Show error notification
                        const notification = document.createElement('div');
                        notification.className = 'bg-red-100 text-red-700 p-2 rounded mb-2';
                        notification.textContent = 'Error: ' + (data.message || data.error || JSON.stringify(data) || 'Failed to save UTR data');
                        notificationsArea.appendChild(notification);

                        setTimeout(() => notification.remove(), 5000);
                    }
                } catch (error) {
                    console.error('Error:', error);

                    // Show error notification
                    const notification = document.createElement('div');
                    notification.className = 'bg-red-100 text-red-700 p-2 rounded mb-2';
                    notification.textContent = 'Error: Failed to save UTR data. Please check the console for details.';
                    notificationsArea.appendChild(notification);

                    setTimeout(() => notification.remove(), 5000);
                }
            });
        });
    });

    // UTR Rating Filter
    (function() {
        const filterSingles = document.getElementById('filterSinglesReliable');
        const filterDoubles = document.getElementById('filterDoublesReliable');
        const filterSinglesMobile = document.getElementById('filterSinglesReliableMobile');
        const filterDoublesMobile = document.getElementById('filterDoublesReliableMobile');
        let singlesActive = {{ request('singles_verified') ? 'true' : 'false' }};
        let doublesActive = {{ request('doubles_verified') ? 'true' : 'false' }};

        function toggleSingles() {
            singlesActive = !singlesActive;
            // Update desktop filter
            if (filterSingles) {
                filterSingles.classList.toggle('text-green-600', singlesActive);
                filterSingles.classList.toggle('text-gray-400', !singlesActive);
            }
            // Update mobile filter
            if (filterSinglesMobile) {
                filterSinglesMobile.classList.toggle('text-green-600', singlesActive);
                filterSinglesMobile.classList.toggle('text-gray-400', !singlesActive);
            }
            updateURL();
        }

        function toggleDoubles() {
            doublesActive = !doublesActive;
            // Update desktop filter
            if (filterDoubles) {
                filterDoubles.classList.toggle('text-green-600', doublesActive);
                filterDoubles.classList.toggle('text-gray-400', !doublesActive);
            }
            // Update mobile filter
            if (filterDoublesMobile) {
                filterDoublesMobile.classList.toggle('text-green-600', doublesActive);
                filterDoublesMobile.classList.toggle('text-gray-400', !doublesActive);
            }
            updateURL();
        }

        function updateURL() {
            const url = new URL(window.location);

            if (singlesActive) {
                url.searchParams.set('singles_verified', '1');
            } else {
                url.searchParams.delete('singles_verified');
            }

            if (doublesActive) {
                url.searchParams.set('doubles_verified', '1');
            } else {
                url.searchParams.delete('doubles_verified');
            }

            url.hash = 'players';
            window.history.pushState({}, '', url);
            window.applyFilters();
        }

        window.applyFilters = function() {
            const table = document.getElementById('playersTable');
            const playerCards = document.getElementById('playerCards');
            const rows = table ? table.querySelectorAll('tbody tr') : [];
            const cards = playerCards ? playerCards.querySelectorAll('[data-name]') : [];

            // Get filter states from URL
            const urlParams = new URLSearchParams(window.location.search);
            const promoted = urlParams.get('promoted') === '1';
            const playingUp = urlParams.get('playing_up') === '1';

            // Filter table rows
            rows.forEach(row => {
                const singlesReliable = row.dataset.singlesReliable === '1';
                const doublesReliable = row.dataset.doublesReliable === '1';
                const isPromoted = row.dataset.promoted === '1';
                const isPlayingUp = row.dataset.playingUp === '1';

                let show = true;
                if (singlesActive && !singlesReliable) show = false;
                if (doublesActive && !doublesReliable) show = false;
                if (promoted && !isPromoted) show = false;
                if (playingUp && !isPlayingUp) show = false;

                row.style.display = show ? '' : 'none';
            });

            // Filter mobile cards
            cards.forEach(card => {
                const singlesReliable = card.dataset.singlesReliable === '1';
                const doublesReliable = card.dataset.doublesReliable === '1';
                const isPromoted = card.dataset.promoted === '1';
                const isPlayingUp = card.dataset.playingUp === '1';

                let show = true;
                if (singlesActive && !singlesReliable) show = false;
                if (doublesActive && !doublesReliable) show = false;
                if (promoted && !isPromoted) show = false;
                if (playingUp && !isPlayingUp) show = false;

                card.style.display = show ? '' : 'none';
            });
        }

        if (filterSingles) filterSingles.addEventListener('click', toggleSingles);
        if (filterDoubles) filterDoubles.addEventListener('click', toggleDoubles);
        if (filterSinglesMobile) filterSinglesMobile.addEventListener('click', toggleSingles);
        if (filterDoublesMobile) filterDoublesMobile.addEventListener('click', toggleDoubles);

        // Apply filters on page load if params exist
        window.applyFilters();
    })();

    // Promoted Players Filter
    (function() {
        const filterPromoted = document.getElementById('filterPromoted');
        const filterPromotedMobile = document.getElementById('filterPromotedMobile');
        if (!filterPromoted && !filterPromotedMobile) return;

        let promotedActive = {{ request('promoted') ? 'true' : 'false' }};

        function togglePromoted(e) {
            e.stopPropagation();
            promotedActive = !promotedActive;

            // Update desktop filter
            if (filterPromoted) {
                if (promotedActive) {
                    filterPromoted.classList.remove('grayscale', 'opacity-50');
                } else {
                    filterPromoted.classList.add('grayscale', 'opacity-50');
                }
            }

            // Update mobile filter
            if (filterPromotedMobile) {
                if (promotedActive) {
                    filterPromotedMobile.classList.remove('grayscale', 'opacity-50');
                } else {
                    filterPromotedMobile.classList.add('grayscale', 'opacity-50');
                }
            }

            updateURL();
        }

        function updateURL() {
            const url = new URL(window.location);

            if (promotedActive) {
                url.searchParams.set('promoted', '1');
            } else {
                url.searchParams.delete('promoted');
            }

            url.hash = 'players';
            window.history.pushState({}, '', url);

            // Call the global applyFilters which handles all filters together
            if (window.applyFilters) {
                window.applyFilters();
            }
        }

        if (filterPromoted) filterPromoted.addEventListener('click', togglePromoted);
        if (filterPromotedMobile) filterPromotedMobile.addEventListener('click', togglePromoted);
    })();

    // Playing Up Filter
    (function() {
        const filterPlayingUp = document.getElementById('filterPlayingUp');
        const filterPlayingUpMobile = document.getElementById('filterPlayingUpMobile');
        if (!filterPlayingUp && !filterPlayingUpMobile) return;

        let playingUpActive = {{ request('playing_up') ? 'true' : 'false' }};

        function togglePlayingUp(e) {
            e.stopPropagation();
            playingUpActive = !playingUpActive;

            // Update desktop filter
            if (filterPlayingUp) {
                if (playingUpActive) {
                    filterPlayingUp.classList.remove('grayscale', 'opacity-50');
                } else {
                    filterPlayingUp.classList.add('grayscale', 'opacity-50');
                }
            }

            // Update mobile filter
            if (filterPlayingUpMobile) {
                if (playingUpActive) {
                    filterPlayingUpMobile.classList.remove('grayscale', 'opacity-50');
                } else {
                    filterPlayingUpMobile.classList.add('grayscale', 'opacity-50');
                }
            }

            updateURL();
        }

        function updateURL() {
            const url = new URL(window.location);

            if (playingUpActive) {
                url.searchParams.set('playing_up', '1');
            } else {
                url.searchParams.delete('playing_up');
            }

            url.hash = 'players';
            window.history.pushState({}, '', url);

            // Call the global applyFilters which handles all filters together
            if (window.applyFilters) {
                window.applyFilters();
            }
        }

        if (filterPlayingUp) filterPlayingUp.addEventListener('click', togglePlayingUp);
        if (filterPlayingUpMobile) filterPlayingUpMobile.addEventListener('click', togglePlayingUp);
    })();

    // Mobile Sort Controls
    (function() {
        const mobileSortField = document.getElementById('mobileSortField');
        const mobileSortDirection = document.getElementById('mobileSortDirection');

        if (mobileSortField && mobileSortDirection) {
            function updateSort() {
                const url = new URL(window.location);
                const sortField = mobileSortField.value;
                const direction = mobileSortDirection.dataset.direction;

                // Update URL with sort parameters
                url.searchParams.set('sort', sortField);
                url.searchParams.set('direction', direction);

                // Navigate to updated URL (preserves filters automatically)
                url.hash = 'players';
                window.location.href = url.toString();
            }

            mobileSortField.addEventListener('change', updateSort);

            mobileSortDirection.addEventListener('click', function() {
                const currentDirection = this.dataset.direction;
                const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                this.dataset.direction = newDirection;
                this.textContent = newDirection === 'asc' ? '↑' : '↓';
                updateSort();
            });
        }
    })();

    // Preserve filters when clicking sort links
    (function() {
        const sortLinks = document.querySelectorAll('thead a[href*="sort="]');

        sortLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                // Get the current URL parameters
                const currentParams = new URLSearchParams(window.location.search);

                // Get the sort link's URL
                const sortUrl = new URL(this.href);

                // Preserve filter parameters from current URL
                const filterParams = ['singles_verified', 'doubles_verified', 'promoted', 'playing_up'];
                filterParams.forEach(param => {
                    const value = currentParams.get(param);
                    if (value) {
                        sortUrl.searchParams.set(param, value);
                    }
                });

                // Navigate to the updated URL
                sortUrl.hash = 'players';
                window.location.href = sortUrl.toString();
            });
        });
    })();

    // League Lineup Comparison Chart
    @if($leagueLineupData && count($leagueLineupData) > 0)
        const lineupData = @json($leagueLineupData);
        const currentTeamId = {{ $team->id }};
        const leagueNtrpRating = {{ $team->league->NTRP_rating ?? 'null' }};
        let currentRatingType = 'utr';
        let verifiedOnlyEnabled = false;

        function renderLineupChart() {
            const chartContainer = document.getElementById('lineupChart');
            if (!chartContainer) return;

            const positions = [1, 2, 3, 4, 5, 6];
            let minRating = Infinity;
            let maxRating = -Infinity;

            // Sort and position players based on selected rating type
            const sortedTeamData = lineupData.map(team => {
                // Sort players by selected rating (highest first)
                const sortedPlayers = [...team.players]
                    .filter(player => {
                        const rating = currentRatingType === 'utr' ? player.utr_singles : player.usta_dynamic;
                        if (rating == null) return false;

                        // If verified filter is enabled and viewing UTR, only show verified players
                        if (verifiedOnlyEnabled && currentRatingType === 'utr') {
                            return player.utr_singles_reliable === true;
                        }

                        return true;
                    })
                    .sort((a, b) => {
                        const ratingA = currentRatingType === 'utr' ? a.utr_singles : a.usta_dynamic;
                        const ratingB = currentRatingType === 'utr' ? b.utr_singles : b.usta_dynamic;
                        return ratingB - ratingA; // Descending order
                    })
                    .slice(0, 6) // Top 6 only
                    .map((player, index) => ({
                        ...player,
                        position: index + 1
                    }));

                return {
                    ...team,
                    players: sortedPlayers
                };
            });

            // Find min and max ratings
            sortedTeamData.forEach(team => {
                team.players.forEach(player => {
                    const rating = currentRatingType === 'utr' ? player.utr_singles : player.usta_dynamic;
                    if (rating) {
                        minRating = Math.min(minRating, rating);
                        maxRating = Math.max(maxRating, rating);
                    }
                });
            });

            // Add padding to the range
            const padding = (maxRating - minRating) * 0.1;
            minRating -= padding;
            maxRating += padding;

            // Create SVG
            const width = chartContainer.offsetWidth || 800;
            const height = 500;
            const margin = { top: 20, right: 200, bottom: 70, left: 60 };
            const chartWidth = width - margin.left - margin.right;
            const chartHeight = height - margin.top - margin.bottom;

            let svg = `<svg width="${width}" height="${height}">`;

            // Y-axis (ratings)
            const yScale = (rating) => {
                return margin.top + chartHeight - ((rating - minRating) / (maxRating - minRating)) * chartHeight;
            };

            // X-axis (positions)
            const xScale = (position) => {
                return margin.left + ((position - 0.5) / 6) * chartWidth;
            };

            // Draw grid lines
            for (let i = 0; i <= 5; i++) {
                const y = margin.top + (i / 5) * chartHeight;
                const rating = maxRating - (i / 5) * (maxRating - minRating);
                svg += `<line x1="${margin.left}" y1="${y}" x2="${width - margin.right}" y2="${y}" stroke="#e5e7eb" stroke-width="1"/>`;
                svg += `<text x="${margin.left - 10}" y="${y + 5}" text-anchor="end" font-size="12" fill="#6b7280">${rating.toFixed(1)}</text>`;
            }

            // Draw position labels
            positions.forEach(pos => {
                const x = xScale(pos);
                svg += `<text x="${x}" y="${height - 40}" text-anchor="middle" font-size="12" fill="#6b7280">#${pos}</text>`;
            });

            // Get court averages from leagueCourtStats data
            const leagueCourtStats = @json($leagueCourtStats ?? []);
            const leagueAverages = {};
            [1, 2].forEach(pos => {
                const courtStat = leagueCourtStats.find(s => s.court_type === 'singles' && parseInt(s.court_number) === pos);
                if (courtStat) {
                    const avg = currentRatingType === 'utr' ? courtStat.avg_utr_singles : courtStat.avg_usta_dynamic;
                    if (avg) leagueAverages[pos] = avg;
                }
            });

            // Store league average lines to draw later (after axis labels, before dots)
            const avgLineColors = { 1: '#dc2626', 2: '#2563eb' }; // Red for #1, Blue for #2
            let avgLinesSvg = '';
            Object.entries(leagueAverages).forEach(([pos, avgRating]) => {
                const y = yScale(avgRating);
                const color = avgLineColors[pos];
                // Visible dashed line
                avgLinesSvg += `<line x1="${margin.left}" y1="${y}" x2="${width - margin.right}" y2="${y}" stroke="${color}" stroke-width="2" stroke-dasharray="6,4" opacity="0.7"/>`;
                // Invisible thick hitbox for hover
                avgLinesSvg += `<line x1="${margin.left}" y1="${y}" x2="${width - margin.right}" y2="${y}" stroke="transparent" stroke-width="15" class="avg-line" data-pos="${pos}" data-avg="${avgRating.toFixed(2)}" style="cursor: pointer;"/>`;
            });
            svg += avgLinesSvg;

            // Draw axis labels
            const ratingLabel = currentRatingType === 'utr' ? 'Singles UTR' : 'USTA Dynamic Rating';
            // Y-axis label (rotated)
            svg += `<text x="${-height / 2}" y="15" transform="rotate(-90)" text-anchor="middle" font-size="13" font-weight="600" fill="#374151">${ratingLabel}</text>`;
            // X-axis label (below position numbers)
            svg += `<text x="${margin.left + chartWidth / 2}" y="${height - 15}" text-anchor="middle" font-size="13" font-weight="600" fill="#374151">Lineup Position by ${currentRatingType.toUpperCase()}</text>`;

            // Colors for teams
            const colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];

            // Collect all points first to detect overlaps
            const allPoints = [];
            sortedTeamData.forEach((teamData, teamIndex) => {
                const color = colors[teamIndex % colors.length];
                const isCurrentTeam = teamData.team_id === currentTeamId;
                const opacity = isCurrentTeam ? 1 : 0.7;

                teamData.players.forEach(player => {
                    const rating = currentRatingType === 'utr' ? player.utr_singles : player.usta_dynamic;
                    if (rating) {
                        allPoints.push({
                            teamData,
                            player,
                            position: player.position,
                            rating,
                            color,
                            opacity,
                            radius: isCurrentTeam ? 7 : 5,
                            isCurrentTeam
                        });
                    }
                });
            });

            // Group points by position, considering overlaps within 0.1 rating if current team is involved
            const pointGroups = [];
            allPoints.forEach(point => {
                // Find if this point should join an existing group
                let joinedGroup = false;
                for (let group of pointGroups) {
                    // Check if same position and within 0.1 rating
                    if (group[0].position === point.position) {
                        const ratingDiff = Math.abs(group[0].rating - point.rating);
                        // Group if within 0.1 AND at least one point in the group (or the current point) is from current team
                        const hasCurrentTeam = group.some(p => p.isCurrentTeam) || point.isCurrentTeam;
                        if (ratingDiff <= 0.1 && hasCurrentTeam) {
                            group.push(point);
                            joinedGroup = true;
                            break;
                        }
                    }
                }
                // If didn't join a group, create new group
                if (!joinedGroup) {
                    pointGroups.push([point]);
                }
            });

            // Draw dots with jitter for overlapping points
            pointGroups.forEach(group => {
                group.forEach((point, index) => {
                    let x = xScale(point.position);
                    const y = yScale(point.rating);

                    // If multiple points in group, spread them horizontally
                    if (group.length > 1) {
                        const totalWidth = (group.length - 1) * 12; // 12px between dots
                        const offset = (index * 12) - (totalWidth / 2);
                        x += offset;
                    }

                    // Check if player is promoted (USTA rating > league NTRP)
                    const isPromoted = leagueNtrpRating && point.player.usta_rating && point.player.usta_rating > leagueNtrpRating;
                    const strokeStyle = isPromoted ? 'stroke="#000000" stroke-width="2"' : '';

                    svg += `<circle cx="${x}" cy="${y}" r="${point.radius}" fill="${point.color}" opacity="${point.opacity}" ${strokeStyle} class="lineup-dot"
                            data-team="${point.teamData.team_name}"
                            data-player="${point.player.name}"
                            data-player-id="${point.player.id}"
                            data-position="${point.position}"
                            data-utr="${point.player.utr_singles || 'N/A'}"
                            data-usta="${point.player.usta_dynamic || 'N/A'}"
                            data-usta-rating="${point.player.usta_rating || ''}"
                            style="cursor: pointer;"/>`;
                });
            });

            // Draw legend with clickable team names
            let legendY = margin.top;
            sortedTeamData.forEach((teamData, teamIndex) => {
                const color = colors[teamIndex % colors.length];
                const isCurrentTeam = teamData.team_id === currentTeamId;
                const fontWeight = isCurrentTeam ? 'bold' : 'normal';

                svg += `<rect x="${width - margin.right + 10}" y="${legendY}" width="15" height="15" fill="${color}"/>`;
                svg += `<a href="/teams/${teamData.team_id}"><text x="${width - margin.right + 30}" y="${legendY + 12}" font-size="12" font-weight="${fontWeight}" fill="#374151" style="cursor: pointer;">${teamData.team_name}</text></a>`;
                legendY += 25;
            });

            svg += '</svg>';
            chartContainer.innerHTML = svg;

            // Add click handler and hover tooltips
            const dots = chartContainer.querySelectorAll('.lineup-dot');
            dots.forEach(dot => {
                dot.addEventListener('click', function() {
                    const playerId = this.dataset.playerId;
                    if (playerId) {
                        window.location.href = `/players/${playerId}`;
                    }
                });

                dot.addEventListener('mouseenter', function(e) {
                    // Increase dot size on hover
                    const currentRadius = parseFloat(this.getAttribute('r'));
                    this.dataset.originalRadius = currentRadius;
                    this.setAttribute('r', currentRadius + 3);

                    const team = this.dataset.team;
                    const player = this.dataset.player;
                    const position = this.dataset.position;
                    const utr = this.dataset.utr;
                    const usta = this.dataset.usta;
                    const ustaRating = parseFloat(this.dataset.ustaRating);

                    // Determine promoted/playing up status
                    let statusIcon = '';
                    if (leagueNtrpRating && ustaRating) {
                        if (ustaRating > leagueNtrpRating) {
                            statusIcon = '<span title="Promoted">🏅</span> ';
                        } else if (ustaRating < leagueNtrpRating) {
                            statusIcon = '<span title="Playing Up">⚔️</span> ';
                        }
                    }

                    const tooltip = document.createElement('div');
                    tooltip.id = 'lineup-tooltip';
                    tooltip.style.position = 'fixed';
                    tooltip.style.left = e.clientX + 10 + 'px';
                    tooltip.style.top = e.clientY + 10 + 'px';
                    tooltip.style.backgroundColor = '#1f2937';
                    tooltip.style.color = 'white';
                    tooltip.style.padding = '8px 12px';
                    tooltip.style.borderRadius = '6px';
                    tooltip.style.fontSize = '12px';
                    tooltip.style.zIndex = '1000';
                    tooltip.style.pointerEvents = 'none';
                    const ratingLine = currentRatingType === 'utr'
                        ? `<div>UTR: ${utr}</div>`
                        : `<div>UTR: ${utr}</div><div>USTA: ${usta}</div>`;

                    tooltip.innerHTML = `
                        <div style="font-weight: bold;">${statusIcon}${player}</div>
                        <div>${team} - #${position}</div>
                        ${ratingLine}
                    `;
                    document.body.appendChild(tooltip);
                });

                dot.addEventListener('mouseleave', function() {
                    // Restore original dot size
                    const originalRadius = this.dataset.originalRadius;
                    if (originalRadius) {
                        this.setAttribute('r', originalRadius);
                    }

                    const tooltip = document.getElementById('lineup-tooltip');
                    if (tooltip) {
                        tooltip.remove();
                    }
                });

                dot.addEventListener('mousemove', function(e) {
                    const tooltip = document.getElementById('lineup-tooltip');
                    if (tooltip) {
                        tooltip.style.left = e.clientX + 10 + 'px';
                        tooltip.style.top = e.clientY + 10 + 'px';
                    }
                });
            });

            // Add hover tooltips for average lines
            const avgLines = chartContainer.querySelectorAll('.avg-line');
            avgLines.forEach(line => {
                line.addEventListener('mouseenter', function(e) {
                    const pos = this.dataset.pos;
                    const avg = this.dataset.avg;

                    const tooltip = document.createElement('div');
                    tooltip.id = 'avg-line-tooltip';
                    tooltip.style.position = 'fixed';
                    tooltip.style.left = e.clientX + 10 + 'px';
                    tooltip.style.top = e.clientY + 10 + 'px';
                    tooltip.style.backgroundColor = '#1f2937';
                    tooltip.style.color = 'white';
                    tooltip.style.padding = '8px 12px';
                    tooltip.style.borderRadius = '6px';
                    tooltip.style.fontSize = '12px';
                    tooltip.style.zIndex = '1000';
                    tooltip.style.pointerEvents = 'none';
                    tooltip.innerHTML = `<div style="font-weight: bold;">Singles #${pos} League Average</div><div>${avg}</div>`;
                    document.body.appendChild(tooltip);
                });

                line.addEventListener('mouseleave', function() {
                    const tooltip = document.getElementById('avg-line-tooltip');
                    if (tooltip) tooltip.remove();
                });

                line.addEventListener('mousemove', function(e) {
                    const tooltip = document.getElementById('avg-line-tooltip');
                    if (tooltip) {
                        tooltip.style.left = e.clientX + 10 + 'px';
                        tooltip.style.top = e.clientY + 10 + 'px';
                    }
                });
            });
        }

        // Toggle buttons
        const toggleUTR = document.getElementById('toggleUTR');
        const toggleUSTA = document.getElementById('toggleUSTA');
        const verifiedOnlyFilter = document.getElementById('verifiedOnlyFilter');

        if (toggleUTR && toggleUSTA) {
            toggleUTR.addEventListener('click', function() {
                currentRatingType = 'utr';
                toggleUTR.classList.remove('bg-gray-300', 'text-gray-700');
                toggleUTR.classList.add('bg-blue-500', 'text-white');
                toggleUSTA.classList.remove('bg-blue-500', 'text-white');
                toggleUSTA.classList.add('bg-gray-300', 'text-gray-700');
                renderLineupChart();
            });

            toggleUSTA.addEventListener('click', function() {
                currentRatingType = 'usta';
                toggleUSTA.classList.remove('bg-gray-300', 'text-gray-700');
                toggleUSTA.classList.add('bg-blue-500', 'text-white');
                toggleUTR.classList.remove('bg-blue-500', 'text-white');
                toggleUTR.classList.add('bg-gray-300', 'text-gray-700');
                renderLineupChart();
            });
        }

        // Verified filter checkbox
        if (verifiedOnlyFilter) {
            verifiedOnlyFilter.addEventListener('change', function() {
                verifiedOnlyEnabled = this.checked;
                renderLineupChart();
            });
        }

        // Initial render
        renderLineupChart();

        // Re-render on window resize
        window.addEventListener('resize', renderLineupChart);
    @endif

    // League Doubles Lineup Comparison Chart
    @if($leagueDoublesLineupData && count($leagueDoublesLineupData) > 0)
        const doublesLineupData = @json($leagueDoublesLineupData);
        const currentDoublesTeamId = {{ $team->id }};
        const doublesLeagueNtrpRating = {{ $team->league->NTRP_rating ?? 'null' }};
        let currentDoublesRatingType = 'utr';
        let doublesVerifiedOnlyEnabled = false;

        function renderDoublesLineupChart() {
            const chartContainer = document.getElementById('doublesLineupChart');
            if (!chartContainer) return;

            const positions = [1, 2, 3, 4, 5, 6, 7, 8];
            let minRating = Infinity;
            let maxRating = -Infinity;

            // Sort and position players based on selected rating type
            const sortedTeamData = doublesLineupData.map(team => {
                // Sort players by selected rating (highest first)
                const sortedPlayers = [...team.players]
                    .filter(player => {
                        const rating = currentDoublesRatingType === 'utr' ? player.utr_doubles : player.usta_dynamic;
                        if (rating == null) return false;

                        // If verified filter is enabled and viewing UTR, only show verified players
                        if (doublesVerifiedOnlyEnabled && currentDoublesRatingType === 'utr') {
                            return player.utr_doubles_reliable === true;
                        }

                        return true;
                    })
                    .sort((a, b) => {
                        const ratingA = currentDoublesRatingType === 'utr' ? a.utr_doubles : a.usta_dynamic;
                        const ratingB = currentDoublesRatingType === 'utr' ? b.utr_doubles : b.usta_dynamic;
                        return ratingB - ratingA; // Descending order
                    })
                    .slice(0, 8) // Top 8 only
                    .map((player, index) => ({
                        ...player,
                        position: index + 1
                    }));

                return {
                    ...team,
                    players: sortedPlayers
                };
            });

            // Find min and max ratings
            sortedTeamData.forEach(team => {
                team.players.forEach(player => {
                    const rating = currentDoublesRatingType === 'utr' ? player.utr_doubles : player.usta_dynamic;
                    if (rating) {
                        minRating = Math.min(minRating, rating);
                        maxRating = Math.max(maxRating, rating);
                    }
                });
            });

            // Add padding to the range
            const padding = (maxRating - minRating) * 0.1;
            minRating -= padding;
            maxRating += padding;

            // Create SVG
            const width = chartContainer.offsetWidth || 800;
            const height = 500;
            const margin = { top: 20, right: 200, bottom: 70, left: 60 };
            const chartWidth = width - margin.left - margin.right;
            const chartHeight = height - margin.top - margin.bottom;

            let svg = `<svg width="${width}" height="${height}">`;

            // Y-axis (ratings)
            const yScale = (rating) => {
                return margin.top + chartHeight - ((rating - minRating) / (maxRating - minRating)) * chartHeight;
            };

            // X-axis (positions)
            const xScale = (position) => {
                return margin.left + ((position - 0.5) / 8) * chartWidth;
            };

            // Draw grid lines
            for (let i = 0; i <= 5; i++) {
                const y = margin.top + (i / 5) * chartHeight;
                const rating = maxRating - (i / 5) * (maxRating - minRating);
                svg += `<line x1="${margin.left}" y1="${y}" x2="${width - margin.right}" y2="${y}" stroke="#e5e7eb" stroke-width="1"/>`;
                svg += `<text x="${margin.left - 10}" y="${y + 5}" text-anchor="end" font-size="12" fill="#6b7280">${rating.toFixed(1)}</text>`;
            }

            // Draw position labels
            positions.forEach(pos => {
                const x = xScale(pos);
                svg += `<text x="${x}" y="${height - 40}" text-anchor="middle" font-size="12" fill="#6b7280">#${pos}</text>`;
            });

            // Get court averages from leagueCourtStats data
            const doublesCourtStats = @json($leagueCourtStats ?? []);
            const doublesLeagueAverages = {};
            [1, 2, 3].forEach(pos => {
                const courtStat = doublesCourtStats.find(s => s.court_type === 'doubles' && parseInt(s.court_number) === pos);
                if (courtStat) {
                    const avg = currentDoublesRatingType === 'utr' ? courtStat.avg_utr_doubles : courtStat.avg_usta_dynamic;
                    if (avg) doublesLeagueAverages[pos] = avg;
                }
            });

            // Store league average lines to draw later (after axis labels, before dots)
            const doublesAvgLineColors = { 1: '#dc2626', 2: '#2563eb', 3: '#059669' }; // Red for #1, Blue for #2, Green for #3
            let doublesAvgLinesSvg = '';
            Object.entries(doublesLeagueAverages).forEach(([pos, avgRating]) => {
                const y = yScale(avgRating);
                const color = doublesAvgLineColors[pos];
                // Visible dashed line
                doublesAvgLinesSvg += `<line x1="${margin.left}" y1="${y}" x2="${width - margin.right}" y2="${y}" stroke="${color}" stroke-width="2" stroke-dasharray="6,4" opacity="0.7"/>`;
                // Invisible thick hitbox for hover
                doublesAvgLinesSvg += `<line x1="${margin.left}" y1="${y}" x2="${width - margin.right}" y2="${y}" stroke="transparent" stroke-width="15" class="doubles-avg-line" data-pos="${pos}" data-avg="${avgRating.toFixed(2)}" style="cursor: pointer;"/>`;
            });
            svg += doublesAvgLinesSvg;

            // Draw axis labels
            const ratingLabel = currentDoublesRatingType === 'utr' ? 'Doubles UTR' : 'USTA Dynamic Rating';
            // Y-axis label (rotated)
            svg += `<text x="${-height / 2}" y="15" transform="rotate(-90)" text-anchor="middle" font-size="13" font-weight="600" fill="#374151">${ratingLabel}</text>`;
            // X-axis label (below position numbers)
            svg += `<text x="${margin.left + chartWidth / 2}" y="${height - 15}" text-anchor="middle" font-size="13" font-weight="600" fill="#374151">Lineup Position by ${currentDoublesRatingType.toUpperCase()}</text>`;

            // Colors for teams (highlight current team)
            const colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];

            // Collect all points first to detect overlaps
            const allDoublesPoints = [];
            sortedTeamData.forEach((teamData, teamIndex) => {
                const isCurrentTeam = teamData.team_id === currentDoublesTeamId;
                const color = colors[teamIndex % colors.length];
                const radius = isCurrentTeam ? 7 : 5;
                const opacity = isCurrentTeam ? 1.0 : 0.5;

                teamData.players.forEach(player => {
                    const rating = currentDoublesRatingType === 'utr' ? player.utr_doubles : player.usta_dynamic;
                    if (rating) {
                        allDoublesPoints.push({
                            teamData,
                            player,
                            position: player.position,
                            rating,
                            color,
                            opacity,
                            radius,
                            isCurrentTeam
                        });
                    }
                });
            });

            // Group points by position, considering overlaps within 0.1 rating
            const doublesPointGroups = [];
            allDoublesPoints.forEach(point => {
                // Find if this point should join an existing group
                let joinedGroup = false;
                for (let group of doublesPointGroups) {
                    // Check if same position and within 0.1 rating
                    if (group[0].position === point.position) {
                        const ratingDiff = Math.abs(group[0].rating - point.rating);
                        if (ratingDiff <= 0.1) {
                            group.push(point);
                            joinedGroup = true;
                            break;
                        }
                    }
                }
                // If didn't join a group, create new group
                if (!joinedGroup) {
                    doublesPointGroups.push([point]);
                }
            });

            // Draw dots with jitter for overlapping points (current team on top)
            doublesPointGroups.forEach(group => {
                // Sort so current team is drawn last (on top)
                group.sort((a, b) => a.isCurrentTeam ? 1 : -1);

                group.forEach((point, index) => {
                    let x = xScale(point.position);
                    const y = yScale(point.rating);

                    // If multiple points in group, spread them horizontally
                    if (group.length > 1) {
                        const totalWidth = (group.length - 1) * 12; // 12px between dots
                        const offset = (index * 12) - (totalWidth / 2);
                        x += offset;
                    }

                    // Check if player is promoted (USTA rating > league NTRP)
                    const isPromoted = doublesLeagueNtrpRating && point.player.usta_rating && point.player.usta_rating > doublesLeagueNtrpRating;
                    const strokeStyle = isPromoted ? 'stroke="#000000" stroke-width="2"' : '';

                    svg += `<circle cx="${x}" cy="${y}" r="${point.radius}" fill="${point.color}" opacity="${point.opacity}" ${strokeStyle} class="doubles-lineup-dot"
                            data-team="${point.teamData.team_name}"
                            data-player="${point.player.name}"
                            data-player-id="${point.player.id}"
                            data-position="${point.position}"
                            data-utr="${point.player.utr_doubles || 'N/A'}"
                            data-usta="${point.player.usta_dynamic || 'N/A'}"
                            data-usta-rating="${point.player.usta_rating || ''}"
                            style="cursor: pointer;"/>`;
                });
            });

            // Draw legend with clickable team names
            let legendY = margin.top;
            sortedTeamData.forEach((teamData, teamIndex) => {
                const isCurrentTeam = teamData.team_id === currentDoublesTeamId;
                const color = colors[teamIndex % colors.length];
                const opacity = isCurrentTeam ? 1.0 : 0.5;
                const fontWeight = isCurrentTeam ? 'bold' : 'normal';

                svg += `<rect x="${width - margin.right + 10}" y="${legendY}" width="15" height="15" fill="${color}" opacity="${opacity}"/>`;
                svg += `<a href="/teams/${teamData.team_id}"><text x="${width - margin.right + 30}" y="${legendY + 12}" font-size="12" font-weight="${fontWeight}" fill="#374151" style="cursor: pointer;">${teamData.team_name}</text></a>`;
                legendY += 25;
            });

            svg += '</svg>';
            chartContainer.innerHTML = svg;

            // Add click handler and hover tooltips
            const dots = chartContainer.querySelectorAll('.doubles-lineup-dot');
            dots.forEach(dot => {
                dot.addEventListener('click', function() {
                    const playerId = this.dataset.playerId;
                    if (playerId) {
                        window.location.href = `/players/${playerId}`;
                    }
                });

                dot.addEventListener('mouseenter', function(e) {
                    // Increase dot size on hover
                    const currentRadius = parseFloat(this.getAttribute('r'));
                    this.dataset.originalRadius = currentRadius;
                    this.setAttribute('r', currentRadius + 3);

                    const team = this.dataset.team;
                    const player = this.dataset.player;
                    const position = this.dataset.position;
                    const utr = this.dataset.utr;
                    const usta = this.dataset.usta;
                    const ustaRating = parseFloat(this.dataset.ustaRating);

                    // Determine promoted/playing up status
                    let statusIcon = '';
                    if (doublesLeagueNtrpRating && ustaRating) {
                        if (ustaRating > doublesLeagueNtrpRating) {
                            statusIcon = '<span title="Promoted">🏅</span> ';
                        } else if (ustaRating < doublesLeagueNtrpRating) {
                            statusIcon = '<span title="Playing Up">⚔️</span> ';
                        }
                    }

                    const tooltip = document.createElement('div');
                    tooltip.id = 'doubles-lineup-tooltip';
                    tooltip.style.position = 'fixed';
                    tooltip.style.left = e.clientX + 10 + 'px';
                    tooltip.style.top = e.clientY + 10 + 'px';
                    tooltip.style.backgroundColor = '#1f2937';
                    tooltip.style.color = 'white';
                    tooltip.style.padding = '8px 12px';
                    tooltip.style.borderRadius = '6px';
                    tooltip.style.fontSize = '12px';
                    tooltip.style.zIndex = '1000';
                    tooltip.style.pointerEvents = 'none';

                    const ratingLine = currentDoublesRatingType === 'utr'
                        ? `<div>UTR Doubles: ${utr}</div>`
                        : `<div>UTR Doubles: ${utr}</div><div>USTA: ${usta}</div>`;

                    tooltip.innerHTML = `
                        <div style="font-weight: bold;">${statusIcon}${player}</div>
                        <div>${team} - #${position}</div>
                        ${ratingLine}
                    `;
                    document.body.appendChild(tooltip);
                });

                dot.addEventListener('mouseleave', function() {
                    // Restore original dot size
                    const originalRadius = this.dataset.originalRadius;
                    if (originalRadius) {
                        this.setAttribute('r', originalRadius);
                    }

                    const tooltip = document.getElementById('doubles-lineup-tooltip');
                    if (tooltip) {
                        tooltip.remove();
                    }
                });

                dot.addEventListener('mousemove', function(e) {
                    const tooltip = document.getElementById('doubles-lineup-tooltip');
                    if (tooltip) {
                        tooltip.style.left = e.clientX + 10 + 'px';
                        tooltip.style.top = e.clientY + 10 + 'px';
                    }
                });
            });

            // Add hover tooltips for doubles average lines
            const doublesAvgLines = chartContainer.querySelectorAll('.doubles-avg-line');
            doublesAvgLines.forEach(line => {
                line.addEventListener('mouseenter', function(e) {
                    const pos = this.dataset.pos;
                    const avg = this.dataset.avg;

                    const tooltip = document.createElement('div');
                    tooltip.id = 'doubles-avg-line-tooltip';
                    tooltip.style.position = 'fixed';
                    tooltip.style.left = e.clientX + 10 + 'px';
                    tooltip.style.top = e.clientY + 10 + 'px';
                    tooltip.style.backgroundColor = '#1f2937';
                    tooltip.style.color = 'white';
                    tooltip.style.padding = '8px 12px';
                    tooltip.style.borderRadius = '6px';
                    tooltip.style.fontSize = '12px';
                    tooltip.style.zIndex = '1000';
                    tooltip.style.pointerEvents = 'none';
                    tooltip.innerHTML = `<div style="font-weight: bold;">Doubles #${pos} League Average</div><div>${avg}</div>`;
                    document.body.appendChild(tooltip);
                });

                line.addEventListener('mouseleave', function() {
                    const tooltip = document.getElementById('doubles-avg-line-tooltip');
                    if (tooltip) tooltip.remove();
                });

                line.addEventListener('mousemove', function(e) {
                    const tooltip = document.getElementById('doubles-avg-line-tooltip');
                    if (tooltip) {
                        tooltip.style.left = e.clientX + 10 + 'px';
                        tooltip.style.top = e.clientY + 10 + 'px';
                    }
                });
            });
        }

        // Toggle buttons
        const toggleDoublesUTR = document.getElementById('toggleDoublesUTR');
        const toggleDoublesUSTA = document.getElementById('toggleDoublesUSTA');
        const doublesVerifiedOnlyFilter = document.getElementById('doublesVerifiedOnlyFilter');

        if (toggleDoublesUTR && toggleDoublesUSTA) {
            toggleDoublesUTR.addEventListener('click', function() {
                currentDoublesRatingType = 'utr';
                toggleDoublesUTR.classList.remove('bg-gray-300', 'text-gray-700');
                toggleDoublesUTR.classList.add('bg-blue-500', 'text-white');
                toggleDoublesUSTA.classList.remove('bg-blue-500', 'text-white');
                toggleDoublesUSTA.classList.add('bg-gray-300', 'text-gray-700');
                renderDoublesLineupChart();
            });

            toggleDoublesUSTA.addEventListener('click', function() {
                currentDoublesRatingType = 'usta';
                toggleDoublesUSTA.classList.remove('bg-gray-300', 'text-gray-700');
                toggleDoublesUSTA.classList.add('bg-blue-500', 'text-white');
                toggleDoublesUTR.classList.remove('bg-blue-500', 'text-white');
                toggleDoublesUTR.classList.add('bg-gray-300', 'text-gray-700');
                renderDoublesLineupChart();
            });
        }

        // Verified filter checkbox
        if (doublesVerifiedOnlyFilter) {
            doublesVerifiedOnlyFilter.addEventListener('change', function() {
                doublesVerifiedOnlyEnabled = this.checked;
                renderDoublesLineupChart();
            });
        }

        // Initial render
        renderDoublesLineupChart();

        // Re-render on window resize
        window.addEventListener('resize', renderDoublesLineupChart);
    @endif
</script>

<!-- Back to Top Button -->
<button id="back-to-top"
    onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
    class="fixed bottom-6 right-6 z-50 bg-blue-600 hover:bg-blue-700 text-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg opacity-0 pointer-events-none transition-opacity duration-200"
    aria-label="Back to top">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
    </svg>
</button>
<script>
    (function () {
        var btn = document.getElementById('back-to-top');
        var label = document.getElementById('nav-context-label');
        if (label) {
            @php
                $words = explode(' ', $team->name);
                $shortName = implode(' ', array_slice($words, 0, 2));
            @endphp
            label.textContent = '{{ addslashes($shortName) }}';
        }
        window.addEventListener('scroll', function () {
            if (window.scrollY > 300) {
                btn.classList.remove('opacity-0', 'pointer-events-none');
                if (label) label.classList.remove('opacity-0');
            } else {
                btn.classList.add('opacity-0', 'pointer-events-none');
                if (label) label.classList.add('opacity-0');
            }
        }, { passive: true });
    })();
</script>
@endsection
