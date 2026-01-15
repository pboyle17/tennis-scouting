@extends('layouts.app')

@section('title', 'Edit Racket')

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

    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Edit Racket</h1>

    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
        <form action="{{ route('rackets.update', $racket) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-3 border-b pb-2">Racket Information</h2>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="name">Name <span class="text-red-500">*</span></label>
                    <input class="w-full border border-gray-300 p-2 rounded" type="text" name="name" id="name" value="{{ old('name', $racket->name) }}" required>
                    @error('name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="brand">Brand <span class="text-red-500">*</span></label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="brand" id="brand" value="{{ old('brand', $racket->brand) }}" required>
                        @error('brand')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="model">Model <span class="text-red-500">*</span></label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="model" id="model" value="{{ old('model', $racket->model) }}" required>
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
                            <option value="{{ $player->id }}" {{ old('player_id', $racket->player_id) == $player->id ? 'selected' : '' }}>
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
                        <input class="w-full border border-gray-300 p-2 rounded" type="number" step="0.1" name="weight" id="weight" value="{{ old('weight', $racket->weight) }}">
                        @error('weight')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="swing_weight">Swing Weight</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="number" name="swing_weight" id="swing_weight" value="{{ old('swing_weight', $racket->swing_weight) }}">
                        @error('swing_weight')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="string_pattern">String Pattern</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="string_pattern" id="string_pattern" value="{{ old('string_pattern', $racket->string_pattern) }}" placeholder="e.g., 16x19">
                        @error('string_pattern')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2" for="grip_size">Grip Size</label>
                        <input class="w-full border border-gray-300 p-2 rounded" type="text" name="grip_size" id="grip_size" value="{{ old('grip_size', $racket->grip_size) }}" placeholder="e.g., 4 3/8">
                        @error('grip_size')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="notes">Notes</label>
                    <textarea class="w-full border border-gray-300 p-2 rounded" name="notes" id="notes" rows="3">{{ old('notes', $racket->notes) }}</textarea>
                    @error('notes')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex justify-between items-center">
                <div>
                    <a href="{{ route('rackets.show', $racket) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">Back</a>
                </div>
                <div class="flex space-x-2">
                    <button type="button" onclick="if(confirm('Are you sure you want to delete this racket? This will also delete all string job history.')) { document.getElementById('deleteForm').submit(); }" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded">
                        Delete
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                        Update Racket
                    </button>
                </div>
            </div>
        </form>

        <form id="deleteForm" action="{{ route('rackets.destroy', $racket) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>
@endsection
