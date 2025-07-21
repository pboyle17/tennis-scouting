<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PlayerController;

Route::apiResource('players', PlayerController::class);
