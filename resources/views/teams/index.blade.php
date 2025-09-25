@extends('layouts.app')

@section('title', 'Teams List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Teams List</h1>
    @include('partials.tabs')
    <div class="flex justify-between items-center mb-4">
        <div class="text-sm text-gray-600">
            💡 <strong>Single-click</strong> to view players | <strong>Double-click</strong> to edit team
        </div>
        <a href="{{ route('teams.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
            + Add Team
        </a>
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
@endsection
