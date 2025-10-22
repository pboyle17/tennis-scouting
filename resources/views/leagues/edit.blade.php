@extends('layouts.app')

@section('title', 'Edit League')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Edit League</h1>

    @if ($errors->any())
        <div class="max-w-lg mx-auto mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow">
        <form action="{{ route('leagues.update', $league->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="name">League Name</label>
                <input
                    class="w-full border border-gray-300 p-2 rounded"
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name', $league->name) }}"
                    required
                >
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="usta_link">USTA Link</label>
                <input
                    class="w-full border border-gray-300 p-2 rounded"
                    type="url"
                    name="usta_link"
                    id="usta_link"
                    value="{{ old('usta_link', $league->usta_link) }}"
                >
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2" for="tennis_record_link">Tennis Record Link</label>
                <input
                    class="w-full border border-gray-300 p-2 rounded"
                    type="url"
                    name="tennis_record_link"
                    id="tennis_record_link"
                    value="{{ old('tennis_record_link', $league->tennis_record_link) }}"
                >
            </div>

            <div class="flex justify-between">
                <a href="{{ route('leagues.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">Back to list</a>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update league</button>
            </div>
        </form>
    </div>
</div>
@endsection
