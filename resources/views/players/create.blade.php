@extends('layouts.app')

@section('title', 'Create Player')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Create New Player</h1>

    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow">
        <form action="{{ route('players.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="first_name">First Name</label>
                <input class="w-full border border-gray-300 p-2 rounded" type="text" name="first_name" id="first_name" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="last_name">Last Name</label>
                <input class="w-full border border-gray-300 p-2 rounded" type="text" name="last_name" id="last_name" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="utr_id">UTR ID</label>
                <input class="w-full border border-gray-300 p-2 rounded" type="number" name="utr_id" id="utr_id">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="utr_rating">UTR Rating</label>
                <input class="w-full border border-gray-300 p-2 rounded" type="number" step="0.01" name="utr_rating" id="utr_rating">
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2" for="USTA_rating">USTA Rating</label>
                <input class="w-full border border-gray-300 p-2 rounded" type="number" step=".5" name="USTA_rating" id="USTA_rating">
            </div>

            <div class="flex justify-between">
                <a href="{{ route('players.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">Back to list</a>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Create Player</button>
            </div>
        </form>
    </div>
</div>
@endsection
