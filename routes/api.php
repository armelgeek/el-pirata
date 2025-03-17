<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthentificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques
Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthentificationController::class, 'login']);
    Route::post('auth/register', [AuthentificationController::class, 'register']);
    Route::post('auth/refresh', [AuthentificationController::class, 'refresh']);

    // Routes protégées
    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [AuthentificationController::class, 'logout']);
        Route::get('auth/me', [AuthentificationController::class, 'me']);
    });
});