<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrackController;
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

// Public Templates (for mobile & web without auth)
Route::get('/public/templates', [TemplateController::class, 'index']);
Route::get('/public/events/{slug}', [EventController::class, 'publicShow']);

// Public Event Types (for mobile to get available event types and their fields)
Route::get('/public/event-types', [\App\Http\Controllers\EventTypeController::class, 'index']);
Route::get('/public/event-types/{type}', [\App\Http\Controllers\EventTypeController::class, 'show']);

// Public Tracks (search + browse)
Route::get('/public/tracks', [TrackController::class, 'publicIndex']);

Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/user', [ProfileController::class, 'me']);
    Route::post('/user', [ProfileController::class, 'update']); // Update profile with image
    Route::get('/me/templates', [ProfileController::class, 'myTemplates']);
    Route::get('/me/posts', [PostController::class, 'myPosts']); // New endpoint for user's posts

    // Templates
    Route::get('/templates', [TemplateController::class, 'index']);

    // Events
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::patch('/events/{id}', [EventController::class, 'update']);
    Route::get('/events/{slug}/stats', [EventController::class, 'stats']);

    // Tracks
    Route::post('/tracks/upload', [TrackController::class, 'upload']);

    // Guests & Logistics
    Route::post('/events/{id}/import', [EventController::class, 'importGuests']);
    Route::post('/events/{id}/send-invites', [EventController::class, 'sendInvites']);

    // Media (Souvenir Module)
    Route::get('/events/{id}/gallery', [EventController::class, 'gallery']);
    Route::post('/events/{id}/upload', [EventController::class, 'uploadMedia']);

    // Credits
    Route::post('/credits/purchase', [\App\Http\Controllers\CreditController::class, 'purchase']);
    Route::get('/credits/balance', [\App\Http\Controllers\CreditController::class, 'balance']);

    // Social: Posts, Likes, Bookmarks, Comments
    Route::get('/feed', [PostController::class, 'feed']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::post('/posts', [PostController::class, 'store']); // agency only
    Route::post('/posts/{id}/like', [PostController::class, 'like']);
    Route::delete('/posts/{id}/like', [PostController::class, 'unlike']);
    Route::post('/posts/{id}/bookmark', [PostController::class, 'bookmark']);
    Route::delete('/posts/{id}/bookmark', [PostController::class, 'unbookmark']);
    Route::get('/posts/{id}/comments', [PostController::class, 'comments']);
    Route::post('/posts/{id}/comments', [PostController::class, 'addComment']);
    Route::delete('/comments/{commentId}', [PostController::class, 'deleteComment']);
    Route::get('/me/bookmarks', [PostController::class, 'myBookmarks']);
    Route::get('/me/likes', [PostController::class, 'myLikes']);
    Route::get('/agencies/{agencyId}/posts', [PostController::class, 'agencyPosts']);
});

// Public Guest Routes
Route::patch('/rsvp/{token}', [GuestController::class, 'rsvp']);
