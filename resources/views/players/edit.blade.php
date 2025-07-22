@extends('layouts.app')

@section('title', 'Edit Player')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center">Edit Player</h1>

    <form action="{{ route('players.update', $player->id) }}" method="POST" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block mb-1" for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $player->first_name) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $player->last_name) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="utr_id">UTR Id</label>
            <input type="number" name="utr_id" id="utr_id" value="{{ old('utr_id', $player->utr_id) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="utr_singles_rating">UTR Singles Rating</label>
            <input type="number" step=".01" name="utr_singles_rating" id="utr_singles_rating" value="{{ old('utr_singles_rating', $player->utr_singles_rating) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="utr_doubles_rating">UTR Doubles Rating</label>
            <input type="number" step=".01" name="utr_doubles_rating" id="utr_doubles_rating" value="{{ old('utr_doubles_rating', $player->utr_doubles_rating) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="USTA_rating">USTA Rating</label>
            <input type="number" step=".5" name="USTA_rating" id="USTA_rating" value="{{ old('USTA_rating', $player->USTA_rating) }}" class="w-full border rounded p-2">
        </div>

        <div class="flex justify-between">
          <a href="{{ route('players.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">Back to list</a>
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update Player</button>
        </div>
    </form>
</div>
@endsection
