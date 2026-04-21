@extends('layouts.app')

@section('title', $league->name . ' — ' . ucfirst($type) . ' ' . $number . ' Results')

@section('content')
<div class="container mx-auto px-4 py-6 md:p-6">
    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-3">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ ucfirst($type) }} {{ $number }} Results</h1>
                <a href="{{ route('leagues.show', $league->id) }}" class="text-blue-600 hover:underline text-sm">{{ $league->name }}</a>
            </div>
            <a href="{{ route('leagues.show', $league->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded self-start">
                ← Back to League
            </a>
        </div>

        <!-- Team Filters -->
        @if($teams->count() > 1)
            <div class="mb-6 flex flex-wrap gap-2">
                <a href="{{ route('leagues.courtResults', [$league->id, $type, $number]) }}"
                   class="inline-block text-sm px-3 py-1 rounded-full transition {{ count($selectedTeamIds) === 0 ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    All Teams
                </a>
                @foreach($teams as $team)
                    @php
                        $isSelected = in_array($team->id, $selectedTeamIds);
                        $toggled = $isSelected
                            ? array_values(array_filter($selectedTeamIds, fn($id) => $id !== $team->id))
                            : array_merge($selectedTeamIds, [$team->id]);
                        $pillUrl = count($toggled)
                            ? route('leagues.courtResults', [$league->id, $type, $number]) . '?' . http_build_query(['teams' => $toggled])
                            : route('leagues.courtResults', [$league->id, $type, $number]);
                    @endphp
                    <a href="{{ $pillUrl }}"
                       class="inline-block text-sm px-3 py-1 rounded-full transition {{ $isSelected ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $team->name }}
                    </a>
                @endforeach
            </div>
        @endif

        @if($courts->isEmpty())
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                No results available for {{ ucfirst($type) }} {{ $number }} in this league yet.
            </div>
        @else
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">{{ ucfirst($type) }} {{ $number }}</h2>
                    <span class="text-sm text-gray-500">{{ $courts->count() }} {{ Str::plural('match', $courts->count()) }}</span>
                </div>

                <!-- Desktop -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Home Players</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Score</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Away Players</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Match</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($courts as $court)
                                @php
                                    $match = $court->tennisMatch;
                                    $homePlayers = $court->courtPlayers->where('team_id', $match->home_team_id);
                                    $awayPlayers = $court->courtPlayers->where('team_id', $match->away_team_id);
                                    $homeWon = $court->home_score > $court->away_score;
                                    $awayWon = $court->away_score > $court->home_score;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $match->start_time ? \Carbon\Carbon::parse($match->start_time)->format('M j, Y') : '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @foreach($homePlayers as $cp)
                                            @php $utr = $type === 'singles' ? $cp->utr_singles_rating : $cp->utr_doubles_rating; @endphp
                                            <div>
                                                <a href="{{ route('players.show', $cp->player->id) }}?team={{ $cp->team_id }}&court={{ $type }}&line={{ $number }}#match-history" class="hover:underline {{ $homeWon ? 'text-green-600 font-semibold' : 'text-blue-600' }}">
                                                    {{ $cp->player->first_name }} {{ $cp->player->last_name }}
                                                </a>
                                                <span class="text-xs text-gray-400 ml-1">{{ $utr ? number_format($utr, 2) : 'x.xx' }}</span>
                                            </div>
                                        @endforeach
                                        @if($homePlayers->isEmpty()) <span class="text-gray-400 italic text-xs">Default</span> @endif
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            <a href="{{ route('teams.show', $match->home_team_id) }}" class="hover:underline">{{ $match->homeTeam->name }}</a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if($court->courtSets->count() > 0)
                                            @foreach($court->courtSets->sortBy('set_number') as $set)
                                                <span class="mr-1"><span class="{{ $set->home_score > $set->away_score ? 'text-green-600 font-semibold' : 'text-gray-700' }}">{{ $set->home_score }}</span>-<span class="{{ $set->away_score > $set->home_score ? 'text-green-600 font-semibold' : 'text-gray-700' }}">{{ $set->away_score }}</span></span>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @foreach($awayPlayers as $cp)
                                            @php $utr = $type === 'singles' ? $cp->utr_singles_rating : $cp->utr_doubles_rating; @endphp
                                            <div>
                                                <a href="{{ route('players.show', $cp->player->id) }}?team={{ $cp->team_id }}&court={{ $type }}&line={{ $number }}#match-history" class="hover:underline {{ $awayWon ? 'text-green-600 font-semibold' : 'text-blue-600' }}">
                                                    {{ $cp->player->first_name }} {{ $cp->player->last_name }}
                                                </a>
                                                <span class="text-xs text-gray-400 ml-1">{{ $utr ? number_format($utr, 2) : 'x.xx' }}</span>
                                            </div>
                                        @endforeach
                                        @if($awayPlayers->isEmpty()) <span class="text-gray-400 italic text-xs">Default</span> @endif
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            <a href="{{ route('teams.show', $match->away_team_id) }}" class="hover:underline">{{ $match->awayTeam->name }}</a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('tennis-matches.show', $match->id) }}" class="text-blue-600 hover:underline text-xs">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile -->
                <div class="md:hidden divide-y divide-gray-200">
                    @foreach($courts as $court)
                        @php
                            $match = $court->tennisMatch;
                            $homePlayers = $court->courtPlayers->where('team_id', $match->home_team_id);
                            $awayPlayers = $court->courtPlayers->where('team_id', $match->away_team_id);
                            $homeWon = $court->home_score > $court->away_score;
                            $awayWon = $court->away_score > $court->home_score;
                        @endphp
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs text-gray-500">
                                    {{ $match->start_time ? \Carbon\Carbon::parse($match->start_time)->format('M j, Y') : '—' }}
                                </span>
                                <a href="{{ route('tennis-matches.show', $match->id) }}" class="text-blue-600 hover:underline text-xs">View match</a>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="text-xs text-gray-500 mb-1">
                                        <a href="{{ route('teams.show', $match->home_team_id) }}" class="hover:underline">{{ $match->homeTeam->name }}</a>
                                    </div>
                                    @foreach($homePlayers as $cp)
                                        @php $utr = $type === 'singles' ? $cp->utr_singles_rating : $cp->utr_doubles_rating; @endphp
                                        <div class="text-sm">
                                            <a href="{{ route('players.show', $cp->player->id) }}?team={{ $cp->team_id }}&court={{ $type }}&line={{ $number }}#match-history" class="hover:underline {{ $homeWon ? 'text-green-600 font-semibold' : 'text-blue-600' }}">{{ $cp->player->first_name }} {{ $cp->player->last_name }}</a>
                                            <span class="text-xs text-gray-400">{{ $utr ? number_format($utr, 2) : 'x.xx' }}</span>
                                        </div>
                                    @endforeach
                                    @if($homePlayers->isEmpty()) <span class="text-gray-400 italic text-xs">Default</span> @endif
                                </div>
                                <div class="text-center px-2">
                                    @if($court->courtSets->count() > 0)
                                        @foreach($court->courtSets->sortBy('set_number') as $set)
                                            <div class="text-sm"><span class="{{ $set->home_score > $set->away_score ? 'text-green-600 font-semibold' : 'text-gray-700' }}">{{ $set->home_score }}</span>-<span class="{{ $set->away_score > $set->home_score ? 'text-green-600 font-semibold' : 'text-gray-700' }}">{{ $set->away_score }}</span></div>
                                        @endforeach
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </div>
                                <div class="flex-1 text-right">
                                    <div class="text-xs text-gray-500 mb-1">
                                        <a href="{{ route('teams.show', $match->away_team_id) }}" class="hover:underline">{{ $match->awayTeam->name }}</a>
                                    </div>
                                    @foreach($awayPlayers as $cp)
                                        @php $utr = $type === 'singles' ? $cp->utr_singles_rating : $cp->utr_doubles_rating; @endphp
                                        <div class="text-sm">
                                            <a href="{{ route('players.show', $cp->player->id) }}?team={{ $cp->team_id }}&court={{ $type }}&line={{ $number }}#match-history" class="hover:underline {{ $awayWon ? 'text-green-600 font-semibold' : 'text-blue-600' }}">{{ $cp->player->first_name }} {{ $cp->player->last_name }}</a>
                                            <span class="text-xs text-gray-400">{{ $utr ? number_format($utr, 2) : 'x.xx' }}</span>
                                        </div>
                                    @endforeach
                                    @if($awayPlayers->isEmpty()) <span class="text-gray-400 italic text-xs">Default</span> @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Player Records Table -->
        @if(count($playerRecords) > 0)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mt-6">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-800">Player Records</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Player</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Team</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">W</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">L</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Win %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($playerRecords as $record)
                                @php
                                    $total = $record['wins'] + $record['losses'];
                                    $pct = $total ? round($record['wins'] / $total * 100) : 0;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">
                                        <a href="{{ route('players.show', $record['player']->id) }}?team={{ $record['team']->id }}&court={{ $type }}&line={{ $number }}#match-history" class="text-blue-600 hover:underline font-medium">
                                            {{ $record['player']->first_name }} {{ $record['player']->last_name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">
                                        <a href="{{ route('teams.show', $record['team']->id) }}" class="hover:underline">{{ $record['team']->name }}</a>
                                    </td>
                                    <td class="px-4 py-2 text-center text-green-600 font-semibold">{{ $record['wins'] }}</td>
                                    <td class="px-4 py-2 text-center text-red-600 font-semibold">{{ $record['losses'] }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="font-semibold {{ $pct >= 60 ? 'text-green-600' : ($pct <= 40 ? 'text-red-600' : 'text-gray-700') }}">{{ $pct }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        </div>
    </div>
</div>

<button id="back-to-top"
    onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
    class="fixed bottom-6 right-6 z-50 bg-blue-600 hover:bg-blue-700 text-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg opacity-0 pointer-events-none transition-opacity duration-200"
    aria-label="Back to top">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
    </svg>
</button>
<script>
    (function () {
        var btn = document.getElementById('back-to-top');
        window.addEventListener('scroll', function () {
            if (window.scrollY > 300) {
                btn.classList.remove('opacity-0', 'pointer-events-none');
            } else {
                btn.classList.add('opacity-0', 'pointer-events-none');
            }
        }, { passive: true });
    })();
</script>
@endsection
