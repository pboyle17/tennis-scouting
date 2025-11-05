@extends('layouts.app')

@section('title', 'Tournaments List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Tournaments List</h1>
    @include('partials.tabs')

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
            <button onclick="openUstaModal()" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                🏆 Create from USTA Link
            </button>
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
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">USTA Link</th>
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

<!-- USTA Link Modal -->
<div id="ustaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Create Tournament from USTA Link</h3>
            <button onclick="closeUstaModal()" class="text-gray-400 hover:text-gray-600">
                <span class="sr-only">Close</span>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="ustaForm" method="POST" action="{{ route('tournaments.createFromUstaLink') }}">
            @csrf
            <div class="mb-4">
                <label for="usta_link" class="block text-sm font-medium text-gray-700 mb-2">
                    USTA Tournament URL
                </label>
                <input type="url" name="usta_link" id="usta_link" required
                       placeholder="https://playtennis.usta.com/Competitions/.../Tournaments/..."
                       class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">
                    Paste the USTA tournament page URL from playtennis.usta.com
                </p>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                <h4 class="text-sm font-medium text-yellow-800 mb-1">What this will do:</h4>
                <ul class="text-xs text-yellow-700 list-disc list-inside space-y-1">
                    <li>Scrape tournament name and details from USTA page</li>
                    <li>Extract dates, location, and description</li>
                    <li>Create tournament with all available information</li>
                </ul>
                <p class="text-xs text-yellow-600 mt-2">
                    <strong>Example URL:</strong> https://playtennis.usta.com/Competitions/indianapolisracquetclubdean/Tournaments/overview/...
                </p>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeUstaModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                    Cancel
                </button>
                <button type="submit" id="ustaSubmitBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    🚀 Create Tournament
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // USTA Tournament Creation functionality
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

    // Handle form submission
    const ustaForm = document.getElementById('ustaForm');
    if (ustaForm) {
        ustaForm.addEventListener('submit', function(e) {
            const ustaLink = document.getElementById('usta_link').value;

            // Validate URL
            if (!ustaLink.includes('playtennis.usta.com')) {
                e.preventDefault();
                alert('Please enter a valid USTA tournament URL from playtennis.usta.com');
                return;
            }

            // Show loading state
            const submitBtn = document.getElementById('ustaSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Creating...';
        });
    }
</script>
@endsection
