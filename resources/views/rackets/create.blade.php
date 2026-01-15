@extends('layouts.app')

@section('title', 'Create Racket')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Create New Racket</h1>

    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
        <form action="{{ route('rackets.store') }}" method="POST">
            @csrf

            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-3 border-b pb-2">Racket Information</h2>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="name">Name <span class="text-red-500">*</span></label>
                    <input class="w-full border border-gray-300 p-2 rounded" type="text" name="name" id="name" value="{{ old('name') }}" required>
                    @error('name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="brand">Brand <span class="text-red-500">*</span></label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="brand" id="brand" value="{{ old('brand') }}" required>
                        @error('brand')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="model">Model <span class="text-red-500">*</span></label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="model" id="model" value="{{ old('model') }}" required>
                        @error('model')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="player_id">Owner</label>
                    <select class="w-full border border-gray-300 p-2 rounded" name="player_id" id="player_id">
                        <option value="">-- None --</option>
                        @foreach($players as $player)
                            <option value="{{ $player->id }}" {{ old('player_id') == $player->id ? 'selected' : '' }}>
                                {{ $player->first_name }} {{ $player->last_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('player_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-3 border-b pb-2">Specifications</h2>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="weight">Weight (g)</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="number" step="0.1" name="weight" id="weight" value="{{ old('weight') }}">
                        @error('weight')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="swing_weight">Swing Weight</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="number" name="swing_weight" id="swing_weight" value="{{ old('swing_weight') }}">
                        @error('swing_weight')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="string_pattern">String Pattern</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="string_pattern" id="string_pattern" value="{{ old('string_pattern') }}" placeholder="e.g., 16x19">
                        @error('string_pattern')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="grip_size">Grip Size</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="grip_size" id="grip_size" value="{{ old('grip_size') }}" placeholder="e.g., 4 3/8">
                        @error('grip_size')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="notes">Notes</label>
                    <textarea class="w-full border border-gray-300 p-2 rounded" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>
                    @error('notes')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <div class="flex items-center mb-3">
                    <input type="checkbox" name="add_string_job" id="add_string_job" class="mr-2" {{ old('add_string_job') ? 'checked' : '' }}>
                    <label class="text-gray-700 font-semibold cursor-pointer" for="add_string_job">Add Initial String Job</label>
                </div>

                <div id="stringJobFields" class="{{ old('add_string_job') ? '' : 'hidden' }}">
                    <h2 class="text-xl font-semibold text-gray-700 mb-3 border-b pb-2">String Job Details</h2>

                    <div id="hybridStringFields">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Mains (Vertical Strings)</h3>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2" for="mains_brand">Mains Brand</label>
                                <input class="w-full border border-gray-300 p-2 rounded" type="text" name="mains_brand" id="mains_brand" value="{{ old('mains_brand') }}">
                                @error('mains_brand')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-gray-700 font-semibold mb-2" for="mains_model">Mains Model</label>
                                <input class="w-full border border-gray-300 p-2 rounded" type="text" name="mains_model" id="mains_model" value="{{ old('mains_model') }}">
                                @error('mains_model')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2" for="mains_gauge">Mains Gauge</label>
                                <input class="w-full border border-gray-300 p-2 rounded" type="text" name="mains_gauge" id="mains_gauge" value="{{ old('mains_gauge') }}" placeholder="e.g., 16">
                                @error('mains_gauge')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-gray-700 font-semibold mb-2" for="mains_tension">Mains Tension (lbs)</label>
                                <input class="w-full border border-gray-300 p-2 rounded" type="number" step="0.1" name="mains_tension" id="mains_tension" value="{{ old('mains_tension') }}">
                                @error('mains_tension')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-700 mb-3 mt-4">Crosses (Horizontal Strings)</h3>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2" for="crosses_brand">Crosses Brand</label>
                                <input class="w-full border border-gray-300 p-2 rounded" type="text" name="crosses_brand" id="crosses_brand" value="{{ old('crosses_brand') }}">
                                @error('crosses_brand')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-gray-700 font-semibold mb-2" for="crosses_model">Crosses Model</label>
                                <input class="w-full border border-gray-300 p-2 rounded" type="text" name="crosses_model" id="crosses_model" value="{{ old('crosses_model') }}">
                                @error('crosses_model')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2" for="crosses_gauge">Crosses Gauge</label>
                                <input class="w-full border border-gray-300 p-2 rounded" type="text" name="crosses_gauge" id="crosses_gauge" value="{{ old('crosses_gauge') }}" placeholder="e.g., 17">
                                @error('crosses_gauge')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-gray-700 font-semibold mb-2" for="crosses_tension">Crosses Tension (lbs)</label>
                                <input class="w-full border border-gray-300 p-2 rounded" type="number" step="0.1" name="crosses_tension" id="crosses_tension" value="{{ old('crosses_tension') }}">
                                @error('crosses_tension')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2" for="stringing_date">Stringing Date</label>
                            <input class="w-full border border-gray-300 p-2 rounded" type="date" name="stringing_date" id="stringing_date" value="{{ old('stringing_date', date('Y-m-d')) }}">
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

                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2" for="string_notes">String Notes</label>
                        <textarea class="w-full border border-gray-300 p-2 rounded" name="string_notes" id="string_notes" rows="2">{{ old('string_notes') }}</textarea>
                        @error('string_notes')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-between">
                <a href="{{ route('rackets.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">Back to list</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">Create Racket</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('add_string_job').addEventListener('change', function() {
    const stringFields = document.getElementById('stringJobFields');
    stringFields.classList.toggle('hidden', !this.checked);
});
</script>
@endsection
