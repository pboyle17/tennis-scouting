@extends('layouts.app')

@section('title', 'Teams List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Teams List</h1>
    @include('partials.tabs')

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-2 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif
    @if(session('status'))
        <div class="bg-blue-100 text-blue-700 p-2 rounded mb-4">
            {{ session('status') }}
        </div>
    @endif

    <!-- USTA Team Creation Progress -->
    <div id="ustaProgressContainer" class="hidden mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center mb-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
            <span class="text-sm font-medium text-blue-800" id="ustaProgressTitle">Creating team from USTA link...</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
            <div id="ustaProgressBar" class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
        <div class="text-xs text-gray-600">
            <div id="ustaProgressMessage">Starting...</div>
            <div id="ustaProgressDetails" class="mt-1 text-gray-500"></div>
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        <div class="text-sm text-gray-600">
            💡 <strong>Single-click</strong> to view players | <strong>Double-click</strong> to edit team
        </div>
        <div class="flex space-x-2">
            <button onclick="openUstaModal()" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                🏆 Create from USTA Link
            </button>
            <a href="{{ route('teams.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                + Add Team
            </a>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            Name
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            USTA Link
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            Tennis Record Link
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($teams as $team)
                    <tr onclick="window.location='{{ route('teams.show', $team->id) }}'" ondblclick="window.location='{{ route('teams.edit', $team->id) }}'" class="hover:bg-gray-50 cursor-pointer">
                        <td class="px-4 py-2 text-sm text-gray-700">
                            {{ $team->name }}
                            <span class="text-xs text-gray-500 ml-2">({{ $team->players->count() }} players)</span>
                        </td>
                        <td class="py-2 text-sm">
                            @if($team->usta_link)
                                <a class='inline-block' href="{{ $team->usta_link }}" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ asset('images/usta_logo.png') }}" alt="USTA Link" class="h-10 w-15">
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xl text-blue-600">
                            @if($team->tennis_record_link)
                                <a href="{{ $team->tennis_record_link }}" target="_blank" rel="noopener noreferrer">
                                  🎾
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- USTA Link Modal -->
<div id="ustaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Create Team from USTA Link</h3>
            <button onclick="closeUstaModal()" class="text-gray-400 hover:text-gray-600">
                <span class="sr-only">Close</span>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="ustaForm" method="POST" action="{{ route('teams.createFromUstaLink') }}">
            @csrf
            <div class="mb-4">
                <label for="usta_link" class="block text-sm font-medium text-gray-700 mb-2">
                    USTA TennisLink URL
                </label>
                <input type="url" name="usta_link" id="usta_link" required
                       placeholder="https://tennislink.usta.com/Leagues/Main/StatsAndStandings.aspx..."
                       class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">
                    Paste the USTA TennisLink roster/standings page URL
                </p>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                <h4 class="text-sm font-medium text-yellow-800 mb-1">What this will do:</h4>
                <ul class="text-xs text-yellow-700 list-disc list-inside space-y-1">
                    <li>Scrape team name and player list from USTA page</li>
                    <li>Create team and add all players</li>
                    <li>Search for UTR IDs for each player</li>
                    <li>Fetch UTR ratings for found players</li>
                </ul>
                <p class="text-xs text-yellow-600 mt-2">
                    <strong>Note:</strong> This process may take several minutes to complete.
                </p>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeUstaModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                    Cancel
                </button>
                <button type="submit" id="ustaSubmitBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    🚀 Create Team
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // USTA Team Creation functionality
    function openUstaModal() {
        document.getElementById('ustaModal').classList.remove('hidden');
        document.getElementById('ustaModal').classList.add('flex');
        document.getElementById('usta_link').focus();
    }

    function closeUstaModal() {
        document.getElementById('ustaModal').classList.add('hidden');
        document.getElementById('ustaModal').classList.remove('flex');
        document.getElementById('usta_link').value = '';
    }

    // Close modal when clicking outside
    document.getElementById('ustaModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUstaModal();
        }
    });

    // USTA Team Creation Progress tracking
    (function() {
        const progressContainer = document.getElementById('ustaProgressContainer');
        const progressBar = document.getElementById('ustaProgressBar');
        const progressTitle = document.getElementById('ustaProgressTitle');
        const progressMessage = document.getElementById('ustaProgressMessage');
        const progressDetails = document.getElementById('ustaProgressDetails');
        const ustaForm = document.getElementById('ustaForm');
        const ustaSubmitBtn = document.getElementById('ustaSubmitBtn');
        let progressInterval;

        // Check if we have a USTA job ID from the session
        @if(session('usta_job_id'))
            const ustaJobId = '{{ session('usta_job_id') }}';
            startUstaProgressTracking(ustaJobId);
        @endif

        // Handle USTA form submission
        if (ustaForm) {
            ustaForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate URL
                const ustaLink = document.getElementById('usta_link').value;
                if (!ustaLink.includes('tennislink.usta.com')) {
                    alert('Please enter a valid USTA TennisLink URL');
                    return;
                }

                // Disable button and show loading state
                ustaSubmitBtn.disabled = true;
                ustaSubmitBtn.innerHTML = '⏳ Starting...';

                // Close modal
                closeUstaModal();

                // Submit form
                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        resetUstaButton();
                    } else if (data.job_id) {
                        startUstaProgressTracking(data.job_id);
                    } else {
                        // Fallback to showing progress immediately
                        showUstaProgress();
                        simulateUstaProgress();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    resetUstaButton();
                });
            });
        }

        function showUstaProgress() {
            progressContainer.classList.remove('hidden');
        }

        function hideUstaProgress() {
            progressContainer.classList.add('hidden');
            resetUstaButton();
        }

        function resetUstaButton() {
            if (ustaSubmitBtn) {
                ustaSubmitBtn.disabled = false;
                ustaSubmitBtn.innerHTML = '🚀 Create Team';
            }
        }

        function startUstaProgressTracking(jobId) {
            showUstaProgress();

            progressInterval = setInterval(() => {
                fetch(`{{ route('teams.ustaCreationProgress') }}?job_id=${jobId}`)
                    .then(response => response.json())
                    .then(data => {
                        updateUstaProgress(data);

                        if (data.status === 'completed') {
                            clearInterval(progressInterval);
                            setTimeout(() => {
                                hideUstaProgress();
                                window.location.reload();
                            }, 3000);
                        } else if (data.status === 'failed') {
                            clearInterval(progressInterval);
                            hideUstaProgress();
                            alert('Team creation failed. Please check the URL and try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Progress check error:', error);
                        clearInterval(progressInterval);
                        hideUstaProgress();
                    });
            }, 2000); // Check every 2 seconds
        }

        function updateUstaProgress(data) {
            progressBar.style.width = data.percentage + '%';
            progressMessage.textContent = data.message;

            let details = '';
            if (data.data && data.data.team_name) {
                details += `Team: ${data.data.team_name}`;
            }
            if (data.data && data.data.current_player) {
                details += ` | Current: ${data.data.current_player}`;
            }
            if (data.data && data.data.total_players) {
                details += ` | Players: ${data.data.players_created + data.data.players_found}/${data.data.total_players}`;
            }
            if (data.data && data.data.utr_ids_found !== undefined) {
                details += ` | UTR IDs: ${data.data.utr_ids_found}`;
            }
            if (data.data && data.data.ratings_updated !== undefined) {
                details += ` | Ratings: ${data.data.ratings_updated}`;
            }

            progressDetails.textContent = details;
        }

        function simulateUstaProgress() {
            // Fallback simulation if real progress tracking isn't available
            showUstaProgress();
            let step = 0;
            const steps = [
                'Scraping USTA page...',
                'Creating team...',
                'Creating players...',
                'Searching for UTR IDs...',
                'Fetching UTR ratings...',
                'Completed!'
            ];

            const stepInterval = setInterval(() => {
                step++;
                progressBar.style.width = (step / steps.length) * 100 + '%';
                progressMessage.textContent = steps[step - 1] || 'Processing...';

                if (step >= steps.length) {
                    clearInterval(stepInterval);
                    setTimeout(() => {
                        hideUstaProgress();
                        window.location.reload();
                    }, 2000);
                }
            }, 15000); // 15 seconds per step
        }
    })();
</script>
@endsection
