<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlayerController;

Route::apiResource('players', PlayerController::class);

Route::get('/test', function () {
    return ['message' => 'API is working'];
});
