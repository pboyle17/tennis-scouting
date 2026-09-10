@extends('layouts.app')

@section('title', $opponent ? $player->first_name . ' ' . $player->last_name . ' vs ' . $opponent->first_name . ' ' . $opponent->last_name : $player->first_name . ' ' . $player->last_name . ' — Head to Head')

@section('content')
<div class="container mx-auto px-4 py-6 md:p-6">
    <div class="max-w-5xl mx-auto">

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-3">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Head to Head</h1>
                <a href="{{ route('players.show', $player->id) }}" class="text-blue-600 hover:underline text-sm">← {{ $player->first_name }} {{ $player->last_name }}</a>
            </div>
        </div>

        <!-- Opponent Selector -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" action="{{ route('players.headToHead', $player->id) }}" id="h2hForm" class="flex flex-col sm:flex-row gap-3 items-end">
                <div class="relative flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Compare {{ $player->first_name }} against</label>
                    <input type="text" id="playerSearchInput" autocomplete="off"
                           placeholder="Search by name…"
                           value="{{ $opponent ? $opponent->first_name . ' ' . $opponent->last_name : '' }}"
                           class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <input type="hidden" name="opponent" id="opponentIdInput" value="{{ $opponent?->id }}">
                    <div id="playerDropdown" class="absolute z-20 w-full bg-white border border-gray-200 rounded shadow-lg hidden max-h-60 overflow-y-auto mt-1"></div>
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-6 rounded">
                    Compare
                </button>
            </form>
        </div>

        @if($opponent)

        <!-- Ratings Comparison -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Ratings</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="py-2 text-left text-gray-500 font-medium w-1/3"></th>
                            <th class="py-2 text-center font-semibold text-gray-800">
                                <a href="{{ route('players.show', $player->id) }}" class="hover:underline text-blue-600">{{ $player->first_name }} {{ $player->last_name }}</a>
                            </th>
                            <th class="py-2 text-center font-semibold text-gray-800">
                                <a href="{{ route('players.show', $opponent->id) }}" class="hover:underline text-blue-600">{{ $opponent->first_name }} {{ $opponent->last_name }}</a>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $rows = [
                                ['label' => 'UTR Singles',  'a' => $player->utr_singles_rating,  'b' => $opponent->utr_singles_rating],
                                ['label' => 'UTR Doubles',  'a' => $player->utr_doubles_rating,  'b' => $opponent->utr_doubles_rating],
                                ['label' => 'USTA Dynamic', 'a' => $player->USTA_dynamic_rating, 'b' => $opponent->USTA_dynamic_rating],
                            ];
                        @endphp
                        @foreach($rows as $row)
                            <tr>
                                <td class="py-3 text-gray-500">{{ $row['label'] }}</td>
                                @php $aHigher = $row['a'] !== null && $row['b'] !== null && $row['a'] > $row['b']; $bHigher = $row['a'] !== null && $row['b'] !== null && $row['b'] > $row['a']; @endphp
                                <td class="py-3 text-center font-semibold {{ $aHigher ? 'text-green-600' : 'text-gray-700' }}">{{ $row['a'] !== null ? number_format($row['a'], 2) : '—' }}</td>
                                <td class="py-3 text-center font-semibold {{ $bHigher ? 'text-green-600' : 'text-gray-700' }}">{{ $row['b'] !== null ? number_format($row['b'], 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Direct Matches -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                Head to Head Matches
                @if($directMatches->count())
                    @php $playerWins = $directMatches->where('player_cp.won', true)->count(); $opponentWins = $directMatches->where('opponent_cp.won', true)->count(); @endphp
                    <span class="ml-2 text-base font-normal text-gray-500">{{ $player->first_name }} {{ $playerWins }}–{{ $opponentWins }} {{ $opponent->first_name }}</span>
                @endif
            </h2>
            @if($directMatches->isEmpty())
                <p class="text-gray-500 text-sm">No recorded matches between these players.</p>
            @else
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">{{ $player->first_name }}</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Score</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">{{ $opponent->first_name }}</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($directMatches as $row)
                                @php $court = $row['court']; $pCp = $row['player_cp']; $oCp = $row['opponent_cp']; $match = $court->tennisMatch; $pIsHome = $pCp && $pCp->team_id === $match->home_team_id; @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $match->start_time ? \Carbon\Carbon::parse($match->start_time)->format('M j, Y') : '—' }}</td>
                                    <td class="px-4 py-3 {{ $pCp?->won ? 'text-green-600 font-semibold' : 'text-gray-700' }}">
                                        {{ $player->first_name }} {{ $player->last_name }}
                                        @if($pCp?->utr_singles_rating)<span class="text-xs text-gray-400 ml-1">({{ number_format($pCp->utr_singles_rating, 2) }})</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @foreach($court->courtSets->sortBy('set_number') as $set)
                                            <span class="mr-1">
                                                <span class="{{ ($pIsHome ? $set->home_score > $set->away_score : $set->away_score > $set->home_score) ? 'text-green-600 font-semibold' : 'text-gray-700' }}">{{ $pIsHome ? $set->home_score : $set->away_score }}</span>-<span class="{{ ($pIsHome ? $set->away_score > $set->home_score : $set->home_score > $set->away_score) ? 'text-green-600 font-semibold' : 'text-gray-700' }}">{{ $pIsHome ? $set->away_score : $set->home_score }}</span>
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 {{ $oCp?->won ? 'text-green-600 font-semibold' : 'text-gray-700' }}">
                                        {{ $opponent->first_name }} {{ $opponent->last_name }}
                                        @if($oCp?->utr_singles_rating)<span class="text-xs text-gray-400 ml-1">({{ number_format($oCp->utr_singles_rating, 2) }})</span>@endif
                                    </td>
                                    <td class="px-4 py-3"><a href="{{ route('tennis-matches.show', $match->id) }}" class="text-blue-600 hover:underline text-xs">View</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="md:hidden space-y-3">
                    @foreach($directMatches as $row)
                        @php $court = $row['court']; $pCp = $row['player_cp']; $oCp = $row['opponent_cp']; $match = $court->tennisMatch; $pIsHome = $pCp && $pCp->team_id === $match->home_team_id; @endphp
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between mb-2">
                                <span class="text-xs text-gray-500">{{ $match->start_time ? \Carbon\Carbon::parse($match->start_time)->format('M j, Y') : '—' }}</span>
                                <a href="{{ route('tennis-matches.show', $match->id) }}" class="text-blue-600 text-xs hover:underline">View</a>
                            </div>
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <div class="flex-1 {{ ($pCp && $pCp->won) ? 'text-green-600 font-semibold' : 'text-gray-700' }}">
                                    {{ $player->first_name }}
                                    @if($pCp && $pCp->utr_singles_rating)
                                        <span class="text-xs font-normal text-gray-400"> ({{ number_format($pCp->utr_singles_rating, 2) }})</span>
                                    @endif
                                </div>
                                <div class="text-center font-mono text-xs">
                                    @foreach($court->courtSets->sortBy('set_number') as $set)
                                        {{ $pIsHome ? $set->home_score : $set->away_score }}-{{ $pIsHome ? $set->away_score : $set->home_score }}{{ $loop->last ? '' : ' ' }}
                                    @endforeach
                                </div>
                                <div class="flex-1 text-right {{ ($oCp && $oCp->won) ? 'text-green-600 font-semibold' : 'text-gray-700' }}">
                                    {{ $opponent->first_name }}
                                    @if($oCp && $oCp->utr_singles_rating)
                                        <span class="text-xs font-normal text-gray-400"> ({{ number_format($oCp->utr_singles_rating, 2) }})</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Common Opponents -->
        @if($commonOpponents->isNotEmpty())
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Common Opponents</h2>
            <div class="divide-y divide-gray-200">
                @foreach($commonOpponents as $i => $row)
                    @php $opp = $row['player']; $pr = $row['player_record']; $or = $row['opponent_record']; @endphp
                    <div>
                        <!-- Summary row -->
                        <button type="button" onclick="toggleCommonMatches({{ $i }})"
                                class="w-full flex items-center justify-between px-2 py-3 hover:bg-gray-50 text-left">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="text-sm font-medium text-gray-800">
                                    <a href="{{ route('players.show', $opp->id) }}" onclick="event.stopPropagation()" class="hover:underline text-blue-600">{{ $opp->first_name }} {{ $opp->last_name }}</a>
                                </span>
                                @if($opp->utr_singles_rating)
                                    <span class="text-xs text-gray-400">({{ number_format($opp->utr_singles_rating, 2) }})</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-6 shrink-0">
                                <span class="text-sm text-center w-16">
                                    <span class="text-xs text-gray-500 block">{{ $player->first_name }}</span>
                                    <span class="{{ $pr['wins'] > $pr['losses'] ? 'text-green-600' : ($pr['losses'] > $pr['wins'] ? 'text-red-500' : 'text-gray-700') }} font-semibold">{{ $pr['wins'] }}-{{ $pr['losses'] }}</span>
                                </span>
                                <span class="text-sm text-center w-16">
                                    <span class="text-xs text-gray-500 block">{{ $opponent->first_name }}</span>
                                    <span class="{{ $or['wins'] > $or['losses'] ? 'text-green-600' : ($or['losses'] > $or['wins'] ? 'text-red-500' : 'text-gray-700') }} font-semibold">{{ $or['wins'] }}-{{ $or['losses'] }}</span>
                                </span>
                                <span id="chevron-{{ $i }}" class="text-gray-400 text-xs">▶</span>
                            </div>
                        </button>

                        <!-- Expanded match rows -->
                        <div id="common-matches-{{ $i }}" class="hidden bg-gray-50 px-4 pb-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3">
                                @foreach([['label' => $player->first_name . ' ' . $player->last_name, 'matches' => $row['player_matches'], 'subject_id' => $player->id], ['label' => $opponent->first_name . ' ' . $opponent->last_name, 'matches' => $row['opponent_matches'], 'subject_id' => $opponent->id]] as $side)
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $side['label'] }}</div>
                                        @if($side['matches']->isEmpty())
                                            <p class="text-xs text-gray-400">No recorded matches.</p>
                                        @else
                                            <div class="space-y-2">
                                                @foreach($side['matches'] as $mrow)
                                                    @php $c = $mrow['court']; $sCp = $mrow['subject_cp']; $m = $c->tennisMatch; $sIsHome = $sCp && $sCp->team_id === $m->home_team_id; @endphp
                                                    <div class="flex items-center justify-between text-sm bg-white rounded border border-gray-200 px-3 py-2">
                                                        <span class="text-gray-500 text-xs whitespace-nowrap">{{ $m->start_time ? \Carbon\Carbon::parse($m->start_time)->format('M j, Y') : '—' }}</span>
                                                        <span class="font-mono text-xs mx-2">
                                                            @foreach($c->courtSets->sortBy('set_number') as $set)
                                                                <span class="{{ ($sIsHome ? $set->home_score > $set->away_score : $set->away_score > $set->home_score) ? 'text-green-600 font-semibold' : 'text-gray-600' }}">{{ $sIsHome ? $set->home_score : $set->away_score }}</span>-<span class="{{ ($sIsHome ? $set->away_score > $set->home_score : $set->home_score > $set->away_score) ? 'text-green-600 font-semibold' : 'text-gray-600' }}">{{ $sIsHome ? $set->away_score : $set->home_score }}</span>
                                                                @if(!$loop->last) &nbsp; @endif
                                                            @endforeach
                                                        </span>
                                                        <span class="{{ $sCp?->won ? 'text-green-600 font-semibold' : 'text-red-500' }} text-xs">{{ $sCp?->won ? 'W' : 'L' }}</span>
                                                        <a href="{{ route('tennis-matches.show', $m->id) }}" class="text-blue-600 hover:underline text-xs ml-2">View</a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        @endif

    </div>
</div>

<script>
(function () {
    const players = @json($allPlayers->map(fn($p) => ['id' => $p->id, 'name' => $p->first_name . ' ' . $p->last_name, 'utr' => $p->utr_singles_rating]));
    const searchInput  = document.getElementById('playerSearchInput');
    const hiddenInput  = document.getElementById('opponentIdInput');
    const dropdown     = document.getElementById('playerDropdown');
    const form         = document.getElementById('h2hForm');

    searchInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        if (!q) { dropdown.classList.add('hidden'); return; }
        const hits = players.filter(p => p.name.toLowerCase().includes(q)).slice(0, 12);
        if (!hits.length) { dropdown.classList.add('hidden'); return; }
        dropdown.innerHTML = hits.map(p =>
            `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm" data-id="${p.id}" data-name="${p.name}">
                ${p.name}${p.utr ? ' <span class="text-gray-400 text-xs">(' + parseFloat(p.utr).toFixed(2) + ')</span>' : ''}
            </div>`
        ).join('');
        dropdown.classList.remove('hidden');
    });

    dropdown.addEventListener('click', function (e) {
        const item = e.target.closest('[data-id]');
        if (!item) return;
        hiddenInput.value = item.dataset.id;
        searchInput.value = item.dataset.name;
        dropdown.classList.add('hidden');
        form.submit();
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
})();

function toggleCommonMatches(i) {
    const el  = document.getElementById('common-matches-' + i);
    const ch  = document.getElementById('chevron-' + i);
    const open = !el.classList.contains('hidden');
    el.classList.toggle('hidden', open);
    ch.textContent = open ? '▶' : '▼';
}
</script>
@endsection
