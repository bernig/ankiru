<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::get('locale/{locale}', [LocaleController::class, 'update'])
    ->name('locale.update')
    ->whereIn('locale', ['en', 'fr']);

Route::middleware('auth')->group(function (): void {
    Route::get('/', function () {
        return view('csv-editor');
    })->name('csv-editor');

    Route::get('profile', function () {
        return view('profile');
    })->name('profile');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

/**
 * Serve a TTS audio file by its SHA-256 filename hash.
 * The where constraint enforces the exact hex format, preventing path traversal.
 */
Route::get('tts-audio/{filenameHash}', function (string $filenameHash) {
    $storagePath = "tts/{$filenameHash}.mp3";

    if (! Storage::disk('local')->exists($storagePath)) {
        abort(404);
    }

    return response(Storage::disk('local')->get($storagePath), 200, [
        'Content-Type' => 'audio/mpeg',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->middleware('auth')->name('tts.serve')->where('filenameHash', '[a-f0-9]{64}');
