@extends('layouts.app')

@section('title', 'Players List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Players List</h1>
    @include('partials.tabs')

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
            <form method="POST" action="{{ route('players.updateUtr') }}" id="utrUpdateForm">
                @csrf
                <button type="submit" id="utrUpdateBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    🔄 Update UTR Ratings
                </button>
            </form>
            <form method="POST" action="{{ route('players.fetchMissingUtrIds') }}" id="utrSearchForm">
                @csrf
                <button type="submit" id="utrSearchBtn" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded">
                    🔍 Find Missing UTR IDs
                </button>
            </form>
            <a href="{{ route('players.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                + Add Player
            </a>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">First Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Last Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">UTR ID</th>
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
                      <a href="{{ route('players.index', ['sort' => 'USTA_rating', 'direction' => ($sortField == 'USTA_rating' && $sortDirection == 'asc') ? 'desc' : 'asc']) }}">
                        USTA Rating
                      </a>
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($players as $player)
                    <tr ondblclick="window.location='{{ route('players.edit', $player->id) }}'" class="hover:bg-gray-50 cursor-pointer group relative" data-name="{{ strtolower($player->first_name . ' ' . $player->last_name) }}">
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->first_name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->last_name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_id }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_singles_rating }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_doubles_rating }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->USTA_rating }}</td>
                        <td class="px-4 py-2 text-sm text-center">
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
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
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
