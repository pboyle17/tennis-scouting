<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtPlayer extends Model
{
    protected $fillable = [
        'court_id',
        'player_id',
        'team_id',
        'won',
        'utr_singles_rating',
        'utr_doubles_rating',
        'usta_dynamic_rating',
    ];

    protected $casts = [
        'won' => 'boolean',
        'utr_singles_rating' => 'decimal:2',
        'utr_doubles_rating' => 'decimal:2',
        'usta_dynamic_rating' => 'decimal:2',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
