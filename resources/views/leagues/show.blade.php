@extends('layouts.app')

@section('title', $league->name)

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">{{ $league->name }}</h1>
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

    <div class="flex justify-end mb-4 space-x-2">
        <button id="toggleAddTeams" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
            + Add Teams
        </button>
        <a href="{{ route('leagues.edit', $league->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
            Edit League
        </a>
        <form method="POST" action="{{ route('leagues.destroy', $league->id) }}" onsubmit="return confirm('Are you sure you want to delete this league? Teams will not be deleted.');" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded">
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
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
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
                            <form method="POST" action="{{ route('leagues.removeTeam', [$league->id, $team->id]) }}" onsubmit="return confirm('Remove {{ $team->name }} from this league?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Remove</button>
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
</script>
@endsection
