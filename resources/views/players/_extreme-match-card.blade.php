@php
    $cp = $entry['court_player'];
    $court = $cp->court;
    $match = $court->tennisMatch;
    $isHomeTeam = $cp->team_id === $match->home_team_id;
    $sets = $court->courtSets->sortBy('set_number')->map(fn($set) => [
        'my'  => $isHomeTeam ? $set->home_score : $set->away_score,
        'opp' => $isHomeTeam ? $set->away_score : $set->home_score,
    ]);
@endphp
<div class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-50 transition" onclick="window.location='{{ route('tennis-matches.show', $match->id) }}'">
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $resultLabel }}</span>
        <span class="text-xs text-gray-400">{{ $match->start_time ? $match->start_time->format('M d, Y') : '' }}</span>
    </div>
    <div class="text-sm text-gray-700 mb-1">
        <span class="text-gray-500">vs</span>
        <span class="font-semibold">
            {{ $entry['opponents']->map(fn($o) => $o->player->first_name . ' ' . $o->player->last_name)->join(' / ') }}
        </span>
    </div>
    <div class="text-xs text-gray-500 mb-2">
        Opponent Current {{ $type === 'singles' ? 'UTR Singles' : 'UTR Doubles (avg)' }}:
        <span class="font-semibold text-gray-700">{{ number_format($entry['opponent_rating'], 2) }}</span>
    </div>
    @if($sets->isNotEmpty())
        <div class="flex items-center gap-3">
            @foreach($sets as $set)
                <span class="text-lg font-bold">
                    <span class="{{ $set['my'] > $set['opp'] ? 'text-green-600' : 'text-gray-500' }}">{{ $set['my'] }}</span>
                    <span class="text-gray-300">-</span>
                    <span class="{{ $set['opp'] > $set['my'] ? 'text-green-600' : 'text-gray-500' }}">{{ $set['opp'] }}</span>
                </span>
            @endforeach
        </div>
    @endif
</div>
