<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
      'name', 'utr_id', 'utr_singles_rating', 'utr_doubles_rating', 'utr_singles_reliable', 'utr_doubles_reliable',
      'first_name', 'last_name', 'USTA_rating', 'USTA_dynamic_rating'
    ];

    public function teams()
    {
      return $this->belongsToMany(Team::class);
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_player')
                    ->withTimestamps();
    }
}
