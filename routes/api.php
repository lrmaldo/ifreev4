<?php

use App\Http\Controllers\Api\CampanaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas para campañas
Route::get('/campanas/activas', [CampanaController::class, 'getCampanasActivas']);

// Rutas para Telegram
Route::post('/telegram/webhook', [\App\Http\Controllers\TelegramController::class, 'webhook'])
    ->name('api.telegram.webhook');
Route::post('/telegram/enviar-notificacion', [\App\Http\Controllers\TelegramController::class, 'enviarNotificacion'])
    ->middleware(['auth:sanctum'])
    ->name('api.telegram.notificacion');
