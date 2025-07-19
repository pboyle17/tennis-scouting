<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'utr_id', 'first_name', 'last_name', 'USTA_rating', 'USTA_dynamic_rating'];
}
