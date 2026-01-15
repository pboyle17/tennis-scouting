@extends('layouts.app')

@section('title', 'Team Players - ' . $team->name)

@section('content')
<div class="container mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-center text-gray-800">{{ $team->name }} - Players</h1>
        <div class="flex space-x-2">
            <form method="POST" action="{{ route('teams.destroy', $team->id) }}" onsubmit="return confirm('Are you sure you want to delete this team? This will not delete the players, only remove them from this team.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded">
                    🗑️ Delete Team
                </button>
            </form>
            <a href="{{ route('teams.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                ← Back to Teams
            </a>
        </div>
    </div>

    @include('partials.tabs')

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

    @if($team->league)
        <div class="max-w-4xl mx-auto mb-6 bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-3">Leagues</h3>
            <a href="{{ route('leagues.show', $team->league->id) }}" class="block p-3 bg-gray-50 hover:bg-gray-100 rounded border border-gray-200 transition">
                <div class="font-medium text-gray-800">{{ $team->league->name }}</div>
            </a>
        </div>
    @endif

    @if(!empty($courtStats))
        <div class="max-w-4xl mx-auto mb-6 bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">Court Position Averages</h3>
            <p class="text-sm text-gray-500 mb-3">Click on any row to see player details</p>
            @if($leagueCourtStats)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-4">
                    <p class="text-sm text-gray-700">
                        <span class="font-semibold">League Comparison:</span> Numbers in parentheses show your team's difference from the league average.
                        <span class="text-green-600 font-semibold">Green (+)</span> means above average,
                        <span class="text-red-600 font-semibold">Red (-)</span> means below average.
                    </p>
                </div>
            @endif
            <div class="overflow-x-auto">
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
                                                                    @endphp
                                                                    @foreach($names as $index => $name)
                                                                        @if($index > 0) / @endif
                                                                        <a href="{{ route('players.show', $ids[$index]) }}" class="text-blue-600 hover:underline">{{ $name }}</a>
                                                                    @endforeach
                                                                @else
                                                                    {{-- For singles players, single link --}}
                                                                    <a href="{{ route('players.show', $player['player_id']) }}" class="text-blue-600 hover:underline">
                                                                        {{ $player['player_name'] }}
                                                                    </a>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-center text-gray-700">
                                                                <span class="text-green-600 font-semibold">{{ $player['wins'] }}</span>
                                                                -
                                                                <span class="text-red-600 font-semibold">{{ $player['losses'] }}</span>
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
    @endif

    <!-- League Lineup Comparison -->
    @if($leagueLineupData && count($leagueLineupData) > 0)
        <div class="max-w-4xl mx-auto mb-6 bg-white p-6 rounded-lg shadow">
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
        <div class="max-w-4xl mx-auto mb-6 bg-white p-6 rounded-lg shadow">
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

    <div class="mb-4 flex justify-between items-center">
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

                <form method="POST" action="{{ route('teams.updateUtr', $team->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded">
                        🔄 Update All UTRs
                    </button>
                </form>
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
    @if($availablePlayers->count() > 0)
        <div class="mb-6">
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
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-yellow-800">All players have been added to this team!</p>
        </div>
    @endif

    @if($team->players->count() > 0)
        <div class="mb-4 flex items-center space-x-2">
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

        <div class="bg-white rounded-lg shadow">
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
                                    <span class="font-semibold">{{ $singlesWins }}-{{ $singlesLosses }}</span>
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
                                    <span class="font-semibold">{{ $doublesWins }}-{{ $doublesLosses }}</span>
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
        <div class="flex justify-between items-center mb-4">
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
            <div class="bg-white rounded-lg shadow">
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
        @else
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                <div class="text-gray-500 text-lg mb-2">No matches scheduled yet</div>
                <p class="text-gray-400 text-sm">Use the "Sync Team Matches" button to fetch matches from Tennis Record</p>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Court Position Expand/Collapse
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

            if (!input || !clearBtn || !playersTable) return;

            const rows = Array.from(playersTable.querySelectorAll('tbody tr[data-name]'));
            let t;

            function applyFilter(term) {
                const q = term.trim().toLowerCase();

                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const show = !q || name.includes(q);
                    row.style.display = show ? '' : 'none';
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
        let singlesActive = {{ request('singles_verified') ? 'true' : 'false' }};
        let doublesActive = {{ request('doubles_verified') ? 'true' : 'false' }};

        function toggleSingles() {
            singlesActive = !singlesActive;
            filterSingles.classList.toggle('text-green-600', singlesActive);
            filterSingles.classList.toggle('text-gray-400', !singlesActive);
            updateURL();
        }

        function toggleDoubles() {
            doublesActive = !doublesActive;
            filterDoubles.classList.toggle('text-green-600', doublesActive);
            filterDoubles.classList.toggle('text-gray-400', !doublesActive);
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

            window.history.pushState({}, '', url);
            window.applyFilters();
        }

        window.applyFilters = function() {
            const table = document.getElementById('playersTable');
            const rows = table.querySelectorAll('tbody tr');

            // Get filter states from URL
            const urlParams = new URLSearchParams(window.location.search);
            const promoted = urlParams.get('promoted') === '1';
            const playingUp = urlParams.get('playing_up') === '1';

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
        }

        filterSingles.addEventListener('click', toggleSingles);
        filterDoubles.addEventListener('click', toggleDoubles);

        // Apply filters on page load if params exist
        window.applyFilters();
    })();

    // Promoted Players Filter
    (function() {
        const filterPromoted = document.getElementById('filterPromoted');
        if (!filterPromoted) return;

        let promotedActive = {{ request('promoted') ? 'true' : 'false' }};

        function togglePromoted(e) {
            e.stopPropagation();
            promotedActive = !promotedActive;
            if (promotedActive) {
                filterPromoted.classList.remove('grayscale', 'opacity-50');
            } else {
                filterPromoted.classList.add('grayscale', 'opacity-50');
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

            window.history.pushState({}, '', url);

            // Call the global applyFilters which handles all filters together
            if (window.applyFilters) {
                window.applyFilters();
            }
        }

        filterPromoted.addEventListener('click', togglePromoted);
    })();

    // Playing Up Filter
    (function() {
        const filterPlayingUp = document.getElementById('filterPlayingUp');
        if (!filterPlayingUp) return;

        let playingUpActive = {{ request('playing_up') ? 'true' : 'false' }};

        function togglePlayingUp(e) {
            e.stopPropagation();
            playingUpActive = !playingUpActive;
            if (playingUpActive) {
                filterPlayingUp.classList.remove('grayscale', 'opacity-50');
            } else {
                filterPlayingUp.classList.add('grayscale', 'opacity-50');
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

            window.history.pushState({}, '', url);

            // Call the global applyFilters which handles all filters together
            if (window.applyFilters) {
                window.applyFilters();
            }
        }

        filterPlayingUp.addEventListener('click', togglePlayingUp);
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
                window.location.href = sortUrl.toString();
            });
        });
    })();

    // League Lineup Comparison Chart
    @if($leagueLineupData && count($leagueLineupData) > 0)
        const lineupData = @json($leagueLineupData);
        const currentTeamId = {{ $team->id }};
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

                    svg += `<circle cx="${x}" cy="${y}" r="${point.radius}" fill="${point.color}" opacity="${point.opacity}" class="lineup-dot"
                            data-team="${point.teamData.team_name}"
                            data-player="${point.player.name}"
                            data-position="${point.position}"
                            data-utr="${point.player.utr_singles || 'N/A'}"
                            data-usta="${point.player.usta_dynamic || 'N/A'}"
                            style="cursor: pointer;"/>`;
                });
            });

            // Draw legend
            let legendY = margin.top;
            sortedTeamData.forEach((teamData, teamIndex) => {
                const color = colors[teamIndex % colors.length];
                const isCurrentTeam = teamData.team_id === currentTeamId;
                const fontWeight = isCurrentTeam ? 'bold' : 'normal';

                svg += `<rect x="${width - margin.right + 10}" y="${legendY}" width="15" height="15" fill="${color}"/>`;
                svg += `<text x="${width - margin.right + 30}" y="${legendY + 12}" font-size="12" font-weight="${fontWeight}" fill="#374151">${teamData.team_name}</text>`;
                legendY += 25;
            });

            svg += '</svg>';
            chartContainer.innerHTML = svg;

            // Add hover tooltips
            const dots = chartContainer.querySelectorAll('.lineup-dot');
            dots.forEach(dot => {
                dot.addEventListener('mouseenter', function(e) {
                    const team = this.dataset.team;
                    const player = this.dataset.player;
                    const position = this.dataset.position;
                    const utr = this.dataset.utr;
                    const usta = this.dataset.usta;

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
                        <div style="font-weight: bold;">${player}</div>
                        <div>${team} - #${position}</div>
                        ${ratingLine}
                    `;
                    document.body.appendChild(tooltip);
                });

                dot.addEventListener('mouseleave', function() {
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

                    svg += `<circle cx="${x}" cy="${y}" r="${point.radius}" fill="${point.color}" opacity="${point.opacity}" class="doubles-lineup-dot"
                            data-team="${point.teamData.team_name}"
                            data-player="${point.player.name}"
                            data-position="${point.position}"
                            data-utr="${point.player.utr_doubles || 'N/A'}"
                            data-usta="${point.player.usta_dynamic || 'N/A'}"
                            style="cursor: pointer;"/>`;
                });
            });

            // Draw legend
            let legendY = margin.top;
            sortedTeamData.forEach((teamData, teamIndex) => {
                const isCurrentTeam = teamData.team_id === currentDoublesTeamId;
                const color = colors[teamIndex % colors.length];
                const opacity = isCurrentTeam ? 1.0 : 0.5;
                const fontWeight = isCurrentTeam ? 'bold' : 'normal';

                svg += `<rect x="${width - margin.right + 10}" y="${legendY}" width="15" height="15" fill="${color}" opacity="${opacity}"/>`;
                svg += `<text x="${width - margin.right + 30}" y="${legendY + 12}" font-size="12" font-weight="${fontWeight}" fill="#374151">${teamData.team_name}</text>`;
                legendY += 25;
            });

            svg += '</svg>';
            chartContainer.innerHTML = svg;

            // Add hover tooltips
            const dots = chartContainer.querySelectorAll('.doubles-lineup-dot');
            dots.forEach(dot => {
                dot.addEventListener('mouseenter', function(e) {
                    const team = this.dataset.team;
                    const player = this.dataset.player;
                    const position = this.dataset.position;
                    const utr = this.dataset.utr;
                    const usta = this.dataset.usta;

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
                        <div style="font-weight: bold;">${player}</div>
                        <div>${team} - #${position}</div>
                        ${ratingLine}
                    `;
                    document.body.appendChild(tooltip);
                });

                dot.addEventListener('mouseleave', function() {
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
@endsection
