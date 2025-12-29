<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    protected $fillable = [
        'tennis_match_id',
        'court_type',
        'court_number',
        'home_score',
        'away_score',
    ];

    public function tennisMatch(): BelongsTo
    {
        return $this->belongsTo(TennisMatch::class);
    }

    public function courtPlayers(): HasMany
    {
        return $this->hasMany(CourtPlayer::class);
    }

    public function homePlayers(): HasMany
    {
        return $this->hasMany(CourtPlayer::class)->where('team_id', $this->tennisMatch->home_team_id);
    }

    public function awayPlayers(): HasMany
    {
        return $this->hasMany(CourtPlayer::class)->where('team_id', $this->tennisMatch->away_team_id);
    }

    public function courtSets(): HasMany
    {
        return $this->hasMany(CourtSet::class);
    }
}
