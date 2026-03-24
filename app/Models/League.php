<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $fillable = ['name', 'usta_link', 'tennis_record_link', 'NTRP_rating', 'is_combo', 'active'];

    protected $casts = [
        'active' => 'boolean',
        'utr_last_updated_at' => 'datetime',
        'teams_last_synced_at' => 'datetime',
    ];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}
