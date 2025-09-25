<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\TeamController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
Route::get('/players/create', [PlayerController::class, 'create'])->name('players.create');
Route::post('/players', [PlayerController::class, 'store'])->name('players.store');
Route::get('/players/{player}/edit', [PlayerController::class, 'edit'])->name('players.edit');
Route::put('/players/{player}', [PlayerController::class, 'update'])->name('players.update');
Route::post('/players/update-utr', [PlayerController::class, 'updateUtrRatings'])->name('players.updateUtr');
Route::post('/players/{player}/update-utr', [PlayerController::class, 'updateUtr'])
    ->name('players.updateUtrSingle');

Route::resource('teams', TeamController::class);
Route::resource('configurations', ConfigurationController::class);
Route::resource('leagues', LeagueController::class);
