@extends('layouts.app')

@section('title', 'Edit Player')

@section('content')
<div class="container mx-auto p-6">
@if(session('success'))
    <div class="bg-green-100 text-green-700 p-2 rounded mb-4">
        {{ session('success') }}
    </div>
@endif
@if(session('status'))
    <div class="bg-blue-100 text-blue-700 p-2 rounded mb-4">
        {{ session('status') }}
    </div>
@endif
@if(session('error'))
    <div class="bg-red-100 text-red-700 p-2 rounded mb-4">
        {{ session('error') }}
    </div>
@endif

@if(session('utr_search_results'))
    <div class="max-w-4xl mx-auto mb-6 bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">UTR ID Search Results for {{ $player->first_name }} {{ $player->last_name }}</h3>

        @php
            $results = session('utr_search_results');
            // Handle nested structure - results can be in 'players.hits' or just 'hits'
            $hits = $results['players']['hits'] ?? $results['hits'] ?? [];
        @endphp

        @if(count($hits) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Location</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Singles UTR</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Doubles UTR</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">UTR ID</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($hits as $hit)
                            @php
                                $source = $hit['source'] ?? [];
                                $firstName = $source['firstName'] ?? '';
                                $lastName = $source['lastName'] ?? '';
                                $location = $source['location']['display'] ?? '';
                                $singlesUtr = $source['singlesUtr'] ?? 0;
                                $doublesUtr = $source['doublesUtr'] ?? 0;
                                $singlesReliability = $source['ratingProgressSingles'] ?? 0;
                                $doublesReliability = $source['ratingProgressDoubles'] ?? 0;
                                $utrId = $source['id'] ?? '';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm">{{ $firstName }} {{ $lastName }}</td>
                                <td class="px-4 py-2 text-sm">{{ $location }}</td>
                                <td class="px-4 py-2 text-sm">
                                    {{ number_format($singlesUtr, 2) }}
                                    @if($singlesReliability == 100)
                                        <span class="text-green-600 font-bold" title="100% Reliable">✓</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    {{ number_format($doublesUtr, 2) }}
                                    @if($doublesReliability == 100)
                                        <span class="text-green-600 font-bold" title="100% Reliable">✓</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <a href="https://app.utrsports.net/profiles/{{ $utrId }}" target="_blank" class="text-blue-600 hover:underline">
                                        {{ $utrId }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <button onclick="setUtrData({{ $utrId }}, {{ $singlesUtr }}, {{ $doublesUtr }}, {{ $singlesReliability }}, {{ $doublesReliability }})" class="bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-1 rounded">
                                        Use This
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-600">No UTR profiles found for this player.</p>
        @endif
    </div>
@endif

<h1 class="text-3xl font-bold mb-6 text-center">Edit Player</h1>
    <div class="max-w-lg mx-auto mb-4 flex justify-center space-x-2">
        <form method="POST" action="{{ route('players.searchUtrId', $player->id) }}{{ isset($returnUrl) ? '?return_url=' . urlencode($returnUrl) : '' }}">
            @csrf
            <button type="submit" class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                🔍 Search UTR ID
            </button>
        </form>
        <form method="POST" action="{{ route('players.updateUtrSingle', $player->id) }}">
            @csrf
            <button type="submit" class="inline-flex items-center bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded">
                🔄 Update UTR
            </button>
        </form>
    </div>

    <form action="{{ route('players.update', $player->id) }}" method="POST" class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        @csrf
        @method('PUT')

        @if(isset($returnUrl))
            <input type="hidden" name="return_url" value="{{ $returnUrl }}">
        @endif

        <div class="mb-4">
            <label class="block mb-1" for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $player->first_name) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $player->last_name) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="utr_id">UTR Id</label>
            <input type="number" name="utr_id" id="utr_id" value="{{ old('utr_id', $player->utr_id) }}" class="w-full border rounded p-2">
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="tennis_record_link">Tennis Record Link</label>
            <input type="url" name="tennis_record_link" id="tennis_record_link" value="{{ old('tennis_record_link', $player->tennis_record_link) }}" class="w-full border rounded p-2" placeholder="https://www.tennisrecord.com/...">
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="utr_singles_rating">UTR Singles Rating</label>
            <div class="flex items-center space-x-2">
                <input type="number" step=".01" name="utr_singles_rating" id="utr_singles_rating" value="{{ old('utr_singles_rating', $player->utr_singles_rating) }}" class="flex-1 border rounded p-2">
                @if($player->utr_singles_reliable)
                    <span class="flex items-center text-green-600 font-semibold text-sm" title="100% Reliable Rating">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Verified
                    </span>
                @elseif($player->utr_singles_rating)
                    <span class="flex items-center text-yellow-600 font-semibold text-sm" title="Rating reliability less than 100%">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        Unverified
                    </span>
                @endif
            </div>
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="utr_doubles_rating">UTR Doubles Rating</label>
            <div class="flex items-center space-x-2">
                <input type="number" step=".01" name="utr_doubles_rating" id="utr_doubles_rating" value="{{ old('utr_doubles_rating', $player->utr_doubles_rating) }}" class="flex-1 border rounded p-2">
                @if($player->utr_doubles_reliable)
                    <span class="flex items-center text-green-600 font-semibold text-sm" title="100% Reliable Rating">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Verified
                    </span>
                @elseif($player->utr_doubles_rating)
                    <span class="flex items-center text-yellow-600 font-semibold text-sm" title="Rating reliability less than 100%">
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        Unverified
                    </span>
                @endif
            </div>
        </div>

        <div class="mb-4">
            <label class="block mb-1" for="USTA_rating">USTA Rating</label>
            <input type="number" step=".5" name="USTA_rating" id="USTA_rating" value="{{ old('USTA_rating', $player->USTA_rating) }}" class="w-full border rounded p-2">
        </div>

        <div class="flex justify-between">
          <a href="{{ route('players.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">Back to list</a>
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update Player</button>
        </div>
      </form>

      @if($player->teams->count() > 0)
        <div class="max-w-lg mx-auto mt-6 bg-white p-6 rounded shadow">
          <h3 class="text-lg font-semibold mb-3">Teams</h3>
          <div class="space-y-2">
            @foreach($player->teams as $team)
              <a href="{{ route('teams.show', $team->id) }}" class="block p-3 bg-gray-50 hover:bg-gray-100 rounded border border-gray-200 transition">
                <div class="font-medium text-gray-800">{{ $team->name }}</div>
              </a>
            @endforeach
          </div>
        </div>
      @endif

      <div class="max-w-lg mx-auto mt-6">
        <form method="POST" action="{{ route('players.destroy', $player->id) }}" onsubmit="return confirm('Are you sure you want to delete {{ $player->first_name }} {{ $player->last_name }}? This action cannot be undone.');">
          @csrf
          @method('DELETE')
          <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded">
            Delete Player
          </button>
        </form>
      </div>
  </div>
</div>

<script>
    function setUtrData(utrId, singlesUtr, doublesUtr, singlesReliability = 0, doublesReliability = 0) {
        // Set UTR ID
        document.getElementById('utr_id').value = utrId;

        // Set Singles UTR
        document.getElementById('utr_singles_rating').value = singlesUtr;

        // Set Doubles UTR
        document.getElementById('utr_doubles_rating').value = doublesUtr;

        // Scroll to the form
        document.getElementById('utr_id').scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Highlight all filled fields
        const fields = ['utr_id', 'utr_singles_rating', 'utr_doubles_rating'];
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            field.classList.add('ring-2', 'ring-green-500');
            field.focus();
        });

        // Show a message about reliability
        let reliabilityMessage = '';
        if (singlesReliability == 100 && doublesReliability == 100) {
            reliabilityMessage = '✓ Both ratings are 100% verified!';
        } else if (singlesReliability == 100) {
            reliabilityMessage = '✓ Singles rating is 100% verified!';
        } else if (doublesReliability == 100) {
            reliabilityMessage = '✓ Doubles rating is 100% verified!';
        } else {
            reliabilityMessage = 'Note: These ratings are not yet 100% verified';
        }

        // Display reliability message as a temporary notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-blue-100 border border-blue-300 text-blue-800 px-4 py-3 rounded shadow-lg z-50';
        notification.innerHTML = `<p class="font-semibold">${reliabilityMessage}</p>`;
        document.body.appendChild(notification);

        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);

        // Remove highlight after 2 seconds
        setTimeout(() => {
            fields.forEach(fieldId => {
                document.getElementById(fieldId).classList.remove('ring-2', 'ring-green-500');
            });
        }, 2000);
    }
</script>
@endsection
