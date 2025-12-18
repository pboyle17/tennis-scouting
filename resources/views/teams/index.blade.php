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

    <div class="flex justify-between items-center mb-4">
        <div class="text-sm text-gray-600">
            💡 <strong>Click</strong> a team name to view players
        </div>
        <div class="flex space-x-2">
            @env('local')
                <button onclick="openTennisRecordModal()" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded">
                    🎾 Create from Tennis Record
                </button>
            @endenv
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
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($teams as $team)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <a href="{{ route('teams.show', $team->id) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $team->name }}
                            </a>
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
                        <td class="px-4 py-2 text-sm">
                            <a href="{{ route('teams.edit', $team->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                Edit
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Tennis Record Link Modal -->
<div id="tennisRecordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Create Team from Tennis Record</h3>
            <button onclick="closeTennisRecordModal()" class="text-gray-400 hover:text-gray-600">
                <span class="sr-only">Close</span>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="tennisRecordForm" method="POST" action="{{ route('teams.createFromTennisRecord') }}">
            @csrf
            <div class="mb-4">
                <label for="tennis_record_link" class="block text-sm font-medium text-gray-700 mb-2">
                    Tennis Record Team URL
                </label>
                <input type="url" name="tennis_record_link" id="tennis_record_link" required
                       placeholder="https://www.tennisrecord.com/adult/teamprofile.aspx?teamname=..."
                       class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                <p class="mt-1 text-xs text-gray-500">
                    Paste the Tennis Record team profile page URL
                </p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-4">
                <h4 class="text-sm font-medium text-blue-800 mb-1">What this will do:</h4>
                <ul class="text-xs text-blue-700 list-disc list-inside space-y-1">
                    <li>Scrape team name and player list from Tennis Record</li>
                    <li>Create team and add all players</li>
                    <li>Search for UTR IDs for each player</li>
                    <li>Fetch UTR ratings for found players</li>
                </ul>
                <p class="text-xs text-blue-600 mt-2">
                    <strong>Note:</strong> This process may take a few minutes to complete.
                </p>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeTennisRecordModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                    Cancel
                </button>
                <button type="submit" id="tennisRecordSubmitBtn" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded">
                    🚀 Create Team
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tennis Record Team Creation functionality
    function openTennisRecordModal() {
        document.getElementById('tennisRecordModal').classList.remove('hidden');
        document.getElementById('tennisRecordModal').classList.add('flex');
        document.getElementById('tennis_record_link').focus();
    }

    function closeTennisRecordModal() {
        document.getElementById('tennisRecordModal').classList.add('hidden');
        document.getElementById('tennisRecordModal').classList.remove('flex');
        document.getElementById('tennis_record_link').value = '';
    }

    // Close modal when clicking outside
    document.getElementById('tennisRecordModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeTennisRecordModal();
        }
    });
</script>
@endsection
