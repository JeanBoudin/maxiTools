<?php

use App\Http\Controllers\CameraFrameController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\ObsController;
use App\Http\Controllers\OverlayController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\SpotifyAuthController;
use App\Http\Controllers\SpotifyController;
use App\Http\Controllers\TwitchAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/twitch/redirect', [TwitchAuthController::class, 'redirect']);
Route::get('/auth/twitch/callback', [TwitchAuthController::class, 'callback']);
Route::get('/auth/spotify/redirect', [SpotifyAuthController::class, 'redirect']);
Route::get('/auth/spotify/callback', [SpotifyAuthController::class, 'callback']);

Route::get('/api/overlay/stats', [OverlayController::class, 'stats']);
Route::get('/api/overlay/promo', [PromoController::class, 'overlay']);
Route::get('/api/overlay/camera', [CameraFrameController::class, 'overlay']);
Route::get('/api/overlay/now-playing', [SpotifyController::class, 'overlayNowPlaying']);
Route::get('/api/obs/switch', [ObsController::class, 'publicSwitch']);

Route::middleware('auth')->group(function () {
    Route::get('/api/me', [MeController::class, 'show']);
    Route::get('/api/promo/settings', [PromoController::class, 'show']);
    Route::post('/api/promo/settings', [PromoController::class, 'store']);
    Route::get('/api/camera/settings', [CameraFrameController::class, 'show']);
    Route::post('/api/camera/settings', [CameraFrameController::class, 'store']);
    Route::get('/api/spotify/status', [SpotifyController::class, 'status']);
    Route::get('/api/obs/settings', [ObsController::class, 'show']);
    Route::post('/api/obs/settings', [ObsController::class, 'store']);
    Route::post('/api/obs/scenes', [ObsController::class, 'scenes']);
    Route::post('/api/obs/switch', [ObsController::class, 'switch']);
    Route::post('/api/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return response()->noContent();
    });
});
