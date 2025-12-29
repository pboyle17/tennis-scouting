<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtSet extends Model
{
    protected $fillable = [
        'court_id',
        'set_number',
        'home_score',
        'away_score',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
