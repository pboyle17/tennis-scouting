<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlayerController;

Route::apiResource('players', PlayerController::class);
