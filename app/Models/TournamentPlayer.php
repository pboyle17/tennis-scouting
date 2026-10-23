<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TournamentPlayer extends Pivot
{
    protected $table = 'tournament_player';

    protected $casts = [
        'flights' => 'array',
    ];
}
