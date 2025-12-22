@php
    $current = request()->route()->getName();
@endphp

<div class="relative mb-6">
    <div class="flex justify-center space-x-4 border-b border-gray-200">
        <a href="{{ route('leagues.index') }}"
           class="px-4 py-2 {{ str_contains($current, 'leagues') ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-500 hover:text-blue-500' }}">
            Leagues
        </a>
        <a href="{{ route('players.index') }}"
           class="px-4 py-2 {{ str_contains($current, 'players') ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-500 hover:text-blue-500' }}">
            Players
        </a>
    </div>
    @env('local')
        <a href="{{ route('configurations.index') }}"
           class="absolute -top-12 right-0 text-2xl cursor-pointer grayscale hover:grayscale-0 transition-all"
           title="Configurations">
            ⚙️
        </a>
    @else
        <span class="absolute -top-12 right-0 text-2xl grayscale"
              title="Configurations">
            ⚙️
        </span>
    @endenv
</div>
