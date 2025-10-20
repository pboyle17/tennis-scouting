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
Route::post('/players/fetch-missing-utr-ids', [PlayerController::class, 'fetchMissingUtrIds'])->name('players.fetchMissingUtrIds');
Route::get('/players/utr-search-progress', [PlayerController::class, 'getUtrSearchProgress'])->name('players.utrSearchProgress');
Route::get('/players/utr-update-progress', [PlayerController::class, 'getUtrUpdateProgress'])->name('players.utrUpdateProgress');
Route::post('/players/{player}/update-utr', [PlayerController::class, 'updateUtr'])
    ->name('players.updateUtrSingle');

Route::resource('teams', TeamController::class);
Route::post('/teams/{team}/add-player', [TeamController::class, 'addPlayer'])->name('teams.addPlayer');
Route::delete('/teams/{team}/remove-player/{player}', [TeamController::class, 'removePlayer'])->name('teams.removePlayer');
Route::post('/teams/{team}/update-utr', [TeamController::class, 'updateUtr'])->name('teams.updateUtr');
Route::post('/teams/create-from-usta', [TeamController::class, 'createFromUstaLink'])->name('teams.createFromUstaLink');
Route::get('/teams/usta-creation-progress', [TeamController::class, 'getUstaCreationProgress'])->name('teams.ustaCreationProgress');
Route::post('/teams/create-from-tennis-record', [TeamController::class, 'createFromTennisRecordLink'])->name('teams.createFromTennisRecord');
Route::get('/teams/tennis-record-creation-progress', [TeamController::class, 'getTennisRecordCreationProgress'])->name('teams.tennisRecordCreationProgress');
Route::resource('configurations', ConfigurationController::class);
Route::resource('leagues', LeagueController::class);
