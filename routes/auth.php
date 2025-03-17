<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthentificationController;

// Routes publiques
Route::post('login', [AuthentificationController::class, 'login']);
Route::post('register', [AuthentificationController::class, 'register']);
Route::post('refresh', [AuthentificationController::class, 'refresh']);

// Routes protégées
Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthentificationController::class, 'logout']);
    Route::get('me', [AuthentificationController::class, 'me']);
});