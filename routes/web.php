<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationHandlerController;
use App\Http\Controllers\Auth\EmailVerificationNoticeController;
use App\Http\Controllers\Auth\EmailVerificationResendController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('contact', fn () => view('contact'))->name('contact');
Route::get('about', fn () => view('about'))->name('about');
Route::get('faq', fn () => view('faq'))->name('faq');
Route::get('legal/mentions-legales', fn () => view('legal.mentions-legales'))->name('legal.mentions');
Route::get('legal/confidentialite', fn () => view('legal.confidentialite'))->name('legal.privacy');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email')->middleware('throttle:password-reset-request');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::get('locale/{locale}', [LocaleController::class, 'update'])
    ->name('locale.update')
    ->whereIn('locale', config('app.supported_locales'));

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('email/verify', EmailVerificationNoticeController::class)->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', EmailVerificationHandlerController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', EmailVerificationResendController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::get('/', function () {
    if (auth()->check() && auth()->user()->hasVerifiedEmail()) {
        return view('csv-editor');
    }

    return view('welcome');
})->name('csv-editor');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('profile', function () {
        return view('profile');
    })->name('profile');
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
