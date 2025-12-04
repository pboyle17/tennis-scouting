<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;

Route::get('/', function () {
    return redirect()->route('players.index');
});

Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
Route::get('/players/create', [PlayerController::class, 'create'])->name('players.create');
Route::post('/players', [PlayerController::class, 'store'])->name('players.store');
Route::get('/players/{player}/edit', [PlayerController::class, 'edit'])->name('players.edit');
Route::put('/players/{player}', [PlayerController::class, 'update'])->name('players.update');
Route::delete('/players/{player}', [PlayerController::class, 'destroy'])->name('players.destroy');
Route::post('/players/update-utr', [PlayerController::class, 'updateUtrRatings'])->name('players.updateUtr');
Route::post('/players/fetch-missing-utr-ids', [PlayerController::class, 'fetchMissingUtrIds'])->name('players.fetchMissingUtrIds');
Route::get('/players/utr-search-progress', [PlayerController::class, 'getUtrSearchProgress'])->name('players.utrSearchProgress');
Route::get('/players/utr-update-progress', [PlayerController::class, 'getUtrUpdateProgress'])->name('players.utrUpdateProgress');
Route::post('/players/{player}/update-utr', [PlayerController::class, 'updateUtr'])->name('players.updateUtrSingle');
Route::post('/players/{player}/search-utr-id', [PlayerController::class, 'searchUtrId'])->name('players.searchUtrId');

Route::resource('teams', TeamController::class);
Route::post('/teams/{team}/add-player', [TeamController::class, 'addPlayer'])->name('teams.addPlayer');
Route::delete('/teams/{team}/remove-player/{player}', [TeamController::class, 'removePlayer'])->name('teams.removePlayer');
Route::post('/teams/{team}/update-utr', [TeamController::class, 'updateUtr'])->name('teams.updateUtr');
Route::post('/teams/{team}/find-missing-utr-ids', [TeamController::class, 'findMissingUtrIds'])->name('teams.findMissingUtrIds');
Route::post('/teams/{team}/players/{player}/set-utr-data', [TeamController::class, 'setPlayerUtrData'])->name('teams.setPlayerUtrData');
Route::post('/teams/create-from-usta', [TeamController::class, 'createFromUstaLink'])->name('teams.createFromUstaLink');
Route::get('/teams/usta-creation-progress', [TeamController::class, 'getUstaCreationProgress'])->name('teams.ustaCreationProgress');
Route::post('/teams/create-from-tennis-record', [TeamController::class, 'createFromTennisRecordLink'])->name('teams.createFromTennisRecord');
Route::get('/teams/tennis-record-creation-progress', [TeamController::class, 'getTennisRecordCreationProgress'])->name('teams.tennisRecordCreationProgress');
Route::post('/teams/{team}/sync-from-tennis-record', [TeamController::class, 'syncFromTennisRecord'])->name('teams.syncFromTennisRecord');
Route::get('/teams/tennis-record-sync-progress', [TeamController::class, 'getTennisRecordSyncProgress'])->name('teams.tennisRecordSyncProgress');
Route::resource('configurations', ConfigurationController::class);
Route::resource('leagues', LeagueController::class);
Route::post('/leagues/{league}/add-teams', [LeagueController::class, 'addTeams'])->name('leagues.addTeams');
Route::delete('/leagues/{league}/remove-team/{team}', [LeagueController::class, 'removeTeam'])->name('leagues.removeTeam');
Route::post('/leagues/{league}/update-utr', [LeagueController::class, 'updateUtr'])->name('leagues.updateUtr');
Route::post('/leagues/{league}/create-teams-from-league', [LeagueController::class, 'createTeamsFromLeague'])->name('leagues.createTeamsFromLeague');
Route::get('/leagues/league-creation-progress', [LeagueController::class, 'getLeagueCreationProgress'])->name('leagues.leagueCreationProgress');
Route::post('/leagues/{league}/teams/{team}/find-missing-utr-ids', [LeagueController::class, 'findMissingUtrIdsForTeam'])->name('leagues.findMissingUtrIdsForTeam');
Route::post('/leagues/{league}/players/{player}/set-utr-data', [LeagueController::class, 'setPlayerUtrData'])->name('leagues.setPlayerUtrData');
Route::post('/leagues/{league}/sync-all-teams', [LeagueController::class, 'syncAllTeamsFromTennisRecord'])->name('leagues.syncAllTeams');

Route::resource('tournaments', TournamentController::class);
Route::post('/tournaments/{tournament}/add-player', [TournamentController::class, 'addPlayer'])->name('tournaments.addPlayer');
Route::delete('/tournaments/{tournament}/remove-player/{player}', [TournamentController::class, 'removePlayer'])->name('tournaments.removePlayer');
Route::post('/tournaments/create-from-usta', [TournamentController::class, 'createFromUstaLink'])->name('tournaments.createFromUstaLink');
