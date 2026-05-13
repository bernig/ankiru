<?php

use App\Livewire\CsvEditor;
use App\Models\User;
use App\Services\RussianTextToSpeechService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Audio;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create(['openai_api_key' => 'sk-test-key-for-automated-tests']);

    $this->actingAs($authenticatedUser);
});

// ── Russian TTS (Text-to-Speech) ────────────────────────────────────────────
test('does not call the tts api when the russian column is empty', function () {
    Storage::fake('local');
    Audio::fake()->preventStrayAudio();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', '']])
        ->set('hasCsvLoaded', true)
        ->call('generateTtsAudio', 0);

    Audio::assertNothingGenerated();
});

test('generates tts audio from the russian phrase and dispatches a playback event', function () {
    Storage::fake('local');
    Audio::fake([base64_encode('fake-mp3-binary')]);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я раб<b>о</b>таю.']])
        ->set('hasCsvLoaded', true)
        ->call('generateTtsAudio', 0)
        ->assertDispatched('tts-audio-ready')
        ->assertSet('ttsGeneratingRowIndex', -1)
        ->assertSet('ttsError', '');

    Audio::assertGenerated(fn ($prompt) => $prompt->contains('Я работаю.'));
});

test('reuses the cached audio file without calling the tts api a second time', function () {
    Storage::fake('local');
    $generateCallCount = 0;
    Audio::fake(function () use (&$generateCallCount) {
        $generateCallCount++;

        return base64_encode('fake-mp3-binary');
    });

    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я раб<b>о</b>таю.']])
        ->set('hasCsvLoaded', true);

    // First call: audio is generated and written to (fake) storage.
    $component->call('generateTtsAudio', 0);
    expect($generateCallCount)->toBe(1);

    // Second call: the cached file is found, so no additional API call is made.
    $component->call('generateTtsAudio', 0);
    expect($generateCallCount)->toBe(1);
});

test('sets a tts error when audio generation throws an exception', function () {
    Storage::fake('local');
    Audio::fake(function () {
        throw new RuntimeException('TTS provider error.');
    });

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('generateTtsAudio', 0)
        ->assertSet('ttsError', __('csv_editor.error_audio_generation_failed', ['message' => 'TTS provider error.']))
        ->assertSet('ttsGeneratingRowIndex', -1);
});

test('tts audio route serves a cached mp3 file', function () {
    Storage::fake('local');
    $filenameHash = hash('sha256', 'Я работаю.');
    Storage::disk('local')->put("tts/{$filenameHash}.mp3", 'fake-mp3-binary');

    $this->get(route('tts.serve', $filenameHash))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'audio/mpeg');
});

test('tts audio route returns 404 when the cached file does not exist', function () {
    Storage::fake('local');
    $validFormatKey = str_repeat('a', 64); // 64-char hex string

    $this->get(route('tts.serve', $validFormatKey))
        ->assertNotFound();
});

// ── RussianTextToSpeechService Unit-level behaviour ─────────────────────────
test('tts service builds a deterministic sha256 filename hash from normalized russian text', function () {
    $service = new RussianTextToSpeechService;
    $rawText = 'Я раб<b>о</b>таю.';
    $expectedHash = hash('sha256', $service->normalizeForSpeech($rawText));

    expect($service->buildFilenameHash($rawText))->toBe($expectedHash);
});

test('tts service strips bold tags and trims whitespace when normalizing for speech', function () {
    $service = new RussianTextToSpeechService;

    expect($service->normalizeForSpeech('  Я раб<b>о</b>таю.  '))->toBe('Я работаю.');
});

test('tts service uses the same filename hash for phrases with different stress positions', function () {
    $service = new RussianTextToSpeechService;

    $phraseWithStressOnO = 'раб<b>о</b>таю';
    $phraseWithStressOnA = 'работ<b>а</b>ю';

    expect($service->buildFilenameHash($phraseWithStressOnO))
        ->toBe($service->buildFilenameHash($phraseWithStressOnA));
});

test('deletes cached tts audio file from storage', function () {
    Storage::fake('local');

    $rawRussianText = 'Я раб<b>о</b>таю.';
    $filenameHash = hash('sha256', 'Я работаю.');
    Storage::disk('local')->put("tts/{$filenameHash}.mp3", 'fake-mp3-binary');

    expect(Storage::disk('local')->exists("tts/{$filenameHash}.mp3"))->toBeTrue();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', $rawRussianText]])
        ->set('hasCsvLoaded', true)
        ->call('deleteTtsAudio', 0);

    expect(Storage::disk('local')->exists("tts/{$filenameHash}.mp3"))->toBeFalse();
});

test('deleting tts audio for a row with no cached file does nothing gracefully', function () {
    Storage::fake('local');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('deleteTtsAudio', 0);

    expect(Storage::disk('local')->allFiles('tts'))->toBeEmpty();
});

test('tts audio exists for row returns true when cached file is present', function () {
    Storage::fake('local');

    $rawRussianText = 'Я раб<b>о</b>таю.';
    $filenameHash = hash('sha256', 'Я работаю.');
    Storage::disk('local')->put("tts/{$filenameHash}.mp3", 'fake-mp3-binary');

    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', $rawRussianText]])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->ttsAudioExistsForRow(0))->toBeTrue();
});

test('tts audio exists for row returns false when no cached file exists', function () {
    Storage::fake('local');

    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->ttsAudioExistsForRow(0))->toBeFalse();
});

test('tts service delete audio removes the cached file and returns true', function () {
    Storage::fake('local');

    $service = new RussianTextToSpeechService;
    $rawText = 'Я раб<b>о</b>таю.';
    $hash = $service->buildFilenameHash($rawText);
    Storage::disk('local')->put("tts/{$hash}.mp3", 'fake-mp3-binary');

    expect($service->deleteAudio($rawText))->toBeTrue();
    expect(Storage::disk('local')->exists("tts/{$hash}.mp3"))->toBeFalse();
});

test('tts service delete audio returns false when no file exists', function () {
    Storage::fake('local');

    $service = new RussianTextToSpeechService;

    expect($service->deleteAudio('Я работаю.'))->toBeFalse();
});

// ── RussianTextToSpeechService::audioFilesExistBatch ─────────────────────────
test('tts service batch check returns empty map for empty input', function () {
    Storage::fake('local');

    $service = new RussianTextToSpeechService;

    expect($service->audioFilesExistBatch([]))->toBe([]);
});

test('tts service batch check returns correct existence flags in a single scan', function () {
    Storage::fake('local');

    $service = new RussianTextToSpeechService;

    $presentPhrase = 'Я раб<b>о</b>таю.';
    $absentPhrase = 'Я работаю дома.';
    $filenameHash = $service->buildFilenameHash($presentPhrase);
    Storage::disk('local')->put("tts/{$filenameHash}.mp3", 'fake-mp3');

    $result = $service->audioFilesExistBatch([$presentPhrase, $absentPhrase]);

    expect($result[$presentPhrase])->toBeTrue();
    expect($result[$absentPhrase])->toBeFalse();
});

// ── audioExistenceByRowIndex computed property ────────────────────────────────
test('audio existence by row index returns a map matching per-row audio state', function () {
    Storage::fake('local');

    $presentPhrase = 'Я раб<b>о</b>таю.';
    $absentPhrase = 'Я работаю дома.';
    $service = new RussianTextToSpeechService;
    $hash = $service->buildFilenameHash($presentPhrase);
    Storage::disk('local')->put("tts/{$hash}.mp3", 'fake-mp3');

    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [
            ['Je travaille.', $presentPhrase],  // has audio
            ['Bonjour.', $absentPhrase],         // no audio
            ['Test.', ''],                       // empty Russian text
        ])
        ->set('hasCsvLoaded', true);

    $map = $component->instance()->audioExistenceByRowIndex;

    expect($map[0])->toBeTrue();
    expect($map[1])->toBeFalse();
    expect($map[2])->toBeFalse();
});

// ── TTS audio player modal ────────────────────────────────────────────────────
test('openTtsModal sets ttsModalRowIndex and dispatches open-tts-modal event without url when no audio exists', function () {
    Storage::fake('local');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('openTtsModal', 0)
        ->assertSet('ttsModalRowIndex', 0)
        ->assertDispatched('open-tts-modal', audioUrl: null);
});

test('openTtsModal dispatches open-tts-modal event with audio url when cached file exists', function () {
    Storage::fake('local');

    $rawText = 'Я раб<b>о</b>таю.';
    $filenameHash = hash('sha256', 'Я работаю.');
    Storage::disk('local')->put("tts/{$filenameHash}.mp3", 'fake-mp3-binary');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', $rawText]])
        ->set('hasCsvLoaded', true)
        ->call('openTtsModal', 0)
        ->assertSet('ttsModalRowIndex', 0)
        ->assertDispatched('open-tts-modal', fn ($name, $params) => str_contains($params['audioUrl'], route('tts.serve', $filenameHash)));
});

test('refreshTtsAudio deletes the existing file and regenerates fresh audio', function () {
    Storage::fake('local');
    Audio::fake([base64_encode('fresh-mp3-binary')]);

    $rawText = 'Я раб<b>о</b>таю.';
    $filenameHash = hash('sha256', 'Я работаю.'); // normalized: tags stripped
    Storage::disk('local')->put("tts/{$filenameHash}.mp3", 'old-mp3-binary');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', $rawText]])
        ->set('hasCsvLoaded', true)
        ->call('refreshTtsAudio', 0)
        ->assertDispatched('tts-audio-ready')
        ->assertSet('ttsError', '');

    expect(Storage::disk('local')->get("tts/{$filenameHash}.mp3"))->toBe('fresh-mp3-binary');
    Audio::assertGenerated(fn ($prompt) => $prompt->contains('Я работаю.'));
});

test('deleteRow resets ttsModalRowIndex to prevent stale references', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.'], ['Bonjour.', 'Привет.']])
        ->set('hasCsvLoaded', true)
        ->set('ttsModalRowIndex', 0)
        ->call('deleteRow', 0)
        ->assertSet('ttsModalRowIndex', -1);
});

// ── Rate Limiting ─────────────────────────────────────────────────────────────
test('tts generation is blocked and an error is set after exceeding the rate limit', function () {
    Storage::fake('local');
    Audio::fake()->preventStrayAudio();

    $rateLimitKey = 'tts-generation:'.auth()->id();
    RateLimiter::clear($rateLimitKey);
    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit($rateLimitKey, 60);
    }

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('generateTtsAudio', 0)
        ->assertSet('ttsError', __('csv_editor.error_rate_limit'));

    Audio::assertNothingGenerated();
    RateLimiter::clear($rateLimitKey);
});
