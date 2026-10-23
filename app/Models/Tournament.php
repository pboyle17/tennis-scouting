<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'utr_link',
        'utr_last_synced_at',
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
        'utr_last_synced_at' => 'datetime',
    ];

    /**
     * Get the players for the tournament.
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'tournament_player')
                    ->using(TournamentPlayer::class)
                    ->withPivot('flights')
                    ->withTimestamps();
    }

    public function draws(): HasMany
    {
        return $this->hasMany(TournamentDraw::class);
    }

    /**
     * The UTR event id from the tournament's UTR link
     * (e.g. https://app.utrsports.net/tournaments/3560084 or /events/3560084).
     */
    public function utrEventId(): ?int
    {
        return static::parseUtrEventId($this->utr_link);
    }

    public static function parseUtrEventId(?string $link): ?int
    {
        if ($link && preg_match('#utrsports\.net/(?:tournaments|events)/(\d+)#', $link, $m)) {
            return (int) $m[1];
        }
        return null;
    }
}
