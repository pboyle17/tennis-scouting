<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
      'name', 'utr_id', 'utr_singles_rating', 'utr_doubles_rating', 'utr_singles_reliable', 'utr_doubles_reliable',
      'first_name', 'last_name', 'USTA_rating', 'usta_rating_type', 'USTA_dynamic_rating', 'tennis_record_link', 'tennis_record_last_sync',
      'tennis_number_singles_rating', 'tennis_number_doubles_rating', 'tennis_number_link',
      'usta_id', 'wtn_id',
      'utr_singles_updated_at', 'utr_doubles_updated_at', 'usta_rating_updated_at'
    ];

    protected $casts = [
        'tennis_record_last_sync' => 'datetime',
        'utr_singles_updated_at' => 'datetime',
        'utr_doubles_updated_at' => 'datetime',
        'usta_rating_updated_at' => 'datetime',
    ];

    /**
     * The player's public USTA profile page, from their USTA member id.
     */
    public function ustaProfileUrl(): ?string
    {
        return $this->usta_id
            ? 'https://www.usta.com/en/home/play/player-search/profile.html#?uaid=' . urlencode($this->usta_id)
            : null;
    }

    public function teams()
    {
      return $this->belongsToMany(Team::class);
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_player')
                    ->withTimestamps();
    }

    public function courtPlayers()
    {
        return $this->hasMany(CourtPlayer::class);
    }

    public function rackets()
    {
        return $this->hasMany(Racket::class);
    }
}
