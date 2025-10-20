@extends('layouts.app')

@section('title', 'Team Players - ' . $team->name)

@section('content')
<div class="container mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-center text-gray-800">{{ $team->name }} - Players</h1>
        <a href="{{ route('teams.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
            ← Back to Teams
        </a>
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
                    🎾 Tennis Record
                </a>
            @endif
        </div>
    </div>

    <!-- Add Player Section -->
    @if($availablePlayers->count() > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Add Player to Team</h3>
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
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">First Name</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Last Name</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">UTR Singles Rating</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">UTR Doubles Rating</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">USTA Rating</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">UTR Profile</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($team->players->sortByDesc('utr_singles_rating') as $player)
                        <tr ondblclick="window.location='{{ route('players.edit', $player->id) }}?return_url={{ urlencode(route('teams.show', $team->id)) }}'" class="hover:bg-gray-50 cursor-pointer group relative">
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $player->first_name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $player->last_name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_singles_rating }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_doubles_rating }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $player->USTA_rating }}</td>
                            <td class="px-4 py-2 text-sm text-center">
                                @if($player->utr_id)
                                    <a href="https://app.utrsports.net/profiles/{{ $player->utr_id }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center">
                                        <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR Profile" class="h-5 w-5">
                                    </a>
                                    <!-- Tooltip -->
                                    <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2
                                                opacity-0 group-hover:opacity-100 transition
                                                bg-gray-800 text-white text-xs rounded py-1 px-2
                                                whitespace-nowrap z-10">
                                        Updated: {{ $player->updated_at->format('M d, Y h:i A') }}
                                    </div>
                                @endif
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
    });
</script>
@endsection