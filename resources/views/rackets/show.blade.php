@extends('layouts.app')

@section('title', $racket->name)

@section('content')
<div class="container mx-auto p-6">
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

    <!-- Racket Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    {{ $racket->name }}
                </h1>
                <p class="text-lg text-gray-600">{{ $racket->brand }} {{ $racket->model }}</p>

                @if($racket->player)
                    <div class="mt-4">
                        <span class="text-sm font-semibold text-gray-600">Owner:</span>
                        <a href="{{ route('players.show', $racket->player) }}" class="inline-block bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full hover:bg-blue-200 transition">
                            {{ $racket->player->first_name }} {{ $racket->player->last_name }}
                        </a>
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-2">
                <a href="{{ route('rackets.edit', $racket) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    Edit Racket
                </a>
                <a href="{{ route('rackets.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                    Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Racket Specifications -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Racket Specifications</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-600 mb-1">Weight</div>
                <div class="text-xl font-bold text-gray-800">
                    {{ $racket->weight ? $racket->weight . 'g' : 'N/A' }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-600 mb-1">Swing Weight</div>
                <div class="text-xl font-bold text-gray-800">
                    {{ $racket->swing_weight ?? 'N/A' }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-600 mb-1">String Pattern</div>
                <div class="text-xl font-bold text-gray-800">
                    {{ $racket->string_pattern ?? 'N/A' }}
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm font-semibold text-gray-600 mb-1">Grip Size</div>
                <div class="text-xl font-bold text-gray-800">
                    {{ $racket->grip_size ?? 'N/A' }}
                </div>
            </div>

            @if($racket->notes)
                <div class="col-span-2 md:col-span-3 bg-gray-50 rounded-lg p-4">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Notes</div>
                    <div class="text-sm text-gray-700">
                        {{ $racket->notes }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Current String Setup -->
    @if($racket->currentStringJob)
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Current String Setup</h2>
                <a href="{{ route('rackets.string-jobs.create', $racket) }}" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded">
                    + Add New String Job
                </a>
            </div>

            @php
                $current = $racket->currentStringJob;
                $daysSince = now()->diffInDays($current->stringing_date);
            @endphp

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="col-span-2 md:col-span-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Mains (Vertical)</h4>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Mains Brand</div>
                    <div class="text-lg font-bold text-purple-600">
                        {{ $current->mains_brand }}
                    </div>
                    @if($current->mains_model)
                        <div class="text-sm text-gray-600">{{ $current->mains_model }}</div>
                    @endif
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Mains Gauge</div>
                    <div class="text-lg font-bold text-purple-600">
                        {{ $current->mains_gauge ?? 'N/A' }}
                    </div>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Mains Tension</div>
                    <div class="text-lg font-bold text-purple-600">
                        {{ $current->mains_tension }}lbs
                    </div>
                </div>

                <div class="col-span-2 md:col-span-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 mt-2">Crosses (Horizontal)</h4>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Crosses Brand</div>
                    <div class="text-lg font-bold text-purple-600">
                        {{ $current->crosses_brand }}
                    </div>
                    @if($current->crosses_model)
                        <div class="text-sm text-gray-600">{{ $current->crosses_model }}</div>
                    @endif
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Crosses Gauge</div>
                    <div class="text-lg font-bold text-purple-600">
                        {{ $current->crosses_gauge ?? 'N/A' }}
                    </div>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Crosses Tension</div>
                    <div class="text-lg font-bold text-purple-600">
                        {{ $current->crosses_tension }}lbs
                    </div>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Stringing Date</div>
                    <div class="text-lg font-bold text-purple-600">
                        {{ $current->stringing_date->format('M d, Y') }}
                    </div>
                    <div class="text-xs text-gray-600">{{ $daysSince }} days ago</div>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm font-semibold text-gray-600 mb-1">Time Played</div>
                    <div class="text-lg font-bold text-purple-600 mb-2">
                        {{ $current->time_played }}h
                    </div>
                    <button onclick="openAddTimeModal()" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded text-sm w-full">
                        + Add Time
                    </button>
                </div>

                @if($current->notes)
                    <div class="col-span-2 md:col-span-4 bg-purple-50 rounded-lg p-4">
                        <div class="text-sm font-semibold text-gray-600 mb-1">Notes</div>
                        <div class="text-sm text-gray-700">
                            {{ $current->notes }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-4 flex space-x-2">
                <a href="{{ route('string-jobs.edit', $current) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    Edit String Job
                </a>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Current String Setup</h2>
                    <p class="text-gray-600 italic">No strings configured yet</p>
                </div>
                <a href="{{ route('rackets.string-jobs.create', $racket) }}" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded">
                    + Add String Job
                </a>
            </div>
        </div>
    @endif

    <!-- String History -->
    @if($racket->stringJobs->count() > 0)
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">String Job History</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Strings</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Gauge</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Tension</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Time Played</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($racket->stringJobs as $stringJob)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    {{ $stringJob->stringing_date->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    <strong>M:</strong> {{ $stringJob->mains_brand }}
                                    @if($stringJob->mains_model)<span class="text-gray-500">{{ $stringJob->mains_model }}</span>@endif<br>
                                    <strong>C:</strong> {{ $stringJob->crosses_brand }}
                                    @if($stringJob->crosses_model)<span class="text-gray-500">{{ $stringJob->crosses_model }}</span>@endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    <strong>M:</strong> {{ $stringJob->mains_gauge ?? '-' }}<br>
                                    <strong>C:</strong> {{ $stringJob->crosses_gauge ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    <strong>M:</strong> {{ $stringJob->mains_tension }}lbs<br>
                                    <strong>C:</strong> {{ $stringJob->crosses_tension }}lbs
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    {{ $stringJob->time_played }}h
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    @if($stringJob->is_current)
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-semibold">Current</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">Past</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <div class="flex space-x-2">
                                        @if(!$stringJob->is_current)
                                            <form action="{{ route('string-jobs.setCurrent', $stringJob) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-semibold">
                                                    Set Current
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('string-jobs.edit', $stringJob) }}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </a>
                                        <form action="{{ route('string-jobs.destroy', $stringJob) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this string job?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Add Time Modal -->
    @if($racket->currentStringJob)
    <div id="addTimeModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Add Playing Time</h3>
                    <button onclick="closeAddTimeModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('string-jobs.addTime', $racket->currentStringJob) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2" for="modal_hours">Hours Played</label>
                        <input type="number" step="0.5" min="0.5" max="100" name="hours" id="modal_hours"
                               class="w-full border border-gray-300 p-3 rounded text-lg"
                               placeholder="Enter hours (e.g., 1.5)" required autofocus>
                        <p class="text-sm text-gray-500 mt-1">Current total: {{ $racket->currentStringJob->time_played }}h</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" onclick="closeAddTimeModal()"
                                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-4 rounded">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded">
                            Add Time
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function openAddTimeModal() {
    document.getElementById('addTimeModal').classList.remove('hidden');
    document.getElementById('modal_hours').focus();
}

function closeAddTimeModal() {
    document.getElementById('addTimeModal').classList.add('hidden');
    document.getElementById('modal_hours').value = '';
}

// Close modal when clicking outside
document.getElementById('addTimeModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddTimeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddTimeModal();
    }
});
</script>
@endsection
