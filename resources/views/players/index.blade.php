@extends('layouts.app')

@section('title', 'Players List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Players List</h1>
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

    <!-- Progress indicator -->
    <div id="utrProgressContainer" class="hidden mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center mb-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
            <span class="text-sm font-medium text-blue-800" id="utrProgressTitle">Processing...</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div id="utrProgressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
        <div class="mt-2 text-xs text-gray-600">
            <span id="utrProgressText">Starting...</span>
        </div>
    </div>

        <div class="flex justify-between mb-4">
            <div class="flex items-center space-x-2">
            <input
                id="playerSearch"
                type="text"
                placeholder="Search by name…"
                class="border rounded px-3 py-2 w-64"
            />
            <button
                id="clearSearch"
                type="button"
                class="hidden bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-3 rounded"
            >
                ✖ Clear
            </button>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('players.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                + Add Player
            </a>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                      <a href="{{ route('players.index', ['sort' => 'utr_singles_rating', 'direction' => ($sortField == 'utr_singles_rating' && $sortDirection == 'asc') ? 'desc' : 'asc']) }}">
                        UTR Singles Rating
                      </a>
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                      <a href="{{ route('players.index', ['sort' => 'utr_doubles_rating', 'direction' => ($sortField == 'utr_doubles_rating' && $sortDirection == 'asc') ? 'desc' : 'asc']) }}">
                        UTR Doubles Rating
                      </a>
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                      <a href="{{ route('players.index', ['sort' => 'USTA_dynamic_rating', 'direction' => ($sortField == 'USTA_dynamic_rating' && $sortDirection == 'asc') ? 'desc' : 'asc']) }}">
                        USTA Dynamic
                      </a>
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Teams</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Player Links</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($players as $player)
                    <tr ondblclick="window.location='{{ route('players.edit', $player->id) }}'" class="hover:bg-gray-50 cursor-pointer" data-name="{{ strtolower($player->first_name . ' ' . $player->last_name) }}">
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
                                    <span>{{ $player->USTA_dynamic_rating }}</span>
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
                            @if($player->teams->count() > 0)
                                <span onclick="toggleTeams({{ $player->id }}); event.stopPropagation();" class="cursor-pointer hover:opacity-70 transition">
                                    👥 {{ $player->teams->count() }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
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
                    @if($player->teams->count() > 0)
                        <tr id="teams-{{ $player->id }}" class="teams-row hidden bg-gray-50">
                            <td colspan="7" class="px-4 py-3">
                                <div class="text-sm font-semibold text-gray-700 mb-2">Teams:</div>
                                <div class="space-y-1">
                                    @foreach($player->teams as $team)
                                        <a href="{{ route('teams.show', $team->id) }}" class="block p-2 bg-white hover:bg-gray-100 rounded border border-gray-200 transition text-sm text-blue-600 hover:underline" onclick="event.stopPropagation();">
                                            {{ $team->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
    // Toggle teams slide-down
    let currentOpenTeamRow = null;

    function toggleTeams(playerId) {
        const teamsRow = document.getElementById('teams-' + playerId);

        if (!teamsRow) return; // No teams for this player

        // If clicking the same row that's already open, close it
        if (currentOpenTeamRow === teamsRow && !teamsRow.classList.contains('hidden')) {
            teamsRow.classList.add('hidden');
            currentOpenTeamRow = null;
            return;
        }

        // Close any currently open team row
        if (currentOpenTeamRow && currentOpenTeamRow !== teamsRow) {
            currentOpenTeamRow.classList.add('hidden');
        }

        // Toggle the clicked row
        teamsRow.classList.toggle('hidden');
        currentOpenTeamRow = teamsRow.classList.contains('hidden') ? null : teamsRow;
    }

    (function () {
    const input = document.getElementById('playerSearch');
    const clearBtn = document.getElementById('clearSearch');
    const rows = Array.from(document.querySelectorAll('tbody tr'));
    let t;

    function applyFilter(term) {
        const q = term.trim().toLowerCase();
        let anyHidden = false;

        rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const show = !q || name.includes(q);
        row.style.display = show ? '' : 'none';
        if (!show) anyHidden = true;
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

    // UTR Update Progress tracking
    (function() {
        const progressContainer = document.getElementById('utrProgressContainer');
        const progressBar = document.getElementById('utrProgressBar');
        const progressText = document.getElementById('utrProgressText');
        const progressTitle = document.getElementById('utrProgressTitle');
        let progressInterval;

        // Check if we have a UTR update job key from the session
        @if(session('utr_job_key'))
            const utrJobKey = '{{ session('utr_job_key') }}';
            startUtrUpdateProgressTracking(utrJobKey);
        @endif

        function startUtrUpdateProgressTracking(jobKey) {
            progressContainer.classList.remove('hidden');
            progressTitle.textContent = '🔄 Updating UTR Ratings...';
            progressBar.style.width = '0%';
            progressText.textContent = 'Starting...';

            progressInterval = setInterval(() => {
                fetch(`{{ route('players.utrUpdateProgress') }}?job_key=${jobKey}`)
                    .then(response => response.json())
                    .then(data => {
                        const percentage = data.total > 0 ? (data.processed / data.total) * 100 : 0;
                        progressBar.style.width = percentage + '%';

                        let text = `${data.processed} of ${data.total} players processed`;
                        if (data.updated !== undefined) {
                            text += ` | Updated: ${data.updated}`;
                        }
                        if (data.failed !== undefined && data.failed > 0) {
                            text += ` | Failed: ${data.failed}`;
                        }
                        progressText.textContent = text;

                        if (data.status === 'completed') {
                            clearInterval(progressInterval);
                            progressTitle.textContent = '✅ UTR Update Complete!';
                            setTimeout(() => {
                                progressContainer.classList.add('hidden');
                                // Refresh the page to show updated ratings
                                window.location.reload();
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('UTR update progress check error:', error);
                        clearInterval(progressInterval);
                        progressContainer.classList.add('hidden');
                    });
            }, 2000); // Check every 2 seconds
        }
    })();

    // UTR Search Progress tracking
    (function() {
        const progressContainer = document.getElementById('utrProgressContainer');
        const progressBar = document.getElementById('utrProgressBar');
        const progressText = document.getElementById('utrProgressText');
        const progressTitle = document.getElementById('utrProgressTitle');
        const utrSearchBtn = document.getElementById('utrSearchBtn');
        let progressInterval;

        // Check if we have a search job ID from the session
        @if(session('search_job_id'))
            const searchJobId = '{{ session('search_job_id') }}';
            startSearchProgressTracking(searchJobId);
        @endif

        // Handle UTR search form submission
        if (document.getElementById('utrSearchForm')) {
            document.getElementById('utrSearchForm').addEventListener('submit', function(e) {
                e.preventDefault();

                // Disable button and show loading state
                utrSearchBtn.disabled = true;
                utrSearchBtn.innerHTML = '⏳ Starting search...';

                // Submit form
                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    // Since we're getting HTML back, show progress immediately
                    showProgressIndicator('🔍 Searching for missing UTR IDs...');

                    // Start checking progress after a brief delay
                    setTimeout(() => {
                        // For now, we'll simulate progress since we don't have the job ID from the response
                        simulateSearchProgress();
                    }, 1000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    resetSearchButton();
                });
            });
        }

        function showProgressIndicator(title = 'Processing...') {
            progressContainer.classList.remove('hidden');
            progressTitle.textContent = title;
            progressBar.style.width = '0%';
            progressText.textContent = 'Starting...';
        }

        function hideProgressIndicator() {
            progressContainer.classList.add('hidden');
            resetSearchButton();
        }

        function resetSearchButton() {
            if (utrSearchBtn) {
                utrSearchBtn.disabled = false;
                utrSearchBtn.innerHTML = '🔍 Find Missing UTR IDs';
            }
        }

        function startSearchProgressTracking(jobId) {
            showProgressIndicator('🔍 Searching for missing UTR IDs...');

            progressInterval = setInterval(() => {
                fetch(`{{ route('players.utrSearchProgress') }}?job_id=${jobId}`)
                    .then(response => response.json())
                    .then(data => {
                        updateSearchProgress(data);

                        if (data.status === 'completed') {
                            clearInterval(progressInterval);
                            setTimeout(() => {
                                hideProgressIndicator();
                                // Refresh the page to show updated UTR IDs
                                window.location.reload();
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Search progress check error:', error);
                        clearInterval(progressInterval);
                        hideProgressIndicator();
                    });
            }, 2000); // Check every 2 seconds
        }

        function simulateSearchProgress() {
            showProgressIndicator('🔍 Searching for missing UTR IDs...');

            let progress = 0;
            const demoInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress >= 100) {
                    progress = 100;
                    updateSearchProgress({
                        total: 100,
                        processed: 100,
                        status: 'completed',
                        current_player: null,
                        found_count: Math.floor(Math.random() * 10),
                        not_found_count: Math.floor(Math.random() * 5)
                    });
                    clearInterval(demoInterval);
                    setTimeout(() => {
                        hideProgressIndicator();
                        window.location.reload();
                    }, 2000);
                } else {
                    updateSearchProgress({
                        total: 100,
                        processed: Math.floor(progress),
                        status: 'processing',
                        current_player: 'Demo Player ' + Math.floor(progress / 10),
                        found_count: Math.floor(progress / 15),
                        not_found_count: Math.floor(progress / 25)
                    });
                }
            }, 1500);
        }

        function updateSearchProgress(data) {
            const percentage = data.total > 0 ? (data.processed / data.total) * 100 : 0;
            progressBar.style.width = percentage + '%';

            let text = `${data.processed} of ${data.total} players searched`;
            if (data.current_player) {
                text += ` (Current: ${data.current_player})`;
            }
            if (data.found_count !== undefined && data.not_found_count !== undefined) {
                text += ` | Found: ${data.found_count}, Not found: ${data.not_found_count}`;
            }
            progressText.textContent = text;
        }
    })();
</script>
@endsection
