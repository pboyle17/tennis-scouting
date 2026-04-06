@extends('layouts.app')

@section('title', 'Leagues List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Leagues List</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-4">
        <div>
            @php $showInactive = request()->boolean('show_inactive'); @endphp
            @if($showInactive)
                <a href="{{ route('leagues.index') }}"
                   class="inline-block text-sm px-3 py-1 rounded-full bg-blue-600 text-white hover:bg-blue-700">
                    Show Inactive Leagues &times;
                </a>
            @else
                <a href="{{ route('leagues.index', ['show_inactive' => 1]) }}"
                   class="inline-block text-sm px-3 py-1 rounded-full bg-gray-300 text-gray-700 hover:bg-gray-400 hover:text-gray-900 cursor-pointer">
                    Show Inactive Leagues
                </a>
            @endif
        </div>
        @env('local')
            <a href="{{ route('leagues.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                + Add League
            </a>
        @endenv
    </div>

    <!-- Mobile Card View -->
    <div class="md:hidden space-y-4">
        @foreach ($leagues as $league)
            <div class="{{ $league->active ? 'bg-white' : 'bg-gray-100 opacity-70' }} rounded-lg shadow p-4">
                <div class="mb-3 flex items-center gap-2">
                    <a href="{{ route('leagues.show', $league->id) }}" class="text-lg font-semibold text-blue-600 hover:underline">
                        {{ $league->name }}
                    </a>
                    @if(!$league->active)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 text-gray-500">Inactive</span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="font-semibold text-gray-600">UTR Updated:</span>
                        <span class="text-gray-700 ml-1">
                            @if($league->utr_last_updated_at)
                                <span title="{{ $league->utr_last_updated_at->format('Y-m-d H:i') }}">{{ $league->utr_last_updated_at->diffForHumans() }}</span>
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">Teams Synced:</span>
                        <span class="text-gray-700 ml-1">
                            @if($league->teams_last_synced_at)
                                <span title="{{ $league->teams_last_synced_at->format('Y-m-d H:i') }}">{{ $league->teams_last_synced_at->diffForHumans() }}</span>
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">S1 UTR:</span>
                        <span class="text-gray-700 ml-1">
                            @if($league->courtAverages['s1'] && $league->courtAverages['s1']['utr'])
                                {{ number_format($league->courtAverages['s1']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">S2 UTR:</span>
                        <span class="text-gray-700 ml-1">
                            @if($league->courtAverages['s2'] && $league->courtAverages['s2']['utr'])
                                {{ number_format($league->courtAverages['s2']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">D1 UTR:</span>
                        <span class="text-gray-700 ml-1">
                            @if($league->courtAverages['d1'] && $league->courtAverages['d1']['utr'])
                                {{ number_format($league->courtAverages['d1']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">D2 UTR:</span>
                        <span class="text-gray-700 ml-1">
                            @if($league->courtAverages['d2'] && $league->courtAverages['d2']['utr'])
                                {{ number_format($league->courtAverages['d2']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">D3 UTR:</span>
                        <span class="text-gray-700 ml-1">
                            @if($league->courtAverages['d3'] && $league->courtAverages['d3']['utr'])
                                {{ number_format($league->courtAverages['d3']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </span>
                    </div>
                </div>

                @env('local')
                    <div class="mt-3 pt-3 border-t border-gray-200 flex gap-3 flex-wrap items-center">
                        <a href="{{ route('leagues.edit', $league->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
                        <form method="POST" action="{{ route('leagues.updateLeague', $league->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium cursor-pointer">Update League</button>
                        </form>
                        <form method="POST" action="{{ route('leagues.toggleDailyUpdate', $league->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="text-sm font-medium cursor-pointer {{ $league->daily_update ? 'text-green-600 hover:text-red-600' : 'text-gray-400 hover:text-green-600' }}" title="{{ $league->daily_update ? 'Daily update ON — click to disable' : 'Daily update OFF — click to enable' }}">
                                ⏰ {{ $league->daily_update ? 'Daily: On' : 'Daily: Off' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('leagues.updateDailyTime', $league->id) }}" style="display:inline;">
                            @csrf
                            <input type="time" name="daily_update_time" value="{{ $league->daily_update_time ?? '05:00' }}"
                                class="text-xs border border-gray-300 rounded px-1 py-0.5 text-gray-700"
                                onchange="this.form.submit()">
                        </form>
                    </div>
                @endenv
            </div>
        @endforeach
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">S1 UTR</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">S2 UTR</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">D1 UTR</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">D2 UTR</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">D3 UTR</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">UTR Updated</th>
                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Teams Synced</th>
                    @env('local')
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    @endenv
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($leagues as $league)
                    <tr class="{{ $league->active ? 'hover:bg-gray-50' : 'bg-gray-100 opacity-70 hover:bg-gray-200' }}">
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <a href="{{ route('leagues.show', $league->id) }}" class="text-blue-600 hover:underline">
                                {{ $league->name }}
                            </a>
                            @if(!$league->active)
                                <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-gray-200 text-gray-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                            @if($league->courtAverages['s1'] && $league->courtAverages['s1']['utr'])
                                {{ number_format($league->courtAverages['s1']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                            @if($league->courtAverages['s2'] && $league->courtAverages['s2']['utr'])
                                {{ number_format($league->courtAverages['s2']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                            @if($league->courtAverages['d1'] && $league->courtAverages['d1']['utr'])
                                {{ number_format($league->courtAverages['d1']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                            @if($league->courtAverages['d2'] && $league->courtAverages['d2']['utr'])
                                {{ number_format($league->courtAverages['d2']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700 text-center">
                            @if($league->courtAverages['d3'] && $league->courtAverages['d3']['utr'])
                                {{ number_format($league->courtAverages['d3']['utr'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-center">
                            @if($league->utr_last_updated_at)
                                <span class="text-gray-600" title="{{ $league->utr_last_updated_at->format('Y-m-d H:i') }}">{{ $league->utr_last_updated_at->diffForHumans() }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-center">
                            @if($league->teams_last_synced_at)
                                <span class="text-gray-600" title="{{ $league->teams_last_synced_at->format('Y-m-d H:i') }}">{{ $league->teams_last_synced_at->diffForHumans() }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        @env('local')
                            <td class="px-4 py-2 text-sm">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('leagues.edit', $league->id) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                                    <form method="POST" action="{{ route('leagues.updateLeague', $league->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-800 cursor-pointer">Update League</button>
                                    </form>
                                    <form method="POST" action="{{ route('leagues.toggleDailyUpdate', $league->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="cursor-pointer {{ $league->daily_update ? 'text-green-600 hover:text-red-600' : 'text-gray-400 hover:text-green-600' }}" title="{{ $league->daily_update ? 'Daily update ON — click to disable' : 'Daily update OFF — click to enable' }}">
                                            ⏰ {{ $league->daily_update ? 'Daily: On' : 'Daily: Off' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('leagues.updateDailyTime', $league->id) }}" style="display:inline;">
                                        @csrf
                                        <input type="time" name="daily_update_time" value="{{ $league->daily_update_time ?? '05:00' }}"
                                            class="text-xs border border-gray-300 rounded px-1 py-0.5 text-gray-700"
                                            onchange="this.form.submit()">
                                    </form>
                                </div>
                            </td>
                        @endenv
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
