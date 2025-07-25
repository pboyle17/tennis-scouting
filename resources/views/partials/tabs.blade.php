@php
    $current = request()->route()->getName();
@endphp

<div class="flex justify-center mb-6 space-x-4 border-b border-gray-200">
    <a href="{{ route('players.index') }}"
       class="px-4 py-2 {{ str_contains($current, 'players') ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-500 hover:text-blue-500' }}">
        Players
    </a>
    <a href="{{ route('teams.index') }}"
       class="px-4 py-2 {{ str_contains($current, 'teams') ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-500 hover:text-blue-500' }}">
        Teams
    </a>
    <a href="{{ route('leagues.index') }}"
       class="px-4 py-2 {{ str_contains($current, 'leagues') ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-500 hover:text-blue-500' }}">
        Leagues
    </a>
    <a href="{{ route('configurations.index') }}"
       class="px-4 py-2 {{ str_contains($current, 'configurations') ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-500 hover:text-blue-500' }}">
        Configurations
    </a>
</div>
