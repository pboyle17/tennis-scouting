<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentMatchSet extends Model
{
    protected $fillable = ['tournament_match_id', 'set_number', 'winner_score', 'loser_score', 'winner_tiebreak', 'loser_tiebreak'];
}
