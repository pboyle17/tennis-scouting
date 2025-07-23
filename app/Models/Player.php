<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
      'name', 'utr_id', 'utr_singles_rating', 'utr_doubles_rating', 'first_name', 'last_name', 'USTA_rating',
      'USTA_dynamic_rating'
    ];

    public function teams()
    {
      return $this->belongsToMany(Team::class);
    }
}
