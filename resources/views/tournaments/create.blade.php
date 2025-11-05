@extends('layouts.app')

@section('title', 'Create Tournament')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Create New Tournament</h1>

    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow">
        <form action="{{ route('tournaments.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="name">Tournament Name</label>
                <input class="w-full border border-gray-300 p-2 rounded @error('name') border-red-500 @enderror"
                       type="text" name="name" id="name" value="{{ old('name') }}" required>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4 grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="start_date">Start Date</label>
                    <input class="w-full border border-gray-300 p-2 rounded @error('start_date') border-red-500 @enderror"
                           type="date" name="start_date" id="start_date" value="{{ old('start_date') }}">
                    @error('start_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="end_date">End Date</label>
                    <input class="w-full border border-gray-300 p-2 rounded @error('end_date') border-red-500 @enderror"
                           type="date" name="end_date" id="end_date" value="{{ old('end_date') }}">
                    @error('end_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="location">Location</label>
                <input class="w-full border border-gray-300 p-2 rounded @error('location') border-red-500 @enderror"
                       type="text" name="location" id="location" value="{{ old('location') }}" placeholder="City, State or Venue">
                @error('location')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="usta_link">USTA Link</label>
                <input class="w-full border border-gray-300 p-2 rounded @error('usta_link') border-red-500 @enderror"
                       type="url" name="usta_link" id="usta_link" value="{{ old('usta_link') }}" placeholder="https://tennislink.usta.com/...">
                @error('usta_link')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2" for="description">Description</label>
                <textarea class="w-full border border-gray-300 p-2 rounded @error('description') border-red-500 @enderror"
                          name="description" id="description" rows="4" placeholder="Optional notes about the tournament">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-between">
                <a href="{{ route('tournaments.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">Back to list</a>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Create Tournament</button>
            </div>
        </form>
    </div>
</div>
@endsection
