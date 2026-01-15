@extends('layouts.app')

@section('title', 'Add String Job')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Add String Job for {{ $racket->name }}</h1>

    @if($racket->currentStringJob)
        <div class="max-w-lg mx-auto mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-sm text-yellow-800">
                <strong>Note:</strong> Adding a new string job will mark the current strings as past. The previous string job will be preserved in the history.
            </p>
            <div class="mt-2 text-sm text-gray-700">
                <strong>Current strings:</strong> {{ $racket->currentStringJob->string_brand }}
                @if($racket->currentStringJob->string_model)
                    {{ $racket->currentStringJob->string_model }}
                @endif
                @ {{ $racket->currentStringJob->tension }}lbs
            </div>
        </div>
    @endif

    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow">
        <form action="{{ route('rackets.string-jobs.store', $racket) }}" method="POST">
            @csrf

            <div id="hybridStringFields">
                <h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Mains (Vertical Strings)</h3>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="mains_brand">Mains Brand <span class="text-red-500">*</span></label>
                    <input class="w-full border border-gray-300 p-2 rounded" type="text" name="mains_brand" id="mains_brand" value="{{ old('mains_brand') }}">
                    @error('mains_brand')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="mains_model">Mains Model</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="mains_model" id="mains_model" value="{{ old('mains_model') }}">
                        @error('mains_model')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="mains_gauge">Mains Gauge</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="mains_gauge" id="mains_gauge" value="{{ old('mains_gauge') }}" placeholder="e.g., 16">
                        @error('mains_gauge')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="mains_tension">Mains Tension (lbs) <span class="text-red-500">*</span></label>
                    <input class="w-full border border-gray-300 p-2 rounded" type="number" step="0.1" name="mains_tension" id="mains_tension" value="{{ old('mains_tension') }}">
                    @error('mains_tension')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <h3 class="text-lg font-semibold text-gray-700 mb-3 mt-6 border-b pb-2">Crosses (Horizontal Strings)</h3>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="crosses_brand">Crosses Brand <span class="text-red-500">*</span></label>
                    <input class="w-full border border-gray-300 p-2 rounded" type="text" name="crosses_brand" id="crosses_brand" value="{{ old('crosses_brand') }}">
                    @error('crosses_brand')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="crosses_model">Crosses Model</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="crosses_model" id="crosses_model" value="{{ old('crosses_model') }}">
                        @error('crosses_model')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="crosses_gauge">Crosses Gauge</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="crosses_gauge" id="crosses_gauge" value="{{ old('crosses_gauge') }}" placeholder="e.g., 17">
                        @error('crosses_gauge')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="crosses_tension">Crosses Tension (lbs) <span class="text-red-500">*</span></label>
                    <input class="w-full border border-gray-300 p-2 rounded" type="number" step="0.1" name="crosses_tension" id="crosses_tension" value="{{ old('crosses_tension') }}">
                    @error('crosses_tension')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="stringing_date">Stringing Date <span class="text-red-500">*</span></label>
                    <input class="w-full border border-gray-300 p-2 rounded" type="date" name="stringing_date" id="stringing_date" value="{{ old('stringing_date', date('Y-m-d')) }}" required>
                    @error('stringing_date')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="time_played">Time Played (hours)</label>
                    <input class="w-full border border-gray-300 p-2 rounded" type="number" step="0.1" name="time_played" id="time_played" value="{{ old('time_played', '0') }}">
                    @error('time_played')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2" for="notes">Notes</label>
                <textarea class="w-full border border-gray-300 p-2 rounded" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>
                @error('notes')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-between">
                <a href="{{ route('rackets.show', $racket) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">Back</a>
                <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded">Add String Job</button>
            </div>
        </form>
    </div>
</div>
@endsection
