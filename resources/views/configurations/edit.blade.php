@extends('layouts.app')

@section('title', 'Edit Configuration')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Edit Configuration</h1>

    <div class="max-w-lg mx-auto bg-white rounded-lg shadow p-6">
        @if ($errors->any())
            <div class="mb-4 bg-red-100 text-red-700 p-4 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('configurations.update', $configuration->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="jwt" class="block mb-1 text-gray-700 font-semibold">JWT</label>
                <input type="text" id="jwt" name="jwt" value="{{ old('jwt', $configuration->jwt) }}" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div class="flex justify-between">
                <a href="{{ route('configurations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                    Back to List
                </a>

                <div>
                    <form action="{{ route('configurations.destroy', $configuration->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this configuration?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded mr-2">
                            Delete
                        </button>
                    </form>

                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                        Update
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
