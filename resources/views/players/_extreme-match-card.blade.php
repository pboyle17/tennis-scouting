{{-- Best win / worst loss card. $entry comes from PlayerController::extremeMatchCandidates(). --}}
<div class="border rounded-lg p-4 cursor-pointer transition {{ $entry['label'] ? 'border-amber-200 bg-amber-50/40 hover:bg-amber-50' : 'border-gray-200 hover:bg-gray-50' }}" onclick="window.location='{{ $entry['url'] }}'">
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $resultLabel }}</span>
        <span class="text-xs text-gray-400">{{ $entry['date'] ? \Carbon\Carbon::parse($entry['date'])->format('M d, Y') : '' }}</span>
    </div>
    @if($entry['label'])
        <div class="text-xs text-gray-500 mb-1">{{ $entry['label'] }}</div>
    @endif
    <div class="text-sm text-gray-700 mb-1">
        @if($entry['partner'])
            <span class="text-gray-500">with</span>
            <a href="{{ route('players.show', $entry['partner']->id) }}" onclick="event.stopPropagation()" class="font-semibold text-blue-600 hover:underline">{{ $entry['partner']->first_name }} {{ $entry['partner']->last_name }}</a>
        @endif
        <span class="text-gray-500">vs</span>
        <span class="font-semibold">
            {{ $entry['opponents']->map(fn($o) => $o->first_name . ' ' . $o->last_name)->join(' / ') }}
        </span>
    </div>
    <div class="text-xs text-gray-500 mb-2">
        Opponent Current {{ $type === 'singles' ? 'UTR Singles' : 'UTR Doubles (avg)' }}:
        <span class="font-semibold text-gray-700">{{ number_format($entry['opponent_rating'], 2) }}</span>
    </div>
    @if(!empty($entry['sets']))
        <div class="flex items-center gap-3">
            @foreach($entry['sets'] as [$mine, $theirs, $tb])
                <span class="text-lg font-bold">
                    <span class="{{ $mine > $theirs ? 'text-green-600' : 'text-gray-500' }}">{{ $mine }}</span>
                    <span class="text-gray-300">-</span>
                    <span class="{{ $theirs > $mine ? 'text-green-600' : 'text-gray-500' }}">{{ $theirs }}</span>
                    @if($tb !== null)<span class="text-xs font-normal text-gray-400">({{ $tb }})</span>@endif
                </span>
            @endforeach
        </div>
    @endif
</div>
