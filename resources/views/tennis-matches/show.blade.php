@extends('layouts.app')

@section('title', 'Match Details')

@section('content')
<div class="container mx-auto p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Match Details</h1>
            <div class="flex space-x-2">
                @if($match->tennis_record_match_link)
                    <form method="POST" action="{{ route('tennis-matches.syncFromTennisRecord', $match->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded" title="Sync match details from Tennis Record">
                            🎾 Sync from Tennis Record
                        </button>
                    </form>
                @endif
                <a href="{{ route('tennis-matches.edit', $match->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    ✏️ Edit Match
                </a>
                @if($match->league)
                    <a href="{{ route('leagues.show', $match->league->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                        ← Back to League
                    </a>
                @else
                    <a href="{{ route('teams.show', $match->homeTeam->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                        ← Back to Team
                    </a>
                @endif
            </div>
        </div>

        @include('partials.tabs')

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Match Information Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
            <!-- League Info -->
            @if($match->league)
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-3">
                    <div class="text-sm text-gray-600">
                        League: <a href="{{ route('leagues.show', $match->league->id) }}" class="text-blue-600 hover:underline font-semibold">{{ $match->league->name }}</a>
                    </div>
                </div>
            @endif

            <!-- Match Score Display -->
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <!-- Home Team -->
                    <div class="flex-1 text-center">
                        <a href="{{ route('teams.show', $match->homeTeam->id) }}" class="text-blue-600 hover:underline">
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ $match->homeTeam->name }}</h2>
                        </a>
                        <div class="text-sm text-gray-600">Home</div>
                    </div>

                    <!-- Score -->
                    <div class="px-8">
                        @if($match->home_score !== null && $match->away_score !== null)
                            <div class="flex items-center space-x-4">
                                <div class="text-5xl font-bold {{ $match->home_score > $match->away_score ? 'text-green-600' : 'text-gray-700' }}">
                                    {{ $match->home_score }}
                                </div>
                                <div class="text-3xl text-gray-400">-</div>
                                <div class="text-5xl font-bold {{ $match->away_score > $match->home_score ? 'text-green-600' : 'text-gray-700' }}">
                                    {{ $match->away_score }}
                                </div>
                            </div>
                        @else
                            <div class="text-2xl text-gray-400 italic">Not played</div>
                        @endif
                    </div>

                    <!-- Away Team -->
                    <div class="flex-1 text-center">
                        <a href="{{ route('teams.show', $match->awayTeam->id) }}" class="text-blue-600 hover:underline">
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ $match->awayTeam->name }}</h2>
                        </a>
                        <div class="text-sm text-gray-600">Away</div>
                    </div>
                </div>

                <!-- Match Details -->
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-600 mb-1">Date & Time</div>
                            <div class="text-lg font-semibold text-gray-800">
                                @if($match->start_time)
                                    {{ $match->start_time->format('l, F j, Y') }}
                                    <div class="text-sm text-gray-600 font-normal">{{ $match->start_time->format('g:i A') }}</div>
                                @else
                                    <span class="text-gray-400 italic">TBD</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">Location</div>
                            <div class="text-lg font-semibold text-gray-800">
                                {{ $match->location ?? 'TBD' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tennis Record Link -->
                @if($match->tennis_record_match_link)
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <a href="{{ $match->tennis_record_match_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                            🎾 View on Tennis Record
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Match Preview -->
        @if((!empty($homeCourtStats) || !empty($awayCourtStats)))
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-3">
                    <h2 class="text-lg font-semibold text-gray-800">Match Preview - Court Position Averages</h2>
                    <p class="text-sm text-gray-500 mt-1">Click on any row to see player/team details</p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Home Team Stats -->
                        <div>
                            <h3 class="text-md font-semibold text-gray-800 mb-3">{{ $match->homeTeam->name }}</h3>
                            @if(!empty($homeCourtStats))
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Court</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Avg UTR</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Avg USTA</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Win %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($homeCourtStats as $index => $stat)
                                            <tr class="hover:bg-gray-50 cursor-pointer border-t border-gray-200 home-court-row" data-court-index="{{ $index }}">
                                                <td class="px-3 py-2 text-sm text-gray-700">
                                                    <span class="inline-block w-3 transition-transform duration-200">▶</span>
                                                    {{ ucfirst($stat['court_type']) }} #{{ $stat['court_number'] }}
                                                </td>
                                                <td class="px-3 py-2 text-sm text-center text-gray-700">
                                                    @if($stat['court_type'] === 'singles' && $stat['avg_utr_singles'])
                                                        {{ number_format($stat['avg_utr_singles'], 2) }}
                                                    @elseif($stat['court_type'] === 'doubles' && $stat['avg_utr_doubles'])
                                                        {{ number_format($stat['avg_utr_doubles'], 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-sm text-center text-gray-700">
                                                    @if($stat['avg_usta_dynamic'])
                                                        {{ number_format($stat['avg_usta_dynamic'], 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-sm text-center">
                                                    @if($stat['court_win_percentage'] !== null)
                                                        <span class="font-semibold {{ $stat['court_win_percentage'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ number_format($stat['court_win_percentage'], 1) }}%
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr class="home-court-details hidden" data-court-index="{{ $index }}">
                                                <td colspan="4" class="px-3 py-2 bg-gray-50">
                                                    @if(!empty($stat['players']))
                                                        <div class="ml-6 text-xs">
                                                            <table class="min-w-full">
                                                                <thead class="bg-gray-100">
                                                                    <tr>
                                                                        <th class="px-2 py-1 text-left text-xs font-semibold text-gray-600">Player/Team</th>
                                                                        <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600">Record</th>
                                                                        <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600">Win%</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($stat['players'] as $player)
                                                                        <tr class="hover:bg-gray-100">
                                                                            <td class="px-2 py-1 text-gray-700">
                                                                                <div class="relative group cursor-pointer inline-block">
                                                                                    <span>{{ $player['player_name'] }}</span>
                                                                                    @if($player['avg_utr'] || $player['avg_usta'])
                                                                                        <div class="absolute left-0 bottom-full mb-2
                                                                                                    opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none
                                                                                                    bg-gray-800 text-white text-xs rounded py-1 px-2
                                                                                                    whitespace-nowrap z-50">
                                                                                            @if($player['avg_utr'])
                                                                                                UTR: {{ number_format($player['avg_utr'], 2) }}
                                                                                            @endif
                                                                                            @if($player['avg_utr'] && $player['avg_usta'])
                                                                                                <br>
                                                                                            @endif
                                                                                            @if($player['avg_usta'])
                                                                                                USTA: {{ number_format($player['avg_usta'], 2) }}
                                                                                            @endif
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            </td>
                                                                            <td class="px-2 py-1 text-center text-gray-700">
                                                                                <span class="text-green-600 font-semibold">{{ $player['wins'] }}</span>-<span class="text-red-600 font-semibold">{{ $player['losses'] }}</span>
                                                                            </td>
                                                                            <td class="px-2 py-1 text-center">
                                                                                @if($player['total'] > 0)
                                                                                    <span class="font-semibold {{ $player['win_percentage'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                                                                        {{ number_format($player['win_percentage'], 1) }}%
                                                                                    </span>
                                                                                @else
                                                                                    -
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-gray-500 text-sm">No court statistics available</p>
                            @endif
                        </div>

                        <!-- Away Team Stats -->
                        <div>
                            <h3 class="text-md font-semibold text-gray-800 mb-3">{{ $match->awayTeam->name }}</h3>
                            @if(!empty($awayCourtStats))
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Court</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Avg UTR</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Avg USTA</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Win %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($awayCourtStats as $index => $stat)
                                            <tr class="hover:bg-gray-50 cursor-pointer border-t border-gray-200 away-court-row" data-court-index="{{ $index }}">
                                                <td class="px-3 py-2 text-sm text-gray-700">
                                                    <span class="inline-block w-3 transition-transform duration-200">▶</span>
                                                    {{ ucfirst($stat['court_type']) }} #{{ $stat['court_number'] }}
                                                </td>
                                                <td class="px-3 py-2 text-sm text-center text-gray-700">
                                                    @if($stat['court_type'] === 'singles' && $stat['avg_utr_singles'])
                                                        {{ number_format($stat['avg_utr_singles'], 2) }}
                                                    @elseif($stat['court_type'] === 'doubles' && $stat['avg_utr_doubles'])
                                                        {{ number_format($stat['avg_utr_doubles'], 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-sm text-center text-gray-700">
                                                    @if($stat['avg_usta_dynamic'])
                                                        {{ number_format($stat['avg_usta_dynamic'], 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-sm text-center">
                                                    @if($stat['court_win_percentage'] !== null)
                                                        <span class="font-semibold {{ $stat['court_win_percentage'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ number_format($stat['court_win_percentage'], 1) }}%
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr class="away-court-details hidden" data-court-index="{{ $index }}">
                                                <td colspan="4" class="px-3 py-2 bg-gray-50">
                                                    @if(!empty($stat['players']))
                                                        <div class="ml-6 text-xs">
                                                            <table class="min-w-full">
                                                                <thead class="bg-gray-100">
                                                                    <tr>
                                                                        <th class="px-2 py-1 text-left text-xs font-semibold text-gray-600">Player/Team</th>
                                                                        <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600">Record</th>
                                                                        <th class="px-2 py-1 text-center text-xs font-semibold text-gray-600">Win%</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($stat['players'] as $player)
                                                                        <tr class="hover:bg-gray-100">
                                                                            <td class="px-2 py-1 text-gray-700">
                                                                                <div class="relative group cursor-pointer inline-block">
                                                                                    <span>{{ $player['player_name'] }}</span>
                                                                                    @if($player['avg_utr'] || $player['avg_usta'])
                                                                                        <div class="absolute left-0 bottom-full mb-2
                                                                                                    opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none
                                                                                                    bg-gray-800 text-white text-xs rounded py-1 px-2
                                                                                                    whitespace-nowrap z-50">
                                                                                            @if($player['avg_utr'])
                                                                                                UTR: {{ number_format($player['avg_utr'], 2) }}
                                                                                            @endif
                                                                                            @if($player['avg_utr'] && $player['avg_usta'])
                                                                                                <br>
                                                                                            @endif
                                                                                            @if($player['avg_usta'])
                                                                                                USTA: {{ number_format($player['avg_usta'], 2) }}
                                                                                            @endif
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            </td>
                                                                            <td class="px-2 py-1 text-center text-gray-700">
                                                                                <span class="text-green-600 font-semibold">{{ $player['wins'] }}</span>-<span class="text-red-600 font-semibold">{{ $player['losses'] }}</span>
                                                                            </td>
                                                                            <td class="px-2 py-1 text-center">
                                                                                @if($player['total'] > 0)
                                                                                    <span class="font-semibold {{ $player['win_percentage'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                                                                        {{ number_format($player['win_percentage'], 1) }}%
                                                                                    </span>
                                                                                @else
                                                                                    -
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-gray-500 text-sm">No court statistics available</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Courts Table -->
        @if($match->courts->count() > 0)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                <div class="bg-gray-50 border-b border-gray-200 px-6 py-3">
                    <h2 class="text-lg font-semibold text-gray-800">Court Results</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Court</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Home Players</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Away Players</th>
                                @env('local')
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                @endenv
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($match->courts->sortBy(function($court) {
                                // Singles (1) come before doubles (2), then sort by court number
                                return ($court->court_type === 'singles' ? '1' : '2') . str_pad($court->court_number, 3, '0', STR_PAD_LEFT);
                            }) as $court)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ ucfirst($court->court_type) }} #{{ $court->court_number }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @php
                                            $homePlayers = $court->courtPlayers->where('team_id', $match->home_team_id);
                                        @endphp
                                        @foreach($homePlayers as $cp)
                                            @php
                                                $utrRating = $court->court_type === 'singles' ? $cp->utr_singles_rating : $cp->utr_doubles_rating;
                                                $ustaRating = $cp->usta_dynamic_rating;
                                            @endphp
                                            <div class="relative group {{ $cp->won ? 'text-green-600 font-semibold' : 'text-gray-700' }}">
                                                <span>{{ $cp->player->first_name }} {{ $cp->player->last_name }}</span>
                                                @if($utrRating || $ustaRating)
                                                    <div class="absolute left-0 bottom-full mb-2
                                                                opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none
                                                                bg-gray-800 text-white text-xs rounded py-1 px-2
                                                                whitespace-nowrap z-50">
                                                        @if($utrRating)
                                                            UTR: {{ number_format($utrRating, 2) }}
                                                        @endif
                                                        @if($utrRating && $ustaRating)
                                                            <br>
                                                        @endif
                                                        @if($ustaRating)
                                                            USTA: {{ number_format($ustaRating, 2) }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($court->courtSets->count() > 0)
                                            <div class="text-sm font-semibold">
                                                @foreach($court->courtSets->sortBy('set_number') as $set)
                                                    <div>
                                                        <span class="{{ $set->home_score > $set->away_score ? 'text-green-600' : 'text-gray-900' }}">{{ $set->home_score }}</span>
                                                        <span class="text-gray-900">-</span>
                                                        <span class="{{ $set->away_score > $set->home_score ? 'text-green-600' : 'text-gray-900' }}">{{ $set->away_score }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @php
                                            $awayPlayers = $court->courtPlayers->where('team_id', $match->away_team_id);
                                        @endphp
                                        @foreach($awayPlayers as $cp)
                                            @php
                                                $utrRating = $court->court_type === 'singles' ? $cp->utr_singles_rating : $cp->utr_doubles_rating;
                                                $ustaRating = $cp->usta_dynamic_rating;
                                            @endphp
                                            <div class="relative group {{ $cp->won ? 'text-green-600 font-semibold' : 'text-gray-700' }}">
                                                <span>{{ $cp->player->first_name }} {{ $cp->player->last_name }}</span>
                                                @if($utrRating || $ustaRating)
                                                    <div class="absolute left-0 bottom-full mb-2
                                                                opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none
                                                                bg-gray-800 text-white text-xs rounded py-1 px-2
                                                                whitespace-nowrap z-50">
                                                        @if($utrRating)
                                                            UTR: {{ number_format($utrRating, 2) }}
                                                        @endif
                                                        @if($utrRating && $ustaRating)
                                                            <br>
                                                        @endif
                                                        @if($ustaRating)
                                                            USTA: {{ number_format($ustaRating, 2) }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                    @env('local')
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form method="POST" action="{{ route('courts.destroy', $court->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this court?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 ml-3">Delete</button>
                                            </form>
                                        </td>
                                    @endenv
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center text-gray-500">
                <p class="text-sm">No court results available. Click "Sync from Tennis Record" to import court results.</p>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Home Team Court Position Expand/Collapse
        const homeCourtRows = document.querySelectorAll('.home-court-row');
        homeCourtRows.forEach(row => {
            row.addEventListener('click', function() {
                const courtIndex = this.dataset.courtIndex;
                const detailsRow = document.querySelector(`.home-court-details[data-court-index="${courtIndex}"]`);
                const arrow = this.querySelector('span');

                if (detailsRow) {
                    detailsRow.classList.toggle('hidden');

                    // Rotate arrow
                    if (detailsRow.classList.contains('hidden')) {
                        arrow.style.transform = 'rotate(0deg)';
                    } else {
                        arrow.style.transform = 'rotate(90deg)';
                    }
                }
            });
        });

        // Away Team Court Position Expand/Collapse
        const awayCourtRows = document.querySelectorAll('.away-court-row');
        awayCourtRows.forEach(row => {
            row.addEventListener('click', function() {
                const courtIndex = this.dataset.courtIndex;
                const detailsRow = document.querySelector(`.away-court-details[data-court-index="${courtIndex}"]`);
                const arrow = this.querySelector('span');

                if (detailsRow) {
                    detailsRow.classList.toggle('hidden');

                    // Rotate arrow
                    if (detailsRow.classList.contains('hidden')) {
                        arrow.style.transform = 'rotate(0deg)';
                    } else {
                        arrow.style.transform = 'rotate(90deg)';
                    }
                }
            });
        });
    });
</script>
@endsection
