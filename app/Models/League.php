<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $fillable = ['name'];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}
