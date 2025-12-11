@extends('layouts.app')

@section('title', 'Edit Match')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Edit Match</h1>

    @if ($errors->any())
        <div class="max-w-2xl mx-auto mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
        <form action="{{ route('tennis-matches.update', $match->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="home_team_id">Home Team</label>
                <select
                    class="w-full border border-gray-300 p-2 rounded"
                    name="home_team_id"
                    id="home_team_id"
                    required
                >
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ old('home_team_id', $match->home_team_id) == $team->id ? 'selected' : '' }}>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="away_team_id">Away Team</label>
                <select
                    class="w-full border border-gray-300 p-2 rounded"
                    name="away_team_id"
                    id="away_team_id"
                    required
                >
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ old('away_team_id', $match->away_team_id) == $team->id ? 'selected' : '' }}>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="start_time">Match Date & Time</label>
                <input
                    class="w-full border border-gray-300 p-2 rounded"
                    type="datetime-local"
                    name="start_time"
                    id="start_time"
                    value="{{ old('start_time', $match->start_time ? $match->start_time->format('Y-m-d\TH:i') : '') }}"
                >
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="location">Location</label>
                <input
                    class="w-full border border-gray-300 p-2 rounded"
                    type="text"
                    name="location"
                    id="location"
                    value="{{ old('location', $match->location) }}"
                >
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="home_score">Home Score</label>
                    <input
                        class="w-full border border-gray-300 p-2 rounded"
                        type="number"
                        name="home_score"
                        id="home_score"
                        min="0"
                        value="{{ old('home_score', $match->home_score) }}"
                    >
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="away_score">Away Score</label>
                    <input
                        class="w-full border border-gray-300 p-2 rounded"
                        type="number"
                        name="away_score"
                        id="away_score"
                        min="0"
                        value="{{ old('away_score', $match->away_score) }}"
                    >
                </div>
            </div>

            <div class="flex justify-between">
                <a href="{{ route('teams.show', $match->homeTeam->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    Update Match
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
