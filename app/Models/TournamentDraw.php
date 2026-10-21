<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentDraw extends Model
{
    protected $fillable = ['tournament_id', 'utr_draw_id', 'name', 'team_type', 'gender', 'player_count'];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches()
    {
        return $this->hasMany(TournamentMatch::class);
    }
}
