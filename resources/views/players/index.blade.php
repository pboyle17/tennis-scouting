@extends('layouts.app')

@section('title', 'Players List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Players List</h1>

    <div class="flex justify-end mb-4">
        <form method="POST" action="{{ route('players.updateUtr') }}">
            @csrf
            <button type="submit" class="mr-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                🔄 Update UTR Ratings
            </button>
        </form>
        <a href="{{ route('configurations.index') }}" class="mr-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
            ⚙️
        </a>
        <a href="{{ route('players.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
            + Add Player
        </a>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">First Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Last Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">UTR ID</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">UTR Singles Rating</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">UTR Doubles Rating</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">USTA Rating</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($players as $player)
                    <tr ondblclick="window.location='{{ route('players.edit', $player->id) }}'" class="hover:bg-gray-50 cursor-pointer">
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->first_name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->last_name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_id }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_singles_rating }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_doubles_rating }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $player->USTA_rating }}</td>
                        <td class="px-4 py-2 text-sm text-center">
                            <a href="https://app.utrsports.net/profiles/{{ $player->utr_id }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center">
                                <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR Profile" class="h-5 w-5">
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
