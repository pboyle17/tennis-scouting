<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tournament extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'usta_link',
        'start_date',
        'end_date',
        'location',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the players for the tournament.
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'tournament_player')
                    ->withTimestamps();
    }
}
