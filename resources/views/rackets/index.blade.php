@extends('layouts.app')

@section('title', 'Rackets List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Rackets List</h1>
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

    <div class="flex justify-between mb-4">
        <div class="flex items-center space-x-2">
            <input
                id="racketSearch"
                type="text"
                placeholder="Search rackets…"
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
            <a href="{{ route('rackets.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                + Add Racket
            </a>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table id="racketsTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Brand / Model</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Owner</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Weight</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">SW</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Current Strings</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Time Played</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($rackets as $racket)
                    <tr class="hover:bg-gray-50 cursor-pointer"
                        onclick="window.location='{{ route('rackets.show', $racket) }}'"
                        data-search="{{ strtolower($racket->name . ' ' . $racket->brand . ' ' . $racket->model . ' ' . ($racket->player ? $racket->player->first_name . ' ' . $racket->player->last_name : '')) }}">
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <a href="{{ route('rackets.show', $racket) }}" class="text-blue-600 hover:underline" onclick="event.stopPropagation()">
                                {{ $racket->name }}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            {{ $racket->brand }} {{ $racket->model }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            @if($racket->player)
                                <a href="{{ route('players.show', $racket->player) }}" class="text-blue-600 hover:underline" onclick="event.stopPropagation()">
                                    {{ $racket->player->first_name }} {{ $racket->player->last_name }}
                                </a>
                            @else
                                <span class="text-gray-400">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            @if($racket->weight)
                                {{ $racket->weight }}g
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            @if($racket->swing_weight)
                                {{ $racket->swing_weight }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            @if($racket->currentStringJob)
                                <div>
                                    <strong>M:</strong> {{ $racket->currentStringJob->mains_brand }}
                                    @if($racket->currentStringJob->mains_gauge)
                                        ({{ $racket->currentStringJob->mains_gauge }})
                                    @endif
                                    @ {{ $racket->currentStringJob->mains_tension }}lbs<br>
                                    <strong>C:</strong> {{ $racket->currentStringJob->crosses_brand }}
                                    @if($racket->currentStringJob->crosses_gauge)
                                        ({{ $racket->currentStringJob->crosses_gauge }})
                                    @endif
                                    @ {{ $racket->currentStringJob->crosses_tension }}lbs
                                </div>
                            @else
                                <span class="text-gray-400 italic">No strings yet</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            @if($racket->currentStringJob)
                                {{ $racket->currentStringJob->time_played }}h
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            No rackets found. <a href="{{ route('rackets.create') }}" class="text-blue-600 hover:underline">Add your first racket</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('racketSearch');
    const clearBtn = document.getElementById('clearSearch');
    const rows = Array.from(document.querySelectorAll('tbody tr[data-search]'));
    let t;

    function applyFilter(term) {
        const q = term.trim().toLowerCase();

        rows.forEach(row => {
            const searchText = row.getAttribute('data-search') || '';
            const show = !q || searchText.includes(q);
            row.style.display = show ? '' : 'none';
        });

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
</script>
@endsection
