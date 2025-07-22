<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ConfigurationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
Route::get('/players/create', [PlayerController::class, 'create'])->name('players.create');
Route::get('/players/{player}/edit', [PlayerController::class, 'edit'])->name('players.edit');
Route::post('/players/update-utr', [PlayerController::class, 'updateUtrRatings'])->name('players.updateUtr');


Route::resource('configurations', ConfigurationController::class);

