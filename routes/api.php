<?php

use App\Http\Controllers\SimulationController;
use Illuminate\Support\Facades\Route;

Route::post('/setup',          [SimulationController::class, 'setup']);
Route::get('/state',           [SimulationController::class, 'state']);
Route::post('/simulate/week',  [SimulationController::class, 'simulateWeek']);
Route::post('/simulate/all',   [SimulationController::class, 'simulateAll']);
Route::post('/reset',          [SimulationController::class, 'reset']);
Route::post('/bulk-simulate',  [SimulationController::class, 'bulkSimulate']);
Route::patch('/matches/{id}',  [SimulationController::class, 'updateMatch']);
