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
                <form method="POST" action="{{ route('teams.syncFromTennisRecord', $team->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded">
                        🔄 Sync from Tennis Record
                    </button>
                </form>
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

        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table id="playersTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('teams.show', ['team' => $team->id, 'sort' => 'first_name', 'direction' => ($sortField === 'first_name' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                Name
                                @if($sortField === 'first_name' || $sortField === 'last_name')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('teams.show', ['team' => $team->id, 'sort' => 'utr_singles_rating', 'direction' => ($sortField === 'utr_singles_rating' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                UTR Singles Rating
                                @if($sortField === 'utr_singles_rating')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('teams.show', ['team' => $team->id, 'sort' => 'utr_doubles_rating', 'direction' => ($sortField === 'utr_doubles_rating' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                UTR Doubles Rating
                                @if($sortField === 'utr_doubles_rating')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('teams.show', ['team' => $team->id, 'sort' => 'USTA_dynamic_rating', 'direction' => ($sortField === 'USTA_dynamic_rating' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                USTA Dynamic Rating
                                @if($sortField === 'USTA_dynamic_rating')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Player Links</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($sortDirection === 'asc' ? $team->players->sortBy($sortField) : $team->players->sortByDesc($sortField) as $player)
                        <tr ondblclick="window.location='{{ route('players.edit', $player->id) }}?return_url={{ urlencode(route('teams.show', $team->id)) }}'" class="hover:bg-gray-50 cursor-pointer" data-name="{{ strtolower($player->first_name . ' ' . $player->last_name) }}">
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $player->first_name }} {{ $player->last_name }}</td>
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
                                                    $ratingClass = 'text-red-600 font-semibold';
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
                            <td class="px-4 py-2 text-sm text-center">
                                <form method="POST" action="{{ route('teams.removePlayer', [$team->id, $player->id]) }}" style="display:inline;"
                                      onsubmit="return confirm('Remove {{ $player->first_name }} {{ $player->last_name }} from this team?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs" onclick="event.stopPropagation()">
                                        Remove
                                    </button>
                                </form>
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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
</script>
@endsection
