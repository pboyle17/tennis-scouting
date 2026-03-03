<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@env('local')🔧 LOCAL | @endenv @yield('title', 'Players')</title>
    @env('local')
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🟢</text></svg>">
    @else
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔭</text></svg>">
    @endenv
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    @php
        $currentRoute = request()->route()?->getName() ?? '';
    @endphp
    <nav class="bg-white shadow-sm fixed top-0 left-0 right-0 z-[9999]">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-14">
            <a href="{{ route('leagues.index') }}" class="font-bold text-gray-800 text-lg tracking-tight">🎾 CourtScout</a>

            {{-- Desktop nav --}}
            <div class="hidden md:flex items-center space-x-1">
                <a href="{{ route('leagues.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium {{ str_contains($currentRoute, 'leagues') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-500 hover:bg-gray-50' }}">
                    Leagues
                </a>
                <a href="{{ route('players.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium {{ str_contains($currentRoute, 'players') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-500 hover:bg-gray-50' }}">
                    Players
                </a>
                <a href="{{ route('rackets.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium {{ str_contains($currentRoute, 'rackets') || str_contains($currentRoute, 'string-jobs') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-500 hover:bg-gray-50' }}">
                    Rackets
                </a>
                @env('local')
                <a href="{{ route('configurations.index') }}"
                   class="px-3 py-2 rounded text-sm font-medium text-gray-600 hover:text-blue-500 hover:bg-gray-50"
                   title="Configurations">⚙️</a>
                @endenv
            </div>

            {{-- Mobile hamburger --}}
            <button id="hamburger-btn" class="md:hidden p-2 rounded text-gray-600 hover:bg-gray-100 focus:outline-none" aria-label="Open menu">
                <svg id="icon-open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg id="icon-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Mobile dropdown --}}
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-100 bg-white">
            <div class="px-4 py-2 space-y-1">
                <a href="{{ route('leagues.index') }}"
                   class="block px-3 py-2 rounded text-sm font-medium {{ str_contains($currentRoute, 'leagues') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' }}">
                    Leagues
                </a>
                <a href="{{ route('players.index') }}"
                   class="block px-3 py-2 rounded text-sm font-medium {{ str_contains($currentRoute, 'players') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' }}">
                    Players
                </a>
                <a href="{{ route('rackets.index') }}"
                   class="block px-3 py-2 rounded text-sm font-medium {{ str_contains($currentRoute, 'rackets') || str_contains($currentRoute, 'string-jobs') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' }}">
                    Rackets
                </a>
                @env('local')
                <a href="{{ route('configurations.index') }}"
                   class="block px-3 py-2 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">
                    ⚙️ Configurations
                </a>
                @endenv
            </div>
        </div>
    </nav>

    <div class="flex-1 pt-14">
        @yield('content')
    </div>

    <script>
        (function () {
            const btn = document.getElementById('hamburger-btn');
            const menu = document.getElementById('mobile-menu');
            const iconOpen = document.getElementById('icon-open');
            const iconClose = document.getElementById('icon-close');

            btn.addEventListener('click', function () {
                const isOpen = !menu.classList.contains('hidden');
                menu.classList.toggle('hidden', isOpen);
                iconOpen.classList.toggle('hidden', !isOpen);
                iconClose.classList.toggle('hidden', isOpen);
            });

            // Close menu when a link is tapped
            menu.querySelectorAll('a').forEach(function (a) {
                a.addEventListener('click', function () {
                    menu.classList.add('hidden');
                    iconOpen.classList.remove('hidden');
                    iconClose.classList.add('hidden');
                });
            });
        })();
    </script>
</body>
</html>
