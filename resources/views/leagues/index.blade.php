@extends('layouts.app')

@section('title', 'Leagues List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Leagues List</h1>
    @include('partials.tabs')

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @env('local')
        <div class="flex justify-end mb-4">
            <a href="{{ route('leagues.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                + Add League
            </a>
        </div>
    @endenv

    <div class="overflow-x-auto bg-white rounded-lg shadow">
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
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-700">
                            <a href="{{ route('leagues.show', $league->id) }}" class="text-blue-600 hover:underline">
                                {{ $league->name }}
                            </a>
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
