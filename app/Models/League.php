<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $fillable = ['name', 'usta_link', 'tennis_record_link', 'NTRP_rating', 'is_combo', 'active', 'daily_update', 'daily_update_time'];

    protected $casts = [
        'active' => 'boolean',
        'daily_update' => 'boolean',
        'utr_last_updated_at' => 'datetime',
        'teams_last_synced_at' => 'datetime',
        'last_daily_run_at' => 'datetime',
    ];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}
