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
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <a href="{{ route('leagues.edit', $league->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</a>
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
                        @env('local')
                            <td class="px-4 py-2 text-sm">
                                <a href="{{ route('leagues.edit', $league->id) }}" class="text-blue-600 hover:text-blue-800 mr-3">Edit</a>
                            </td>
                        @endenv
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
