@extends('layouts.app')

@section('title', 'Configurations List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Configurations List</h1>
    @include('partials.tabs')

    <div class="flex justify-end mb-4">
        <a href="{{ route('configurations.create') }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
            + Add Configuration
        </a>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">JWT</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($configurations as $config)
                    <tr ondblclick="window.location='{{ route('configurations.edit', $config->id) }}'" class="hover:bg-gray-50 cursor-pointer">
                        <td class="px-4 py-2 text-sm text-gray-700 truncate max-w-xs">{{ $config->jwt }}</td>
                        <td class="px-4 py-2 text-sm text-center">
                            <form action="{{ route('configurations.destroy', $config->id) }}" method="POST" onsubmit="return confirm('Are you sure?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-semibold">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
