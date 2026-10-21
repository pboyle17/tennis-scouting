@extends('layouts.app')

@section('title', 'Tournaments List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Tournaments List</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4 font-semibold">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4 font-semibold">
            ✖ {{ session('error') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-4">
        <div class="text-sm text-gray-600">
            💡 <strong>Click</strong> a tournament name to view players
        </div>
        <div class="flex space-x-2">
            @env('local')
                <button onclick="openUtrModal()" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    🏆 Create from UTR Link
                </button>
            @endenv
            <a href="{{ route('tournaments.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                + Add Tournament
            </a>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Dates</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Location</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Links</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($tournaments as $tournament)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <a href="{{ route('tournaments.show', $tournament->id) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $tournament->name }}
                            </a>
                            <span class="text-xs text-gray-500 ml-2">({{ $tournament->players_count }} players)</span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            @if($tournament->start_date)
                                {{ $tournament->start_date->format('M d, Y') }}
                                @if($tournament->end_date && !$tournament->start_date->isSameDay($tournament->end_date))
                                    - {{ $tournament->end_date->format('M d, Y') }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $tournament->location ?? '-' }}</td>
                        <td class="py-2 text-sm">
                            @if($tournament->usta_link)
                                <a class='inline-block' href="{{ $tournament->usta_link }}" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ asset('images/usta_logo.png') }}" alt="USTA Link" class="h-10 w-15">
                                </a>
                            @endif
                            @if($tournament->utr_link)
                                <a class='inline-block ml-1 align-middle' href="{{ $tournament->utr_link }}" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR Link" class="h-8 w-8">
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <a href="{{ route('tournaments.edit', $tournament->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            No tournaments yet. Click "Add Tournament" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@env('local')
<!-- UTR Link Modal -->
<div id="utrModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Create Tournament from UTR Link</h3>
            <button onclick="closeUtrModal()" class="text-gray-400 hover:text-gray-600">
                <span class="sr-only">Close</span>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="utrForm" method="POST" action="{{ route('tournaments.createFromUtrLink') }}">
            @csrf
            <div class="mb-4">
                <label for="utr_link" class="block text-sm font-medium text-gray-700 mb-2">
                    UTR Event URL
                </label>
                <input type="url" name="utr_link" id="utr_link" required
                       placeholder="https://app.utrsports.net/tournaments/3560084"
                       class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">
                    Paste the event's page URL from app.utrsports.net
                </p>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                <h4 class="text-sm font-medium text-yellow-800 mb-1">What this will do:</h4>
                <ul class="text-xs text-yellow-700 list-disc list-inside space-y-1">
                    <li>Create the tournament with its name and dates from UTR</li>
                    <li>Sync every draw and match result in the background</li>
                    <li>Add the event's players, creating any that are missing</li>
                </ul>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeUtrModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                    Cancel
                </button>
                <button type="submit" id="utrSubmitBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    🚀 Create &amp; Sync
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUtrModal() {
        document.getElementById('utrModal').classList.remove('hidden');
        document.getElementById('utrModal').classList.add('flex');
        document.getElementById('utr_link').focus();
    }

    function closeUtrModal() {
        document.getElementById('utrModal').classList.add('hidden');
        document.getElementById('utrModal').classList.remove('flex');
        document.getElementById('utr_link').value = '';
    }

    // Close modal when clicking outside
    document.getElementById('utrModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUtrModal();
        }
    });

    const utrForm = document.getElementById('utrForm');
    if (utrForm) {
        utrForm.addEventListener('submit', function(e) {
            const utrLink = document.getElementById('utr_link').value;
            if (!/utrsports\.net\/(tournaments|events)\/\d+/.test(utrLink)) {
                e.preventDefault();
                alert('Please enter a UTR event URL like https://app.utrsports.net/tournaments/3560084');
                return;
            }
            const submitBtn = document.getElementById('utrSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Creating...';
        });
    }
</script>
@endenv
@endsection
