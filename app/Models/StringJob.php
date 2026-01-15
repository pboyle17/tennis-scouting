<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StringJob extends Model
{
    protected $fillable = [
        'racket_id',
        'mains_brand',
        'mains_model',
        'mains_gauge',
        'mains_tension',
        'crosses_brand',
        'crosses_model',
        'crosses_gauge',
        'crosses_tension',
        'stringing_date',
        'time_played',
        'notes',
        'is_current',
    ];

    protected $casts = [
        'stringing_date' => 'date',
        'mains_tension' => 'decimal:1',
        'crosses_tension' => 'decimal:1',
        'time_played' => 'decimal:1',
        'is_current' => 'boolean',
    ];

    public function racket()
    {
        return $this->belongsTo(Racket::class);
    }
}
