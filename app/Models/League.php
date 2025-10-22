<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $fillable = ['name', 'usta_link', 'tennis_record_link'];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}
