@extends('layouts.app')

@section('title', 'Add Configuration')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Add Configuration</h1>

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

        <form action="{{ route('configurations.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="jwt" class="block mb-1 text-gray-700 font-semibold">JWT</label>
                <input type="text" id="jwt" name="jwt" value="{{ old('jwt') }}" class="w-full border rounded p-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div class="flex justify-end">
                <a href="{{ route('configurations.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded mr-2">
                    Cancel
                </a>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                    Save Configuration
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
