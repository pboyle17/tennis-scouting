<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TennisMatch extends Model
{
    protected $fillable = [
        'league_id',
        'home_team_id',
        'away_team_id',
        'location',
        'start_time',
        'home_score',
        'away_score',
        'external_id',
        'tennis_record_match_link'
    ];

    protected $casts = [
        'start_time' => 'datetime',
    ];

    /**
     * Get the league that the match belongs to
     */
    public function league()
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the home team
     */
    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Get the away team
     */
    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
