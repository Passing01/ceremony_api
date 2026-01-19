<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Templates
    Route::get('/templates', [TemplateController::class, 'index']);

    // Events
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events/{slug}/stats', [EventController::class, 'stats']);
    
    // Guests & Logistics
    Route::post('/events/{id}/import', [EventController::class, 'importGuests']);
    Route::post('/events/{id}/send-invites', [EventController::class, 'sendInvites']);
    
    // Media (Souvenir Module)
    Route::get('/events/{id}/gallery', [EventController::class, 'gallery']);
    Route::post('/events/{id}/upload', [EventController::class, 'uploadMedia']);

    // Credits
    Route::post('/credits/purchase', [\App\Http\Controllers\CreditController::class, 'purchase']);
    Route::get('/credits/balance', [\App\Http\Controllers\CreditController::class, 'balance']);
});

// Public Guest Routes
Route::patch('/rsvp/{token}', [GuestController::class, 'rsvp']);
