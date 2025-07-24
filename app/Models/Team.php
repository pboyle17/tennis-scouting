<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['name', 'usta_link', 'tennis_record_link'];

    public function players()
    {
        return $this->belongsToMany(Player::class);
    }

    public function league()
    {
        return $this->belongsTo(League::class);
    }
}
