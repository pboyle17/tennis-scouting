@php
    $current = request()->route()->getName();
@endphp

<div class="mb-6 border-b border-gray-200">
    <div class="flex justify-center space-x-4">
        <a href="{{ route('leagues.index') }}"
           class="px-4 py-2 text-sm font-medium {{ str_contains($current, 'leagues') ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-blue-500' }}">
            Leagues
        </a>
        <a href="{{ route('players.index') }}"
           class="px-4 py-2 text-sm font-medium {{ str_contains($current, 'players') ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-blue-500' }}">
            Players
        </a>
        <a href="{{ route('rackets.index') }}"
           class="px-4 py-2 text-sm font-medium {{ str_contains($current, 'rackets') || str_contains($current, 'string-jobs') ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-blue-500' }}">
            Rackets
        </a>
    </div>
</div>
