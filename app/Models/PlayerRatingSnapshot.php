<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerRatingSnapshot extends Model
{
    protected $fillable = [
        'player_id',
        'snapshot_date',
        'utr_singles_rating',
        'utr_doubles_rating',
        'usta_dynamic_rating',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
