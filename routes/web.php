<?php

use App\Livewire\CsvEditor;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::livewire('/', CsvEditor::class)->name('csv-editor');

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
})->name('tts.serve')->where('filenameHash', '[a-f0-9]{64}');
