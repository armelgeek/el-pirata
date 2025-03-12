<?php

use Illuminate\Http\Request;
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

// Routes d'authentification
Route::post('/v1/login', [AuthentificationController::class, 'login']);
Route::post('/v1/register', [AuthentificationController::class, 'register']);
Route::post('/v1/refresh', [AuthentificationController::class, 'refresh']);

// Routes protégées
Route::middleware('auth:api')->group(function () {
    Route::get('/v1/logout', [AuthentificationController::class, 'logout']);
    Route::get('/v1/me', [AuthentificationController::class, 'me']);
});
