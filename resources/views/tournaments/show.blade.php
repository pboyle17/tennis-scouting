@extends('layouts.app')

@section('title', 'Tournament Players - ' . $tournament->name)

@section('content')
<div class="container mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-center text-gray-800">{{ $tournament->name }}</h1>
        <div class="flex space-x-2">
            @if(app()->isLocal() && \App\Services\UstaTournamentService::parseTournamentId($tournament->usta_link))
                <form method="POST" action="{{ route('tournaments.importUstaPlayers', $tournament->id) }}">
                    @csrf
                    <button type="submit" class="bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
                        🎾 Import USTA Players
                    </button>
                </form>
            @endif
            @if(app()->isLocal() && $tournament->utrEventId())
                <form method="POST" action="{{ route('tournaments.syncFromUtr', $tournament->id) }}">
                    @csrf
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded cursor-pointer"
                            title="{{ $tournament->utr_last_synced_at ? 'Last synced ' . $tournament->utr_last_synced_at->diffForHumans() : 'Never synced' }}">
                        🔄 Sync from UTR
                    </button>
                </form>
            @endif
            <a href="{{ route('tournaments.edit', $tournament->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                Edit Tournament
            </a>
            <a href="{{ route('tournaments.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                ← Back to Tournaments
            </a>
        </div>
    </div>


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

    @if(app()->isLocal() && ($import = \Illuminate\Support\Facades\Cache::get(\App\Jobs\ImportUstaTournamentPlayersJob::statusKey($tournament->id))))
        <div class="bg-indigo-50 border-l-4 border-indigo-400 p-4 mb-6 text-sm text-gray-700">
            <span class="font-semibold">USTA import {{ $import['stage'] === 'completed' ? 'finished' : $import['stage'] . '…' }}</span>
            <span class="text-gray-500">({{ \Carbon\Carbon::parse($import['at'])->diffForHumans() }})</span>
            <div class="mt-1">
                {{ $import['registrations'] }} registered ·
                {{ $import['created'] }} new players · {{ $import['existing'] }} already in the app
                @if($import['ambiguous']) · {{ $import['ambiguous'] }} skipped (name shared by several players) @endif
            </div>
            <div>
                UTR: {{ $import['utr_matched'] }} matched · {{ $import['utr_unmatched'] }} not found or unclear · {{ $import['utr_already'] }} already linked
                @if($import['rate_limited']) · <span class="text-red-600 font-semibold">stopped early, UTR rate limited</span> @endif
            </div>
            @isset($import['wtn_matched'])
                <div>
                    WTN: {{ $import['wtn_matched'] }} matched · {{ $import['wtn_unmatched'] }} not found or unclear · {{ $import['wtn_refreshed'] }} refreshed
                </div>
            @endisset
        </div>
    @endif

    @include('partials.utr-search-results', [
        'saveUrl' => fn($playerId) => route('leagues.setPlayerUtrData', ['player' => $playerId]),
    ])

    <!-- Tournament Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if($tournament->start_date)
                <div>
                    <label class="text-sm font-semibold text-gray-600">Dates</label>
                    <p class="text-gray-800">
                        {{ $tournament->start_date->format('M d, Y') }}
                        @if($tournament->end_date && !$tournament->start_date->isSameDay($tournament->end_date))
                            - {{ $tournament->end_date->format('M d, Y') }}
                        @endif
                    </p>
                </div>
            @endif
            @if($tournament->location)
                <div>
                    <label class="text-sm font-semibold text-gray-600">Location</label>
                    <p class="text-gray-800">{{ $tournament->location }}</p>
                </div>
            @endif
            @if($tournament->usta_link)
                <div>
                    <label class="text-sm font-semibold text-gray-600">USTA Link</label>
                    <a href="{{ $tournament->usta_link }}" target="_blank" class="text-blue-600 hover:underline flex items-center">
                        <img src="{{ asset('images/usta_logo.png') }}" alt="USTA" class="h-8 w-12 mr-2">
                        View on USTA
                    </a>
                </div>
            @endif
            @if($tournament->utr_link)
                <div>
                    <label class="text-sm font-semibold text-gray-600">UTR Link</label>
                    <a href="{{ $tournament->utr_link }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline flex items-center">
                        <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR" class="h-8 w-8 mr-2">
                        View on UTR
                    </a>
                </div>
            @endif
        </div>
        @if($tournament->description)
            <div class="mt-4">
                <label class="text-sm font-semibold text-gray-600">Description</label>
                <p class="text-gray-800">{{ $tournament->description }}</p>
            </div>
        @endif
    </div>

    <!-- Draws & Results -->
    @if($tournament->draws->isNotEmpty())
        @php
            $sideNames = fn($players) => $players->map(fn($mp) => $mp->player)->filter();
        @endphp
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Draws &amp; Results</h2>
                @if($tournament->utr_last_synced_at)
                    <span class="text-sm text-gray-500">Synced from UTR {{ $tournament->utr_last_synced_at->diffForHumans() }}</span>
                @endif
            </div>
            <div class="space-y-3">
                @foreach($tournament->draws as $draw)
                    @php
                        $matches = $draw->matches->sortBy([fn($a, $b) => $a->roundRank() <=> $b->roundRank(), ['played_on', 'asc']]);
                        $final = $draw->matches->firstWhere('round_code', 'F');
                        $champions = $final ? $sideNames($final->matchPlayers->where('won', true)->sortBy('position')) : collect();
                    @endphp
                    <details class="border border-gray-200 rounded-lg group">
                        <summary class="cursor-pointer px-4 py-3 flex flex-wrap items-center justify-between gap-2 hover:bg-gray-50 rounded-lg">
                            <span class="font-semibold text-gray-800">
                                <span class="inline-block transition-transform group-open:rotate-90 mr-1">▶</span>
                                {{ $draw->name }}
                            </span>
                            <span class="text-sm text-gray-500">
                                @if($champions->isNotEmpty())
                                    🏆 {{ $champions->map(fn($p) => $p->first_name . ' ' . $p->last_name)->implode(' / ') }} ·
                                @endif
                                {{ $draw->matches->count() }} {{ Str::plural('match', $draw->matches->count()) }}
                                @if($draw->player_count) · {{ $draw->player_count }} players @endif
                            </span>
                        </summary>
                        @if($matches->isEmpty())
                            <p class="px-4 pb-4 text-sm text-gray-500">No results yet.</p>
                        @else
                            <div class="overflow-x-auto px-4 pb-4">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-xs text-gray-500 uppercase text-left">
                                            <th class="py-2 pr-4">Round</th>
                                            <th class="py-2 pr-4">Winner</th>
                                            <th class="py-2 pr-4">Score</th>
                                            <th class="py-2 pr-4">Loser</th>
                                            <th class="py-2">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($matches as $match)
                                            @php $ratingField = $draw->team_type === 'doubles' ? 'utr_doubles_rating' : 'utr_singles_rating'; @endphp
                                            <tr>
                                                <td class="py-2 pr-4 text-gray-600 whitespace-nowrap">{{ $match->round_name ?? 'Round robin' }}</td>
                                                @foreach([true, false] as $won)
                                                    @if(!$won)
                                                        <td class="py-2 pr-4 whitespace-nowrap font-semibold">
                                                            @if($match->isWalkover())
                                                                <span class="text-orange-500 italic font-normal">Walkover</span>
                                                            @else
                                                                {{ $match->scoreText() }}
                                                            @endif
                                                        </td>
                                                    @endif
                                                    <td class="py-2 pr-4">
                                                        @foreach($match->matchPlayers->where('won', $won)->sortBy('position') as $mp)
                                                            @if(!$loop->first) <span class="text-gray-400">/</span> @endif
                                                            <a href="{{ route('players.show', $mp->player_id) }}" class="hover:underline {{ $won ? 'text-green-600 font-semibold' : 'text-gray-700' }}">{{ $mp->player->first_name }} {{ $mp->player->last_name }}</a>
                                                            @if($mp->$ratingField)<span class="text-xs text-gray-400">({{ number_format($mp->$ratingField, 2) }})</span>@endif
                                                        @endforeach
                                                    </td>
                                                @endforeach
                                                <td class="py-2 text-gray-500 whitespace-nowrap">{{ $match->played_on?->format('M j') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </details>
                @endforeach
            </div>
        </div>
    @elseif(app()->isLocal() && $tournament->utrEventId())
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 text-sm text-gray-700">
            No draws yet. Click <strong>Sync from UTR</strong> to pull this event's draws and results.
        </div>
    @endif

    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            <strong>{{ $tournament->players->count() }}</strong> players in this tournament
        </div>

        @php $playersWithoutUtrId = $tournament->players->whereNull('utr_id')->count(); @endphp
        <div class="flex items-center gap-2">
        @if(app()->isLocal() && $playersWithoutUtrId > 0)
            <form method="POST" action="{{ route('tournaments.findMissingUtrIds', $tournament->id) }}">
                @csrf
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded cursor-pointer">
                    🔍 Find Missing UTR IDs ({{ $playersWithoutUtrId }})
                </button>
            </form>
        @endif
        @if($availablePlayers->count() > 0)
            <button type="button" id="toggleAddPlayerBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                + Add Players
            </button>
        @endif
        </div>
    </div>

    <!-- Add Player Section -->
    @if($availablePlayers->count() > 0)
        <div id="addPlayerSection" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 hidden">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-800">Add Players to Tournament</h3>
                <button type="button" id="closeAddPlayerBtn" class="text-gray-500 hover:text-gray-700 text-xl font-bold">
                    &times;
                </button>
            </div>
            <form method="POST" action="{{ route('tournaments.addPlayer', $tournament->id) }}">
                @csrf
                <div class="flex-1">
                    <div class="flex justify-between items-center mb-2">
                        <label for="playerSearch" class="block text-sm font-medium text-gray-700">Search Players</label>
                        <div class="text-xs text-gray-500 space-x-2">
                            <button type="button" id="selectAllBtn" class="text-blue-600 hover:text-blue-800">Select All Visible</button>
                            <span>|</span>
                            <button type="button" id="clearAllBtn" class="text-blue-600 hover:text-blue-800">Clear All</button>
                        </div>
                    </div>
                    <input type="text" id="playerSearch" placeholder="Type to search players..."
                           class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mb-3">

                    <div id="playerList" class="border rounded px-3 py-2 h-48 overflow-y-auto bg-white">
                        @foreach($availablePlayers as $player)
                            <label class="flex items-center py-2 hover:bg-gray-50 cursor-pointer player-option"
                                   data-name="{{ strtolower($player->first_name . ' ' . $player->last_name) }}">
                                <input type="checkbox" name="player_ids[]" value="{{ $player->id }}"
                                       class="mr-3 text-blue-600 rounded focus:ring-blue-500">
                                <span class="flex-1">
                                    {{ $player->first_name }} {{ $player->last_name }}
                                    @if($player->utr_singles_rating)
                                        <span class="text-sm text-gray-500">(UTR: {{ $player->utr_singles_rating }})</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-2 text-xs text-gray-600">
                        <span id="selectedCount">0</span> player(s) selected
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" id="addPlayersBtn" disabled
                            class="bg-green-500 hover:bg-green-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-2 px-4 rounded">
                        + Add Selected
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if($tournament->players->count() > 0)
        <div class="mb-4 flex items-center space-x-2">
            <input
                id="playerTableSearch"
                type="text"
                placeholder="Search by name…"
                class="border rounded px-3 py-2 w-64"
            />
            <button
                id="clearPlayerTableSearch"
                type="button"
                class="hidden bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-3 rounded"
            >
                ✖ Clear
            </button>
        </div>

        @php
            $flightNames = $tournament->players->flatMap(fn($p) => $p->pivot->flights ?? [])->unique()->sort(SORT_NATURAL)->values();
            $flightsAttr = fn($p) => e(implode('|', $p->pivot->flights ?? []));
        @endphp
        @if($flightNames->count() > 1)
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Flight:</span>
                @foreach($flightNames as $flightName)
                    @php $flightCount = $tournament->players->filter(fn($p) => in_array($flightName, $p->pivot->flights ?? []))->count(); @endphp
                    <button type="button" data-flight="{{ $flightName }}" class="flight-filter-btn px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                        {{ $flightName }} <span class="text-xs opacity-70">({{ $flightCount }})</span>
                    </button>
                @endforeach
                <button type="button" id="clearFlightFilter" class="hidden text-sm text-blue-600 hover:underline">Show all</button>
            </div>
        @endif

        <!-- Mobile Sort Controls -->
        <div class="md:hidden mb-4 flex items-center gap-2 text-sm">
            <span class="text-gray-600 font-medium">Sort:</span>
            <select id="mobileSortField" class="border rounded px-2 py-1 text-sm">
                <option value="last_name" {{ $sortField === 'last_name' ? 'selected' : '' }}>Name</option>
                <option value="utr_singles_rating" {{ $sortField === 'utr_singles_rating' ? 'selected' : '' }}>UTR Singles</option>
                <option value="utr_doubles_rating" {{ $sortField === 'utr_doubles_rating' ? 'selected' : '' }}>UTR Doubles</option>
                <option value="tennis_number_singles_rating" {{ $sortField === 'tennis_number_singles_rating' ? 'selected' : '' }}>WTN Singles</option>
            </select>
            <button id="mobileSortDirection" class="border rounded px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200" data-direction="{{ $sortDirection }}">
                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
            </button>
        </div>

        <!-- Mobile Cards -->
        <div id="playerCards" class="md:hidden space-y-3">
            @foreach ($sortDirection === 'asc' ? $tournament->players->sortBy($sortField) : $tournament->players->sortByDesc($sortField) as $player)
                <div class="bg-white rounded-lg shadow p-4" data-name="{{ strtolower($player->first_name . ' ' . $player->last_name) }}" data-flights="{!! $flightsAttr($player) !!}">
                    @if(!empty($player->pivot->flights))
                        <div class="text-xs text-gray-500 mb-1">{{ implode(' · ', $player->pivot->flights) }}</div>
                    @endif
                    <div class="flex justify-between items-start mb-2">
                        <a href="{{ route('players.show', $player->id) }}" class="text-base font-semibold text-blue-600 hover:underline">
                            {{ $player->first_name }} {{ $player->last_name }}
                        </a>
                        <button onclick="openPlayerLinksModal('{{ $player->id }}', '{{ addslashes($player->first_name . ' ' . $player->last_name) }}', '{{ $player->utr_id }}', '{{ $player->tennis_record_link }}', '{{ $player->tennis_number_link }}')" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded">
                            🔗 Links
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500 text-xs">UTR Singles</span>
                            <div class="font-medium text-gray-700">{{ $player->utr_singles_rating ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500 text-xs">UTR Doubles</span>
                            <div class="font-medium text-gray-700">{{ $player->utr_doubles_rating ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500 text-xs">WTN Singles</span>
                            <div class="font-medium text-gray-700">{{ $player->tennis_number_singles_rating ? number_format($player->tennis_number_singles_rating, 2) : '—' }}</div>
                        </div>
                    </div>
                    @env('local')
                        <div class="mt-2 pt-2 border-t border-gray-100 flex justify-between items-center">
                            <a href="{{ route('players.edit', $player->id) }}?return_url={{ urlencode(route('tournaments.show', $tournament->id)) }}" class="text-blue-600 hover:text-blue-800 text-xs">Edit</a>
                            <form method="POST" action="{{ route('tournaments.removePlayer', [$tournament->id, $player->id]) }}" style="display:inline;" onsubmit="return confirm('Remove {{ $player->first_name }} {{ $player->last_name }} from this tournament?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Remove</button>
                            </form>
                        </div>
                    @endenv
                </div>
            @endforeach
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto bg-white rounded-lg shadow">
            <table id="playersTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            Rank
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('tournaments.show', ['tournament' => $tournament->id, 'sort' => 'last_name', 'direction' => ($sortField === 'last_name' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                Name
                                @if(in_array($sortField, ['first_name', 'last_name']))
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('tournaments.show', ['tournament' => $tournament->id, 'sort' => 'utr_singles_rating', 'direction' => ($sortField === 'utr_singles_rating' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                UTR Singles
                                @if($sortField === 'utr_singles_rating')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('tournaments.show', ['tournament' => $tournament->id, 'sort' => 'utr_doubles_rating', 'direction' => ($sortField === 'utr_doubles_rating' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                UTR Doubles
                                @if($sortField === 'utr_doubles_rating')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">
                            <a href="{{ route('tournaments.show', ['tournament' => $tournament->id, 'sort' => 'tennis_number_singles_rating', 'direction' => ($sortField === 'tennis_number_singles_rating' && $sortDirection === 'desc') ? 'asc' : 'desc']) }}" class="hover:text-gray-900">
                                WTN Singles
                                @if($sortField === 'tennis_number_singles_rating')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        @if($flightNames->isNotEmpty())
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Flight</th>
                        @endif
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Links</th>
                        @env('local')<th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>@endenv
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($sortDirection === 'asc' ? $tournament->players->sortBy($sortField) : $tournament->players->sortByDesc($sortField) as $player)
                        <tr ondblclick="window.location='{{ route('players.edit', $player->id) }}?return_url={{ urlencode(route('tournaments.show', $tournament->id)) }}'" class="hover:bg-gray-50 cursor-pointer" data-name="{{ strtolower($player->first_name . ' ' . $player->last_name) }}" data-flights="{!! $flightsAttr($player) !!}">
                            <td class="px-4 py-2 text-sm text-gray-700" data-rank>{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 text-sm">
                                <a href="{{ route('players.show', $player->id) }}" onclick="event.stopPropagation()" class="text-blue-600 hover:underline">{{ $player->first_name }} {{ $player->last_name }}</a>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_singles_rating }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $player->utr_doubles_rating }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $player->tennis_number_singles_rating ? number_format($player->tennis_number_singles_rating, 2) : '' }}</td>
                            @if($flightNames->isNotEmpty())
                                <td class="px-4 py-2 text-sm text-gray-600">
                                    @foreach($player->pivot->flights ?? [] as $flightName)
                                        <div class="whitespace-nowrap">{{ $flightName }}</div>
                                    @endforeach
                                </td>
                            @endif
                            <td class="px-4 py-2 text-sm text-center">
                                <div class="flex items-center gap-2 justify-center">
                                    @if($player->utr_id)
                                        <a href="https://app.utrsports.net/profiles/{{ $player->utr_id }}" target="_blank" rel="noopener noreferrer">
                                            <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR Profile" class="h-5 w-5">
                                        </a>
                                    @endif
                                    @if($player->tennis_record_link)
                                        <a href="{{ $player->tennis_record_link }}" target="_blank" rel="noopener noreferrer" class="text-base leading-none">🎾</a>
                                    @endif
                                    @if($player->ustaProfileUrl())
                                        <a href="{{ $player->ustaProfileUrl() }}" target="_blank" rel="noopener noreferrer" title="USTA profile">
                                            <img src="{{ asset('images/usta_logo.png') }}" alt="USTA Profile" class="h-5 w-8 object-contain">
                                        </a>
                                    @endif
                                    @if($player->tennis_number_link)
                                        <a href="{{ $player->tennis_number_link }}" target="_blank" rel="noopener noreferrer">
                                            <img src="{{ asset('images/wtn_logo.png') }}" alt="WTN Profile" class="h-5 w-5">
                                        </a>
                                    @endif
                                </div>
                            </td>
                            @env('local')
                            <td class="px-4 py-2 text-sm text-center">
                                <a href="{{ route('players.edit', $player->id) }}?return_url={{ urlencode(route('tournaments.show', $tournament->id)) }}" onclick="event.stopPropagation()" class="text-blue-600 hover:text-blue-800 text-xs mr-2">Edit</a>
                                <form method="POST" action="{{ route('tournaments.removePlayer', [$tournament->id, $player->id]) }}" style="display:inline;"
                                      onsubmit="return confirm('Remove {{ $player->first_name }} {{ $player->last_name }} from this tournament?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs" onclick="event.stopPropagation()">
                                        Remove
                                    </button>
                                </form>
                            </td>
                            @endenv
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
            <div class="text-gray-500 text-lg mb-2">No players in this tournament yet</div>
            <p class="text-gray-400 text-sm">Click "Add Players" above to get started</p>
        </div>
    @endif
</div>

<!-- Player Links Modal -->
<div id="playerLinksModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 id="playerLinksModalTitle" class="text-lg font-medium text-gray-900">Player Links</h3>
            <button onclick="closePlayerLinksModal()" class="text-gray-400 hover:text-gray-600">
                <span class="sr-only">Close</span>
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div id="playerLinksContent" class="space-y-3">
            <!-- Links will be inserted here -->
        </div>
        <div class="mt-4 flex justify-end">
            <button onclick="closePlayerLinksModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function openPlayerLinksModal(playerId, playerName, utrId, tennisRecordLink, tennisNumberLink) {
        const modal = document.getElementById('playerLinksModal');
        const title = document.getElementById('playerLinksModalTitle');
        const content = document.getElementById('playerLinksContent');

        title.textContent = playerName + ' - Links';

        let linksHtml = '';

        linksHtml += `
            <a href="{{ url('/players') }}/${playerId}" class="flex items-center space-x-3 p-3 bg-blue-50 hover:bg-blue-100 rounded border border-blue-200 transition">
                <span class="text-3xl">👤</span>
                <div>
                    <div class="font-semibold text-gray-800">Player Profile</div>
                    <div class="text-xs text-gray-500">View full profile</div>
                </div>
            </a>
        `;

        if (utrId) {
            linksHtml += `
                <a href="https://app.utrsports.net/profiles/${utrId}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-3 p-3 bg-gray-50 hover:bg-gray-100 rounded border border-gray-200 transition">
                    <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR Profile" class="h-8 w-8">
                    <div>
                        <div class="font-semibold text-gray-800">UTR Profile</div>
                        <div class="text-xs text-gray-500">View on UTR Sports</div>
                    </div>
                </a>
            `;
        }

        if (tennisRecordLink) {
            linksHtml += `
                <a href="${tennisRecordLink}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-3 p-3 bg-gray-50 hover:bg-gray-100 rounded border border-gray-200 transition">
                    <span class="text-3xl">🎾</span>
                    <div>
                        <div class="font-semibold text-gray-800">Tennis Record</div>
                        <div class="text-xs text-gray-500">View match history</div>
                    </div>
                </a>
            `;
        }

        if (tennisNumberLink) {
            linksHtml += `
                <a href="${tennisNumberLink}" target="_blank" rel="noopener noreferrer" class="flex items-center space-x-3 p-3 bg-gray-50 hover:bg-gray-100 rounded border border-gray-200 transition">
                    <img src="{{ asset('images/wtn_logo.png') }}" alt="WTN Profile" class="h-8 w-8">
                    <div>
                        <div class="font-semibold text-gray-800">World Tennis Number</div>
                        <div class="text-xs text-gray-500">View WTN profile</div>
                    </div>
                </a>
            `;
        }

        content.innerHTML = linksHtml;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePlayerLinksModal() {
        const modal = document.getElementById('playerLinksModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('playerLinksModal').addEventListener('click', function(e) {
        if (e.target === this) closePlayerLinksModal();
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Add Player Section
        const toggleAddPlayerBtn = document.getElementById('toggleAddPlayerBtn');
        const closeAddPlayerBtn = document.getElementById('closeAddPlayerBtn');
        const addPlayerSection = document.getElementById('addPlayerSection');

        if (toggleAddPlayerBtn) {
            toggleAddPlayerBtn.addEventListener('click', function() {
                addPlayerSection.classList.remove('hidden');
                toggleAddPlayerBtn.classList.add('hidden');
            });
        }

        if (closeAddPlayerBtn) {
            closeAddPlayerBtn.addEventListener('click', function() {
                addPlayerSection.classList.add('hidden');
                toggleAddPlayerBtn.classList.remove('hidden');
            });
        }

        // Add player search and selection logic
        const searchInput = document.getElementById('playerSearch');
        const playerOptions = document.querySelectorAll('.player-option');
        const checkboxes = document.querySelectorAll('input[name="player_ids[]"]');
        const addPlayersBtn = document.getElementById('addPlayersBtn');
        const selectedCount = document.getElementById('selectedCount');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const clearAllBtn = document.getElementById('clearAllBtn');

        if (searchInput && playerOptions.length > 0) {
            function updateUI() {
                const checkedBoxes = document.querySelectorAll('input[name="player_ids[]"]:checked');
                selectedCount.textContent = checkedBoxes.length;
                addPlayersBtn.disabled = checkedBoxes.length === 0;
            }

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                playerOptions.forEach(option => {
                    const playerName = option.dataset.name || '';
                    if (playerName.includes(searchTerm)) {
                        option.style.display = 'flex';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateUI);
            });

            selectAllBtn.addEventListener('click', function() {
                const visibleOptions = Array.from(playerOptions).filter(option =>
                    option.style.display !== 'none'
                );
                visibleOptions.forEach(option => {
                    const checkbox = option.querySelector('input[type="checkbox"]');
                    checkbox.checked = true;
                });
                updateUI();
            });

            clearAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateUI();
            });

            updateUI();
        }

        // Mobile Sort Controls
        (function() {
            const mobileSortField = document.getElementById('mobileSortField');
            const mobileSortDirection = document.getElementById('mobileSortDirection');

            if (mobileSortField && mobileSortDirection) {
                function updateSort() {
                    const url = new URL(window.location);
                    url.searchParams.set('sort', mobileSortField.value);
                    url.searchParams.set('direction', mobileSortDirection.dataset.direction);
                    window.location.href = url.toString();
                }

                mobileSortField.addEventListener('change', updateSort);

                mobileSortDirection.addEventListener('click', function() {
                    const newDirection = this.dataset.direction === 'asc' ? 'desc' : 'asc';
                    this.dataset.direction = newDirection;
                    this.textContent = newDirection === 'asc' ? '↑' : '↓';
                    updateSort();
                });
            }
        })();

        // Player table search + flight filter
        (function () {
            const input = document.getElementById('playerTableSearch');
            const clearBtn = document.getElementById('clearPlayerTableSearch');
            const clearFlights = document.getElementById('clearFlightFilter');
            const playersTable = document.getElementById('playersTable');
            const playerCards = document.getElementById('playerCards');

            if (!input || !clearBtn) return;

            const rows = playersTable ? Array.from(playersTable.querySelectorAll('tbody tr[data-name]')) : [];
            const cards = playerCards ? Array.from(playerCards.querySelectorAll('[data-name]')) : [];
            const flightButtons = Array.from(document.querySelectorAll('.flight-filter-btn'));
            const activeFlights = new Set();
            let t;

            function matches(el, q) {
                const name = el.getAttribute('data-name') || '';
                const flights = (el.getAttribute('data-flights') || '').split('|').filter(Boolean);
                const nameOk = !q || name.includes(q);
                const flightOk = activeFlights.size === 0 || flights.some(f => activeFlights.has(f));
                return nameOk && flightOk;
            }

            function applyFilter() {
                const q = input.value.trim().toLowerCase();
                let rank = 0;
                rows.forEach(row => {
                    const show = matches(row, q);
                    row.style.display = show ? '' : 'none';
                    const rankCell = row.querySelector('[data-rank]');
                    if (show && rankCell) rankCell.textContent = ++rank;
                });
                cards.forEach(card => {
                    card.style.display = matches(card, q) ? '' : 'none';
                });
                clearBtn.classList.toggle('hidden', q.length === 0);
                if (clearFlights) clearFlights.classList.toggle('hidden', activeFlights.size === 0);
            }

            function setActive(btn, on) {
                btn.classList.toggle('bg-blue-600', on);
                btn.classList.toggle('text-white', on);
                btn.classList.toggle('hover:bg-blue-700', on);
                btn.classList.toggle('bg-gray-100', !on);
                btn.classList.toggle('text-gray-700', !on);
                btn.classList.toggle('hover:bg-gray-200', !on);
            }

            // Keep the selected flights in the URL (?flight=...) so they survive re-sorting
            function withFlights(href) {
                const url = new URL(href, window.location.href);
                url.searchParams.delete('flight');
                activeFlights.forEach(f => url.searchParams.append('flight', f));
                return url.toString();
            }

            function syncUrl() {
                history.replaceState(null, '', withFlights(window.location.href));
            }

            flightButtons.forEach(btn => btn.addEventListener('click', () => {
                const flight = btn.dataset.flight;
                const on = !activeFlights.has(flight);
                on ? activeFlights.add(flight) : activeFlights.delete(flight);
                setActive(btn, on);
                applyFilter();
                syncUrl();
            }));

            if (clearFlights) {
                clearFlights.addEventListener('click', () => {
                    activeFlights.clear();
                    flightButtons.forEach(btn => setActive(btn, false));
                    applyFilter();
                    syncUrl();
                });
            }

            // Column sort links reload the page, so carry the current flights along
            document.querySelectorAll('#playersTable thead a[href*="sort="]').forEach(link => {
                link.addEventListener('click', () => { link.href = withFlights(link.href); });
            });

            // Restore flights from the URL on load
            const initialFlights = new URLSearchParams(window.location.search).getAll('flight');
            flightButtons.forEach(btn => {
                if (initialFlights.includes(btn.dataset.flight)) {
                    activeFlights.add(btn.dataset.flight);
                    setActive(btn, true);
                }
            });
            if (activeFlights.size) applyFilter();

            input.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(applyFilter, 150);
            });
            clearBtn.addEventListener('click', () => {
                input.value = '';
                applyFilter();
                input.focus();
            });
        })();
    });
</script>
@endsection
