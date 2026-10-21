<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentMatchPlayer extends Model
{
    protected $fillable = ['tournament_match_id', 'player_id', 'won', 'position', 'utr_singles_rating', 'utr_doubles_rating'];

    protected $casts = [
        'won' => 'boolean',
    ];

    public function match()
    {
        return $this->belongsTo(TournamentMatch::class, 'tournament_match_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
