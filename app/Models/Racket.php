<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Racket extends Model
{
    protected $fillable = [
        'player_id',
        'name',
        'brand',
        'model',
        'weight',
        'swing_weight',
        'string_pattern',
        'grip_size',
        'notes',
    ];

    protected $casts = [
        'weight' => 'decimal:1',
        'swing_weight' => 'integer',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function stringJobs()
    {
        return $this->hasMany(StringJob::class)->orderByDesc('stringing_date');
    }

    public function currentStringJob()
    {
        return $this->hasOne(StringJob::class)->where('is_current', true);
    }
}
