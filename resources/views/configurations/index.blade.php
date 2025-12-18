@extends('layouts.app')

@section('title', 'Configurations List')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Configurations List</h1>
    @include('partials.tabs')

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4 font-semibold">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4 font-semibold">
            ✖ {{ session('error') }}
        </div>
    @endif

    <!-- Backup List -->
    @if(session('backups'))
        <div class="mb-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-4">Available Database Backups</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Backup File</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Size</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach(session('backups') as $backup)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm text-gray-700 font-mono">{{ $backup['filename'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $backup['date'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $backup['size'] }}</td>
                                    <td class="px-4 py-2 text-sm text-center">
                                        <form method="POST" action="{{ route('configurations.restoreDatabase') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="filename" value="{{ $backup['path'] }}">
                                            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white text-xs px-3 py-1 rounded" onclick="return confirm('⚠️ WARNING: This will restore the database from this backup, REPLACING ALL current data. This cannot be undone.\n\nBackup: {{ $backup['filename'] }}\n\nContinue?');">
                                                🔄 Restore This
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="flex justify-end mb-4 space-x-2">
        @env('local')
            <form method="POST" action="{{ route('configurations.backupDatabase') }}" style="display:inline;">
                @csrf
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded cursor-pointer" onclick="return confirm('This will create a database backup and upload it to S3. Continue?');">
                    📦 Backup DB to S3
                </button>
            </form>
        @endenv
            <form method="POST" action="{{ route('configurations.listBackups') }}" style="display:inline;">
                @csrf
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
                    📋 View Backups
                </button>
            </form>
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
