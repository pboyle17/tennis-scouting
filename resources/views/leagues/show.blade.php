@extends('layouts.app')

@section('title', $league->name)

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">{{ $league->name }}</h1>
    @include('partials.tabs')

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4 font-semibold">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('status'))
        <div class="bg-blue-100 text-blue-700 p-4 rounded mb-4 font-semibold">
            ℹ {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4 font-semibold">
            ✖ {{ session('error') }}
        </div>
    @endif

    <!-- Tennis Record League Creation Progress -->
    <div id="leagueProgressContainer" class="hidden mb-4 bg-purple-50 border border-purple-200 rounded-lg p-4">
        <div class="flex items-center mb-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-purple-600 mr-2"></div>
            <span class="text-sm font-medium text-purple-800" id="leagueProgressTitle">Creating teams from Tennis Record league...</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
            <div id="leagueProgressBar" class="bg-purple-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
        <div class="text-xs text-gray-600">
            <div id="leagueProgressMessage">Starting...</div>
            <div id="leagueProgressDetails" class="mt-1 text-gray-500"></div>
        </div>
    </div>

    <!-- Tennis Record Profile Sync Progress -->
    <div id="trSyncProgressContainer" class="hidden mb-4 bg-orange-50 border border-orange-200 rounded-lg p-4">
        <div class="flex items-center mb-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-orange-600 mr-2"></div>
            <span class="text-sm font-medium text-orange-800" id="trSyncProgressTitle">Syncing Tennis Record profiles...</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
            <div id="trSyncProgressBar" class="bg-orange-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
        <div class="text-xs text-gray-600">
            <div id="trSyncProgressMessage">Starting...</div>
            <div id="trSyncProgressDetails" class="mt-1 text-gray-500"></div>
        </div>
    </div>

    <!-- UTR Search Results -->
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
                        <span class="text-sm text-gray-600 font-normal">({{ $player['team_name'] }})</span>
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
                                        $singlesReliability = $source['ratingProgressSingles'] ?? 0;
                                        $doublesReliability = $source['ratingProgressDoubles'] ?? 0;
                                        $utrId = $source['id'] ?? '';

                                        // Check if this is a single result with matching names (auto-selected)
                                        $isSingleMatch = count($results) === 1 &&
                                                        strtolower(trim($firstName)) === strtolower(trim($player['first_name'])) &&
                                                        strtolower(trim($lastName)) === strtolower(trim($player['last_name']));
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-sm">{{ $firstName }} {{ $lastName }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $location }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            {{ number_format($singlesUtr, 2) }}
                                            @if($singlesReliability == 100)
                                                <span class="text-green-600 font-bold" title="100% Reliable">✓</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            {{ number_format($doublesUtr, 2) }}
                                            @if($doublesReliability == 100)
                                                <span class="text-green-600 font-bold" title="100% Reliable">✓</span>
                                            @endif
                                        </td>
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
                                                <form class="utr-selection-form" data-player-id="{{ $player['id'] }}" data-player-name="{{ $player['first_name'] }} {{ $player['last_name'] }}" data-action="{{ route('leagues.setPlayerUtrData', ['league' => $league->id, 'player' => $player['id']]) }}" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="utr_id" value="{{ $utrId }}">
                                                    <input type="hidden" name="singles_utr" value="{{ $singlesUtr }}">
                                                    <input type="hidden" name="doubles_utr" value="{{ $doublesUtr }}">
                                                    <input type="hidden" name="singles_reliability" value="{{ $singlesReliability }}">
                                                    <input type="hidden" name="doubles_reliability" value="{{ $doublesReliability }}">
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

    <div class="flex justify-end mb-4 space-x-2">
        <button id="toggleAddTeams" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
            + Add Teams
        </button>
        @if($league->usta_link)
            <a href="{{ $league->usta_link }}" target="_blank" class="bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-2 px-4 rounded">
                🔗 USTA Link
            </a>
        @endif
        @if($league->tennis_record_link)
            <a href="{{ $league->tennis_record_link }}" target="_blank" class="bg-teal-500 hover:bg-teal-600 text-white font-semibold py-2 px-4 rounded">
                🔗 Tennis Record
            </a>
            <form method="POST" action="{{ route('leagues.createTeamsFromLeague', $league->id) }}" style="display:inline;" onsubmit="return confirm('This will scrape all teams from the Tennis Record league page and create them if they don\'t exist.\n\nThis may take several minutes. Continue?');">
                @csrf
                <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
                    🎾 Import League Teams
                </button>
            </form>
            <form method="POST" action="{{ route('leagues.syncTeamMatches', $league->id) }}" style="display:inline;">
                @csrf
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
                    📅 Sync Team Matches
                </button>
            </form>
        @endif
        <a href="{{ route('leagues.edit', $league->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
            Edit League
        </a>
        <form method="POST" action="{{ route('leagues.destroy', $league->id) }}" onsubmit="return confirm('Are you sure you want to delete this league? Teams will not be deleted.');" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
                Delete League
            </button>
        </form>
    </div>

    <!-- Add Teams Section -->
    <div id="addTeamsSection" class="bg-white rounded-lg shadow mb-6 hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Add Teams to League</h2>
                <button id="closeAddTeams" class="text-gray-500 hover:text-gray-700 text-2xl font-bold">&times;</button>
            </div>

            @if($availableTeams->count() > 0)
                <form method="POST" action="{{ route('leagues.addTeams', $league->id) }}" id="addTeamsForm">
                    @csrf

                    <div class="mb-4">
                        <input type="text" id="teamSearch" placeholder="Search teams..." class="w-full border rounded p-2">
                    </div>

                    <div class="mb-4 flex space-x-2">
                        <button type="button" id="selectAllTeams" class="bg-gray-500 hover:bg-gray-600 text-white text-sm py-1 px-3 rounded">
                            Select All
                        </button>
                        <button type="button" id="clearAllTeams" class="bg-gray-500 hover:bg-gray-600 text-white text-sm py-1 px-3 rounded">
                            Clear All
                        </button>
                        <span id="selectedCount" class="text-sm text-gray-600 py-1">0 selected</span>
                    </div>

                    <div class="max-h-96 overflow-y-auto border rounded p-4 mb-4">
                        @foreach($availableTeams as $team)
                            <div class="team-item mb-2">
                                <label class="flex items-center hover:bg-gray-50 p-2 rounded cursor-pointer">
                                    <input type="checkbox" name="team_ids[]" value="{{ $team->id }}" class="team-checkbox mr-3">
                                    <span class="team-name">{{ $team->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded w-full">
                        Add Selected Teams
                    </button>
                </form>
            @else
                <p class="text-gray-600">No available teams to add. All teams are either in this league or assigned to other leagues.</p>
            @endif
        </div>
    </div>

    <!-- Teams Table -->
    <div class="bg-white rounded-lg shadow mb-6">
        <table class="w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Team Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Players</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($league->teams as $team)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <a href="{{ route('teams.show', $team->id) }}" class="text-blue-600 hover:underline">
                                {{ $team->name }}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            {{ $team->players->count() }} {{ $team->players->count() === 1 ? 'player' : 'players' }}
                        </td>
                        <td class="px-4 py-2 text-sm">
                            @php
                                $teamPlayersWithoutUtrId = $team->players->whereNull('utr_id')->count();
                            @endphp

                            @if($teamPlayersWithoutUtrId > 0)
                                <form method="POST" action="{{ route('leagues.findMissingUtrIdsForTeam', [$league->id, $team->id]) }}" style="display:inline;" class="mr-2">
                                    @csrf
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs cursor-pointer" title="Find UTR IDs for {{ $teamPlayersWithoutUtrId }} player(s) without UTR IDs">
                                        🔍 Find UTR IDs ({{ $teamPlayersWithoutUtrId }})
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('leagues.removeTeam', [$league->id, $team->id]) }}" onsubmit="return confirm('Remove {{ $team->name }} from this league?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs cursor-pointer">Remove Team</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                            No teams in this league yet. Click "Add Teams" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Players Table -->
    @if($players->count() > 0)
        @php
            $playersWithUtr = $players->filter(fn($p) => $p->utr_id !== null)->count();
        @endphp
        <div class="mb-4 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    <span id="filterStatus">All Players in League</span>
                </h2>
                <div class="text-sm text-gray-600 mt-1">
                    <strong id="visiblePlayersCount">{{ $players->count() }}</strong> <span id="playersLabel">players</span>
                    <span id="totalPlayersText"></span>
                    across <strong id="visibleTeamsCount">{{ $league->teams->count() }}</strong> <span id="teamsLabel">teams</span>
                    <span id="totalTeamsText"></span>
                    <span id="utrText">
                        @if($playersWithUtr > 0)
                            | <strong id="visibleUtrCount">{{ $playersWithUtr }}</strong> with UTR IDs <span id="totalUtrText"></span>
                        @else
                            | <span class="text-orange-600 font-semibold">No players with UTR IDs</span>
                        @endif
                    </span>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                @php
                    $teamsWithTennisRecord = $league->teams()->whereNotNull('tennis_record_link')->count();
                @endphp
                @if($teamsWithTennisRecord > 0)
                    <form id="syncTeamsForm" method="POST" action="{{ route('leagues.syncAllTeams', $league->id) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" id="syncTeamIds" name="team_ids" value="">
                        <button type="submit" id="syncTeamsButton" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded cursor-pointer" title="Sync filtered teams from Tennis Record">
                            🎾 Sync Teams (<span id="syncTeamsCount">{{ $teamsWithTennisRecord }}</span>)
                        </button>
                    </form>
                @else
                    <button type="button" disabled class="bg-gray-400 text-white font-semibold py-2 px-4 rounded cursor-not-allowed" title="No teams with Tennis Record links found in this league">
                        🎾 Sync Teams (0)
                    </button>
                @endif
                @if($playersWithUtr > 0)
                    <form id="updateUtrForm" method="POST" action="{{ route('leagues.updateUtr', $league->id) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" id="updateUtrTeamIds" name="team_ids" value="">
                        <button type="submit" id="updateUtrButton" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded cursor-pointer" title="Update UTR ratings for filtered teams">
                            🔄 Update UTRs (<span id="updateUtrCount">{{ $playersWithUtr }}</span>)
                        </button>
                    </form>
                @else
                    <button type="button" disabled class="bg-gray-400 text-white font-semibold py-2 px-4 rounded cursor-not-allowed" title="No players with UTR IDs found in this league">
                        🔄 Update UTRs (0)
                    </button>
                @endif
                @php
                    $playersWithTennisRecord = $players->filter(fn($p) => $p->tennis_record_link !== null)->count();
                @endphp
                @if($playersWithTennisRecord > 0)
                    <form id="syncTrProfilesForm" method="POST" action="{{ route('leagues.syncTrProfiles', $league->id) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" id="syncTrProfilesTeamIds" name="team_ids" value="">
                        <button type="submit" id="syncTrProfilesButton" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded cursor-pointer" title="Sync USTA ratings from Tennis Record player profiles">
                            📋 Sync TR Profiles (<span id="syncTrProfilesCount">{{ $playersWithTennisRecord }}</span>)
                        </button>
                    </form>
                @else
                    <button type="button" disabled class="bg-gray-400 text-white font-semibold py-2 px-4 rounded cursor-not-allowed" title="No players with Tennis Record links found">
                        📋 Sync TR Profiles (0)
                    </button>
                @endif
                <div class="relative inline-block">
                    <button
                        id="teamFilterButton"
                        type="button"
                        class="border rounded px-3 py-2 bg-white hover:bg-gray-50 flex items-center space-x-2 cursor-pointer"
                    >
                        <span id="teamFilterLabel">All Teams</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div
                        id="teamFilterDropdown"
                        class="hidden absolute z-10 mt-1 w-64 bg-white border rounded shadow-lg max-h-64 overflow-y-auto"
                    >
                        <div class="p-2">
                            @php
                                $selectedTeams = request('teams') ? explode(',', request('teams')) : [];
                            @endphp
                            @foreach($league->teams->sortBy('name') as $team)
                                <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" class="team-filter-checkbox rounded" value="{{ $team->id }}" {{ in_array((string)$team->id, $selectedTeams) ? 'checked' : '' }}>
                                    <span>{{ $team->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <input
                    id="playerSearch"
                    type="text"
                    placeholder="Search by name…"
                    class="border rounded px-3 py-2 w-64"
                    value="{{ request('search', '') }}"
                />
                <button
                    id="clearFilters"
                    type="button"
                    class="invisible bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-3 rounded cursor-pointer"
                >
                    ✖ Clear All Filters
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <table id="playersTable" class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Rank</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <div class="flex items-center gap-2">
                                @if(!$league->is_combo)
                                    <span id="filterPromoted" class="cursor-pointer text-lg {{ request('promoted') ? '' : 'grayscale opacity-50' }}" title="Filter promoted players">🏅</span>
                                    <span id="filterPlayingUp" class="cursor-pointer text-lg {{ request('playing_up') ? '' : 'grayscale opacity-50' }}" title="Filter players playing up">⚔️</span>
                                @endif
                                <a href="{{ route('leagues.show', array_merge(['league' => $league->id, 'sort' => 'first_name', 'direction' => ($sortField === 'first_name' && $sortDirection === 'desc') ? 'asc' : 'desc'], request()->only(['teams', 'search', 'singles_verified', 'doubles_verified', 'promoted', 'playing_up']))) }}" class="hover:text-gray-900">
                                    Name
                                    @if($sortField === 'first_name' || $sortField === 'last_name')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </div>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('leagues.show', array_merge(['league' => $league->id, 'sort' => 'team_name', 'direction' => ($sortField === 'team_name' && $sortDirection === 'desc') ? 'asc' : 'desc'], request()->only(['teams', 'search', 'singles_verified', 'doubles_verified', 'promoted', 'playing_up']))) }}" class="hover:text-gray-900">
                                Team
                                @if($sortField === 'team_name')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <div class="flex items-center gap-2">
                                <span id="filterSinglesReliable" class="cursor-pointer text-lg font-bold {{ request('singles_verified') ? 'text-green-600' : 'text-gray-400' }}" title="Filter verified ratings">✓</span>
                                <a href="{{ route('leagues.show', array_merge(['league' => $league->id, 'sort' => 'utr_singles_rating', 'direction' => ($sortField === 'utr_singles_rating' && $sortDirection === 'desc') ? 'asc' : 'desc'], request()->only(['teams', 'search', 'singles_verified', 'doubles_verified', 'promoted', 'playing_up']))) }}" class="hover:text-gray-900">
                                    UTR Singles
                                    @if($sortField === 'utr_singles_rating')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </div>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <div class="flex items-center gap-2">
                                <span id="filterDoublesReliable" class="cursor-pointer text-lg font-bold {{ request('doubles_verified') ? 'text-green-600' : 'text-gray-400' }}" title="Filter verified ratings">✓</span>
                                <a href="{{ route('leagues.show', array_merge(['league' => $league->id, 'sort' => 'utr_doubles_rating', 'direction' => ($sortField === 'utr_doubles_rating' && $sortDirection === 'desc') ? 'asc' : 'desc'], request()->only(['teams', 'search', 'singles_verified', 'doubles_verified', 'promoted', 'playing_up']))) }}" class="hover:text-gray-900">
                                    UTR Doubles
                                    @if($sortField === 'utr_doubles_rating')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </div>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('leagues.show', array_merge(['league' => $league->id, 'sort' => 'USTA_dynamic_rating', 'direction' => ($sortField === 'USTA_dynamic_rating' && $sortDirection === 'desc') ? 'asc' : 'desc'], request()->only(['teams', 'search', 'singles_verified', 'doubles_verified', 'promoted', 'playing_up']))) }}" class="hover:text-gray-900">
                                USTA Dynamic
                                @if($sortField === 'USTA_dynamic_rating')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Player Links</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @php
                        $rank = 1;
                    @endphp
                    @foreach ($sortDirection === 'asc' ? $players->sortBy($sortField) : $players->sortByDesc($sortField) as $player)
                        @php
                            $isPromoted = $league->NTRP_rating && $player->USTA_rating && $player->USTA_rating > $league->NTRP_rating;
                            $isPlayingUp = $league->NTRP_rating && $player->USTA_rating && $player->USTA_rating < $league->NTRP_rating;
                        @endphp
                        <tr ondblclick="window.location='{{ route('players.edit', $player->id) }}?return_url={{ urlencode(route('leagues.show', $league->id)) }}'" class="hover:bg-gray-50 cursor-pointer" data-name="{{ strtolower($player->first_name . ' ' . $player->last_name . ' ' . $player->team_name) }}" data-team-id="{{ $player->team_id }}" data-has-utr="{{ $player->utr_id ? '1' : '0' }}" data-has-tr="{{ $player->tennis_record_link ? '1' : '0' }}" data-singles-reliable="{{ $player->utr_singles_reliable ? '1' : '0' }}" data-doubles-reliable="{{ $player->utr_doubles_reliable ? '1' : '0' }}" data-promoted="{{ $isPromoted ? '1' : '0' }}" data-playing-up="{{ $isPlayingUp ? '1' : '0' }}">
                            <td class="px-4 py-2 text-sm text-gray-700 font-semibold">{{ $rank++ }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                {{ $player->first_name }} {{ $player->last_name }}
                                @if(!$league->is_combo)
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
                                <a href="{{ route('teams.show', $player->team_id) }}" class="text-blue-600 hover:underline">
                                    {{ $player->team_name }}
                                </a>
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
                                            if (!$league->is_combo && $league->NTRP_rating) {
                                                if ($player->USTA_dynamic_rating >= $league->NTRP_rating) {
                                                    $ratingClass = 'text-green-600 font-semibold';
                                                } elseif ($league->NTRP_rating > 3.0 && $player->USTA_dynamic_rating <= $league->NTRP_rating - 0.5) {
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
    @endif
</div>

<script>
    // Toggle add teams section
    const toggleBtn = document.getElementById('toggleAddTeams');
    const closeBtn = document.getElementById('closeAddTeams');
    const addTeamsSection = document.getElementById('addTeamsSection');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            addTeamsSection.classList.toggle('hidden');
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            addTeamsSection.classList.add('hidden');
        });
    }

    // Team search functionality
    const searchInput = document.getElementById('teamSearch');
    const teamItems = document.querySelectorAll('.team-item');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            teamItems.forEach(item => {
                const teamName = item.querySelector('.team-name').textContent.toLowerCase();
                if (teamName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Select all / Clear all functionality
    const selectAllBtn = document.getElementById('selectAllTeams');
    const clearAllBtn = document.getElementById('clearAllTeams');
    const checkboxes = document.querySelectorAll('.team-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.team-checkbox:checked').length;
        if (selectedCountSpan) {
            selectedCountSpan.textContent = `${checkedCount} selected`;
        }
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            teamItems.forEach(item => {
                if (item.style.display !== 'none') {
                    const checkbox = item.querySelector('.team-checkbox');
                    if (checkbox) checkbox.checked = true;
                }
            });
            updateSelectedCount();
        });
    }

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            checkboxes.forEach(checkbox => checkbox.checked = false);
            updateSelectedCount();
        });
    }

    // Update count on checkbox change
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Initialize count
    updateSelectedCount();

    // Team filter dropdown functionality
    (function () {
        const button = document.getElementById('teamFilterButton');
        const dropdown = document.getElementById('teamFilterDropdown');
        const label = document.getElementById('teamFilterLabel');
        const teamCheckboxes = Array.from(document.querySelectorAll('.team-filter-checkbox'));

        // Toggle dropdown
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && !button.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Prevent dropdown from closing when clicking inside
        dropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Update label and apply filters when checkboxes change
        function updateLabelAndFilter() {
            const checkedBoxes = teamCheckboxes.filter(cb => cb.checked);

            if (checkedBoxes.length === 0) {
                label.textContent = 'All Teams';
            } else if (checkedBoxes.length === teamCheckboxes.length) {
                label.textContent = 'All Teams';
            } else if (checkedBoxes.length === 1) {
                label.textContent = '1 Team Selected';
            } else {
                label.textContent = `${checkedBoxes.length} Teams Selected`;
            }

            updateTeamURL();
        }

        function updateTeamURL() {
            const checkedBoxes = teamCheckboxes.filter(cb => cb.checked);
            const url = new URL(window.location);

            if (checkedBoxes.length === 0 || checkedBoxes.length === teamCheckboxes.length) {
                url.searchParams.delete('teams');
            } else {
                const teamIds = checkedBoxes.map(cb => cb.value).join(',');
                url.searchParams.set('teams', teamIds);
            }

            window.history.pushState({}, '', decodeURIComponent(url.toString()));
            if (window.applyFilters) {
                window.applyFilters();
            }
        }

        // Individual checkbox changes
        teamCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateLabelAndFilter);
        });

        // Initialize label on page load
        updateLabelAndFilter();
    })();

    // Player search and filter functionality
    (function () {
        const input = document.getElementById('playerSearch');
        const teamCheckboxes = Array.from(document.querySelectorAll('.team-filter-checkbox'));
        const clearFiltersBtn = document.getElementById('clearFilters');
        const rows = Array.from(document.querySelectorAll('tbody tr[data-name]'));
        let t;

        window.applyFilters = function() {
            const searchTerm = input.value.trim().toLowerCase();
            const selectedTeams = teamCheckboxes.filter(cb => cb.checked).map(cb => cb.value);

            // Get filter states from URL
            const urlParams = new URLSearchParams(window.location.search);
            const singlesVerified = urlParams.get('singles_verified') === '1';
            const doublesVerified = urlParams.get('doubles_verified') === '1';
            const promoted = urlParams.get('promoted') === '1';
            const playingUp = urlParams.get('playing_up') === '1';

            let visibleRank = 1;
            let visiblePlayers = 0;
            let visiblePlayersWithUtr = 0;
            let visiblePlayersWithTR = 0;
            const visibleTeamIds = new Set();

            const totalPlayers = rows.length;
            const totalTeams = teamCheckboxes.length;
            const totalPlayersWithUtr = rows.filter(row => row.getAttribute('data-has-utr') === '1').length;
            const totalPlayersWithTR = rows.filter(row => row.getAttribute('data-has-tr') === '1').length;

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const teamId = row.getAttribute('data-team-id') || '';
                const hasUtr = row.getAttribute('data-has-utr') === '1';
                const hasTR = row.getAttribute('data-has-tr') === '1';
                const singlesReliable = row.getAttribute('data-singles-reliable') === '1';
                const doublesReliable = row.getAttribute('data-doubles-reliable') === '1';
                const isPromoted = row.getAttribute('data-promoted') === '1';
                const isPlayingUp = row.getAttribute('data-playing-up') === '1';

                const matchesSearch = !searchTerm || name.includes(searchTerm);
                const matchesTeam = selectedTeams.length === 0 || selectedTeams.length === teamCheckboxes.length || selectedTeams.includes(teamId);
                const matchesSinglesVerified = !singlesVerified || singlesReliable;
                const matchesDoublesVerified = !doublesVerified || doublesReliable;
                const matchesPromoted = !promoted || isPromoted;
                const matchesPlayingUp = !playingUp || isPlayingUp;

                const show = matchesSearch && matchesTeam && matchesSinglesVerified && matchesDoublesVerified && matchesPromoted && matchesPlayingUp;
                row.style.display = show ? '' : 'none';

                // Update rank and counts for visible rows
                if (show) {
                    const rankCell = row.querySelector('td:first-child');
                    if (rankCell) {
                        rankCell.textContent = visibleRank++;
                    }
                    visiblePlayers++;
                    if (hasUtr) visiblePlayersWithUtr++;
                    if (hasTR) visiblePlayersWithTR++;
                    if (teamId) visibleTeamIds.add(teamId);
                }
            });

            // Update header text
            const isFiltered = searchTerm.length > 0 || (selectedTeams.length > 0 && selectedTeams.length < teamCheckboxes.length);
            document.getElementById('filterStatus').textContent = isFiltered ? 'Filtered Players' : 'All Players in League';
            document.getElementById('visiblePlayersCount').textContent = visiblePlayers;
            document.getElementById('playersLabel').textContent = visiblePlayers === 1 ? 'player' : 'players';
            document.getElementById('totalPlayersText').textContent = isFiltered ? ` of ${totalPlayers}` : '';
            document.getElementById('visibleTeamsCount').textContent = visibleTeamIds.size;
            document.getElementById('teamsLabel').textContent = visibleTeamIds.size === 1 ? 'team' : 'teams';
            document.getElementById('totalTeamsText').textContent = isFiltered ? ` of ${totalTeams}` : '';

            // Update UTR counts
            const visibleUtrCountEl = document.getElementById('visibleUtrCount');
            const totalUtrTextEl = document.getElementById('totalUtrText');
            if (visibleUtrCountEl) {
                visibleUtrCountEl.textContent = visiblePlayersWithUtr;
                if (totalUtrTextEl) {
                    totalUtrTextEl.textContent = isFiltered ? ` of ${totalPlayersWithUtr}` : '';
                }
            }

            // Update button counts and team IDs
            const selectedTeamIdsForSync = Array.from(visibleTeamIds);
            document.getElementById('syncTeamIds').value = selectedTeamIdsForSync.join(',');
            document.getElementById('updateUtrTeamIds').value = selectedTeamIdsForSync.join(',');
            document.getElementById('syncTrProfilesTeamIds').value = selectedTeamIdsForSync.join(',');
            document.getElementById('syncTeamsCount').textContent = selectedTeamIdsForSync.length;
            document.getElementById('updateUtrCount').textContent = visiblePlayersWithUtr;
            document.getElementById('syncTrProfilesCount').textContent = visiblePlayersWithTR;

            // Show clear all filters button when there's any active filter
            const hasSearch = searchTerm.length > 0;
            const hasTeamFilter = selectedTeams.length > 0 && selectedTeams.length < teamCheckboxes.length;
            const hasAnyFilter = hasSearch || hasTeamFilter || singlesVerified || doublesVerified || promoted;
            clearFiltersBtn.classList.toggle('invisible', !hasAnyFilter);
            clearFiltersBtn.style.pointerEvents = hasAnyFilter ? 'auto' : 'none';
        }

        function debouncedFilter() {
            clearTimeout(t);
            t = setTimeout(() => {
                updateSearchURL();
            }, 150);
        }

        function updateSearchURL() {
            const url = new URL(window.location);
            const searchTerm = input.value.trim();

            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }

            window.history.pushState({}, '', decodeURIComponent(url.toString()));
            applyFilters();
        }

        input.addEventListener('input', debouncedFilter);

        clearFiltersBtn.addEventListener('click', () => {
            input.value = '';
            teamCheckboxes.forEach(cb => cb.checked = false);
            document.getElementById('teamFilterLabel').textContent = 'All Teams';

            // Clear UTR verified filter checkmarks
            const filterSingles = document.getElementById('filterSinglesReliable');
            const filterDoubles = document.getElementById('filterDoublesReliable');
            const filterPromoted = document.getElementById('filterPromoted');
            const filterPlayingUp = document.getElementById('filterPlayingUp');
            if (filterSingles) {
                filterSingles.classList.remove('text-green-600');
                filterSingles.classList.add('text-gray-400');
            }
            if (filterDoubles) {
                filterDoubles.classList.remove('text-green-600');
                filterDoubles.classList.add('text-gray-400');
            }
            if (filterPromoted) {
                filterPromoted.classList.add('grayscale', 'opacity-50');
            }
            if (filterPlayingUp) {
                filterPlayingUp.classList.add('grayscale', 'opacity-50');
            }

            // Clear URL params
            const url = new URL(window.location);
            url.searchParams.delete('teams');
            url.searchParams.delete('search');
            url.searchParams.delete('singles_verified');
            url.searchParams.delete('doubles_verified');
            url.searchParams.delete('promoted');
            url.searchParams.delete('playing_up');
            window.history.pushState({}, '', decodeURIComponent(url.toString()));

            applyFilters();
        });

        // Apply filters on page load to respect query params
        applyFilters();
    })();

    // Tennis Record League Creation Progress tracking
    (function() {
        const progressContainer = document.getElementById('leagueProgressContainer');
        const progressBar = document.getElementById('leagueProgressBar');
        const progressTitle = document.getElementById('leagueProgressTitle');
        const progressMessage = document.getElementById('leagueProgressMessage');
        const progressDetails = document.getElementById('leagueProgressDetails');
        let progressInterval;

        // Check if we have a league job key from the session
        @if(session('tennis_record_league_job_key'))
            const leagueJobKey = '{{ session('tennis_record_league_job_key') }}';
            startLeagueProgressTracking(leagueJobKey);
        @endif

        function showLeagueProgress() {
            progressContainer.classList.remove('hidden');
        }

        function hideLeagueProgress() {
            progressContainer.classList.add('hidden');
        }

        function startLeagueProgressTracking(jobKey) {
            showLeagueProgress();

            progressInterval = setInterval(() => {
                fetch(`{{ route('leagues.leagueCreationProgress') }}?job_key=${jobKey}`)
                    .then(response => response.json())
                    .then(data => {
                        updateLeagueProgress(data);

                        if (data.status === 'completed') {
                            clearInterval(progressInterval);
                            setTimeout(() => {
                                hideLeagueProgress();
                                window.location.reload();
                            }, 3000);
                        } else if (data.status === 'failed') {
                            clearInterval(progressInterval);
                            hideLeagueProgress();
                            alert('League team creation failed. Please check the URL and try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Progress check error:', error);
                        clearInterval(progressInterval);
                        hideLeagueProgress();
                    });
            }, 2000); // Check every 2 seconds
        }

        function updateLeagueProgress(data) {
            progressBar.style.width = data.percentage + '%';
            progressMessage.textContent = data.message;

            let details = '';
            if (data.data && data.data.league_name) {
                details += `League: ${data.data.league_name}`;
            }
            if (data.data && data.data.current_team) {
                details += ` | Current: ${data.data.current_team}`;
            }
            if (data.data && data.data.total_teams) {
                details += ` | Progress: ${data.data.teams_processed || 0}/${data.data.total_teams}`;
            }
            if (data.data && data.data.teams_created !== undefined) {
                details += ` | Created: ${data.data.teams_created}`;
            }
            if (data.data && data.data.teams_existing !== undefined) {
                details += ` | Existing: ${data.data.teams_existing}`;
            }
            if (data.data && data.data.current_action) {
                details += ` | ${data.data.current_action}`;
            }

            progressDetails.textContent = details;
        }
    })();

    // Tennis Record Profile Sync Progress tracking
    (function() {
        const progressContainer = document.getElementById('trSyncProgressContainer');
        const progressBar = document.getElementById('trSyncProgressBar');
        const progressTitle = document.getElementById('trSyncProgressTitle');
        const progressMessage = document.getElementById('trSyncProgressMessage');
        const progressDetails = document.getElementById('trSyncProgressDetails');
        let progressInterval;

        // Check if we have a TR sync job key from the session
        @if(session('tr_sync_job_key'))
            const trSyncJobKey = '{{ session('tr_sync_job_key') }}';
            startTrSyncProgressTracking(trSyncJobKey);
        @endif

        function showTrSyncProgress() {
            progressContainer.classList.remove('hidden');
        }

        function hideTrSyncProgress() {
            progressContainer.classList.add('hidden');
        }

        function startTrSyncProgressTracking(jobKey) {
            showTrSyncProgress();

            progressInterval = setInterval(() => {
                fetch(`{{ route('leagues.trSyncProgress') }}?job_key=${jobKey}`)
                    .then(response => response.json())
                    .then(data => {
                        updateTrSyncProgress(data);

                        if (data.status === 'completed') {
                            clearInterval(progressInterval);
                            setTimeout(() => {
                                hideTrSyncProgress();
                                window.location.reload();
                            }, 3000);
                        } else if (data.status === 'failed') {
                            clearInterval(progressInterval);
                            hideTrSyncProgress();
                            alert('Tennis Record profile sync failed. Please check the logs for details.');
                        }
                    })
                    .catch(error => {
                        console.error('TR sync progress check error:', error);
                        clearInterval(progressInterval);
                        hideTrSyncProgress();
                    });
            }, 2000); // Check every 2 seconds
        }

        function updateTrSyncProgress(data) {
            progressBar.style.width = data.percentage + '%';
            progressMessage.textContent = data.message;

            let details = '';
            if (data.data && data.data.total_players) {
                details += `Total players: ${data.data.total_players}`;
            }
            if (data.data && data.data.updated !== undefined) {
                details += ` | Updated: ${data.data.updated}`;
            }
            if (data.data && data.data.skipped !== undefined && data.data.skipped > 0) {
                details += ` | Skipped: ${data.data.skipped}`;
            }
            if (data.data && data.data.errors !== undefined) {
                details += ` | Errors: ${data.data.errors}`;
            }
            if (data.data && data.data.current_player) {
                details += ` | Current: ${data.data.current_player}`;
            }

            progressDetails.textContent = details;
        }
    })();

    // UTR Selection Form Handler
    (function() {
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

                        // Remove error notification after 5 seconds
                        setTimeout(() => notification.remove(), 5000);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    const notification = document.createElement('div');
                    notification.className = 'bg-red-100 text-red-700 p-2 rounded mb-2';
                    notification.textContent = 'Network error: ' + error.message;
                    notificationsArea.appendChild(notification);

                    // Remove error notification after 5 seconds
                    setTimeout(() => notification.remove(), 5000);
                }
            });
        });
    })();

    // UTR Rating Filter
    (function() {
        const filterSingles = document.getElementById('filterSinglesReliable');
        const filterDoubles = document.getElementById('filterDoublesReliable');
        let singlesActive = {{ request('singles_verified') ? 'true' : 'false' }};
        let doublesActive = {{ request('doubles_verified') ? 'true' : 'false' }};

        function toggleSingles(e) {
            e.stopPropagation();
            singlesActive = !singlesActive;
            filterSingles.classList.toggle('text-green-600', singlesActive);
            filterSingles.classList.toggle('text-gray-400', !singlesActive);
            updateURL();
        }

        function toggleDoubles(e) {
            e.stopPropagation();
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

            window.history.pushState({}, '', decodeURIComponent(url.toString()));

            // Call the global applyFilters which handles all filters together
            if (window.applyFilters) {
                window.applyFilters();
            }
        }

        if (filterSingles) {
            filterSingles.addEventListener('click', toggleSingles);
        }
        if (filterDoubles) {
            filterDoubles.addEventListener('click', toggleDoubles);
        }
    })();

    // Promoted Players Filter
    (function() {
        const filterPromoted = document.getElementById('filterPromoted');
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

            window.history.pushState({}, '', decodeURIComponent(url.toString()));

            // Call the global applyFilters which handles all filters together
            if (window.applyFilters) {
                window.applyFilters();
            }
        }

        if (filterPromoted) {
            filterPromoted.addEventListener('click', togglePromoted);
        }
    })();

    // Playing Up Filter
    (function() {
        const filterPlayingUp = document.getElementById('filterPlayingUp');
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

            window.history.pushState({}, '', decodeURIComponent(url.toString()));

            // Call the global applyFilters which handles all filters together
            if (window.applyFilters) {
                window.applyFilters();
            }
        }

        if (filterPlayingUp) {
            filterPlayingUp.addEventListener('click', togglePlayingUp);
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
                const filterParams = ['teams', 'search', 'singles_verified', 'doubles_verified', 'promoted', 'playing_up'];
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
</script>
@endsection
