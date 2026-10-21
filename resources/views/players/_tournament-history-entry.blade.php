{{-- A tournament match in the player's match history. Expects $tmp (TournamentMatchPlayer), $player, and $mode ('card' | 'row'). --}}
@php
    $tm = $tmp->match;
    $draw = $tm->draw;
    $tournament = $draw->tournament;
    $isDoubles = $draw->team_type === 'doubles';
    $ratingField = $isDoubles ? 'utr_doubles_rating' : 'utr_singles_rating';
    $partner = $tm->matchPlayers->first(fn($mp) => $mp->won === $tmp->won && $mp->player_id !== $player->id);
    $opponents = $tm->matchPlayers->where('won', '!==', $tmp->won)->sortBy('position');
    // Set scores from this player's side: [mine, theirs, tiebreak points of whoever lost the set]
    $sets = $tm->sets->map(fn($set) => $tmp->won
        ? [$set->winner_score, $set->loser_score, $set->loser_tiebreak]
        : [$set->loser_score, $set->winner_score, $set->loser_tiebreak]);
    $filterAttrs = 'data-team-id="tournament-' . $tournament->id . '" data-court-type="' . e($draw->team_type) . '" data-court-line=""'
        . ' data-partner-id="' . ($partner?->player_id ?? '') . '" data-result="' . ($tmp->won ? 'win' : 'loss') . '"';
    $url = route('tournaments.show', $tournament->id);
@endphp

@php
    $ratingSpans = function ($mp, $class) use ($ratingField) {
        $utr = $mp->$ratingField;
        $usta = $mp->player->USTA_dynamic_rating;
        return '<span class="rating-utr"><span class="' . $class . '">(' . ($utr ? number_format($utr, 2) : ' - ') . ')</span></span>'
            . '<span class="rating-usta"><span class="' . $class . '">(' . ($usta ? number_format($usta, 2) : ' - ') . ')</span></span>';
    };
@endphp

@if($mode === 'card')
    <div onclick="window.location.href='{{ $url }}'" class="match-card block bg-amber-50/40 rounded-lg border border-amber-200 p-4 hover:bg-amber-50 transition cursor-pointer" {!! $filterAttrs !!}>
        <div class="flex justify-between items-start mb-2">
            <div class="text-xs text-gray-500">{{ $tm->played_on ? $tm->played_on->format('M d, Y') : 'N/A' }}</div>
            <div class="text-xs text-gray-600 font-medium">🏆 {{ $tm->round_name ?? 'Round robin' }}</div>
        </div>
        <div class="text-sm font-semibold text-gray-800 mb-1">{{ $tournament->name }}</div>
        <div class="text-xs text-gray-600 mb-2">{{ $draw->name }}</div>

        <div class="text-xs mb-3 flex flex-wrap items-center gap-x-1">
            <span class="font-medium text-blue-600">{{ $player->first_name }} {{ $player->last_name }}</span>
            {!! $ratingSpans($tmp, 'text-gray-400') !!}
            @if($partner)
                <span class="text-gray-400">/</span>
                <a href="{{ route('players.show', $partner->player_id) }}" onclick="event.stopPropagation()" class="font-medium text-blue-600 hover:underline">{{ $partner->player->first_name }} {{ $partner->player->last_name }}</a>
                {!! $ratingSpans($partner, 'text-gray-400') !!}
            @endif
            <span class="text-gray-400">vs</span>
            @foreach($opponents as $opp)
                @if(!$loop->first)<span class="text-gray-400">/</span>@endif
                <a href="{{ route('players.show', $opp->player_id) }}" onclick="event.stopPropagation()" class="font-medium text-red-600 hover:underline">{{ $opp->player->first_name }} {{ $opp->player->last_name }}</a>
                {!! $ratingSpans($opp, 'text-gray-400') !!}
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <div class="text-sm font-semibold">
                @if($tm->isWalkover())
                    <span class="text-orange-500 italic font-normal">Walkover</span>
                @else
                    @foreach($sets as [$ms, $os, $tb])
                        <span class="{{ $ms > $os ? 'text-green-600' : 'text-gray-700' }}">{{ $ms }}</span><span class="text-gray-400">-</span><span class="{{ $os > $ms ? 'text-red-600' : 'text-gray-700' }}">{{ $os }}</span>@if($tb !== null)<span class="text-xs text-gray-400">({{ $tb }})</span>@endif
                        @if(!$loop->last) <span class="text-gray-300 mx-1">|</span> @endif
                    @endforeach
                @endif
            </div>
            <span class="{{ $tmp->won ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} text-xs px-2 py-0.5 rounded font-semibold">{{ $tmp->won ? 'Win' : 'Loss' }}</span>
        </div>
    </div>
@else
    <tr class="match-row hover:bg-gray-50 bg-amber-50/30" {!! $filterAttrs !!}>
        <td class="px-4 py-3 text-sm text-gray-700">{{ $tm->played_on ? $tm->played_on->format('M d, Y') : 'N/A' }}</td>
        <td class="px-4 py-3 text-sm">
            <a href="{{ $url }}" class="text-blue-600 hover:underline">
                <div class="font-semibold">🏆 {{ $tournament->name }}</div>
                <div class="text-gray-600">{{ $draw->name }}</div>
            </a>
        </td>
        <td class="px-4 py-3 text-sm text-gray-700">{{ $tm->round_name ?? 'Round robin' }}</td>
        <td class="px-4 py-3 text-sm text-gray-700">
            <div class="{{ $isDoubles ? 'mb-1' : '' }}">
                <span class="font-semibold text-blue-600">{{ $player->first_name }} {{ $player->last_name }}</span>
                {!! $ratingSpans($tmp, 'text-xs text-gray-400') !!}
                @if($partner)
                    / <a href="{{ route('players.show', $partner->player_id) }}" class="text-blue-600 hover:underline font-semibold">{{ $partner->player->first_name }} {{ $partner->player->last_name }}</a>
                    {!! $ratingSpans($partner, 'text-xs text-gray-400') !!}
                @endif
                @unless($isDoubles)<span class="text-xs text-gray-500 mx-1">vs</span>@endunless
            @if($isDoubles)</div><div><span class="text-xs text-gray-500">vs</span>@endif
                @foreach($opponents as $opp)
                    @if(!$loop->first) / @endif
                    <a href="{{ route('players.show', $opp->player_id) }}" class="text-red-600 hover:underline {{ $isDoubles ? '' : 'font-semibold' }}">{{ $opp->player->first_name }} {{ $opp->player->last_name }}</a>
                    {!! $ratingSpans($opp, 'text-xs text-gray-400') !!}
                @endforeach
            </div>
        </td>
        <td class="px-4 py-3 text-sm">
            @if($tm->isWalkover())
                <span class="text-orange-500 italic">Walkover</span>
            @else
                @foreach($sets as [$ms, $os, $tb])
                    <div class="whitespace-nowrap">
                        <span class="{{ $ms > $os ? 'font-semibold text-green-600' : 'text-gray-700' }}">{{ $ms }}</span>
                        <span class="text-gray-700">-</span>
                        <span class="{{ $os > $ms ? 'font-semibold text-red-600' : 'text-gray-700' }}">{{ $os }}</span>
                        @if($tb !== null)<span class="text-xs text-gray-400">({{ $tb }})</span>@endif
                    </div>
                @endforeach
            @endif
        </td>
        <td class="px-4 py-3 text-sm">
            @if($tmp->won)
                <span class="bg-green-100 text-green-800 px-2 py-1 rounded font-semibold">Win</span>
            @else
                <span class="bg-red-100 text-red-800 px-2 py-1 rounded font-semibold">Loss</span>
            @endif
        </td>
        <td class="px-4 py-3 text-sm text-center">
            @if($tournament->utr_link)
                <a href="{{ $tournament->utr_link }}" target="_blank" rel="noopener noreferrer" class="inline-block hover:opacity-70 transition-opacity" title="View on UTR">
                    <img src="{{ asset('images/utr_logo.avif') }}" alt="UTR" class="h-6 w-6">
                </a>
            @else
                <span class="text-gray-400">-</span>
            @endif
        </td>
    </tr>
@endif
