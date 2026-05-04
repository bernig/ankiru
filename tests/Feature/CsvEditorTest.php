<?php

use App\Livewire\CsvEditor;
use App\Services\RussianTextToSpeechService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function sampleRows(): array
{
    return [
        ['Je travaille depuis chez moi.', 'Я раб<b>о</b>таю из д<b>о</b>ма.'],
        ['Je suis développeur web.', 'Я веб-разраб<b>о</b>тчик.'],
    ];
}
function tempFilePath(): string
{
    return storage_path('app/csv_editor_temp.json');
}
beforeEach(fn () => @unlink(tempFilePath()));
afterEach(fn () => @unlink(tempFilePath()));
// ── Rendering ──────────────────────────────────────────────────────────────
test('component renders successfully', function () {
    Livewire::test(CsvEditor::class)
        ->assertStatus(200);
});
test('shows the upload panel when no csv is loaded', function () {
    Livewire::test(CsvEditor::class)
        ->assertSet('hasCsvLoaded', false)
        ->assertSee('Upload a CSV file');
});
test('hides the upload panel and shows rows when a csv is loaded', function () {
    // Cell content is rendered via Alpine x-html (client-side), so assertSee is not
    // appropriate here. We verify state instead, and confirm the upload panel is gone.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertDontSee('Upload a CSV file')
        ->assertSet('hasCsvLoaded', true)
        ->assertCount('csvRows', 2);
});
// ── CSV Upload ──────────────────────────────────────────────────────────────
test('parses an uploaded csv file into component state', function () {
    $csvContent = implode("\n", [
        '"Je travaille depuis chez moi.","Я раб<b>о</b>таю из д<b>о</b>ма."',
        '"Je suis développeur web.","Я веб-разраб<b>о</b>тчик."',
    ]);
    $uploadedFile = UploadedFile::fake()->createWithContent('translations.csv', $csvContent);
    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $uploadedFile)
        ->assertSet('hasCsvLoaded', true)
        ->assertSet('originalFileName', 'translations.csv')
        ->assertCount('csvRows', 2)
        ->assertSet('csvRows.0.0', 'Je travaille depuis chez moi.')
        ->assertSet('csvRows.0.1', 'Я раб<b>о</b>таю из д<b>о</b>ма.')
        ->assertSet('csvRows.1.0', 'Je suis développeur web.');
});
test('rejects an empty csv file with a validation error', function () {
    $uploadedFile = UploadedFile::fake()->createWithContent('empty.csv', '');
    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $uploadedFile)
        ->assertSet('hasCsvLoaded', false)
        ->assertSet('validationError', 'The CSV file appears to be empty or malformed.');
});
// ── Cell Editing ────────────────────────────────────────────────────────────
test('updates a cell value in the left column', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 0, 'Je reste à la maison.')
        ->assertSet('csvRows.0.0', 'Je reste à la maison.');
});
test('updates a cell value in the right column', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 1, 'Я ост<b>а</b>юсь д<b>о</b>ма.')
        ->assertSet('csvRows.0.1', 'Я ост<b>а</b>юсь д<b>о</b>ма.');
});
test('row needs stress correction returns false after updating column 1 with fully accented text', function () {
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 1, 'Я ост<b>а</b>юсь д<b>о</b>ма.');

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
});
test('row needs stress correction is unaffected when only the left column is edited', function () {
    // Start with Russian text that has no accent on a 3-vowel word.
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 0, 'Je rentre chez moi.');

    // Russian column was not touched — still needs an accent.
    expect($component->instance()->rowNeedsStressCorrection(0))->toBeTrue();
});
// ── Row Management ──────────────────────────────────────────────────────────
test('adds an empty row at the end', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('addRow')
        ->assertCount('csvRows', 3)
        ->assertSet('csvRows.2.0', '')
        ->assertSet('csvRows.2.1', '');
});
test('navigates to the last page when an added row creates a second page', function () {
    $fullPageRows = array_fill(0, 50, ['French text.', 'Russian text.']);
    Livewire::test(CsvEditor::class)
        ->set('csvRows', $fullPageRows)
        ->set('hasCsvLoaded', true)
        ->call('addRow')
        ->assertSet('paginators.page', 2);
});
test('deletes a row by its index and re-indexes remaining rows', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('deleteRow', 0)
        ->assertCount('csvRows', 1)
        ->assertSet('csvRows.0.0', 'Je suis développeur web.');
});
test('clamps the current page after deleting the only row on the last page', function () {
    $rows = array_fill(0, 51, ['French text.', 'Russian text.']);
    Livewire::test(CsvEditor::class)
        ->set('csvRows', $rows)
        ->set('hasCsvLoaded', true)
        ->call('setPage', 2)
        ->call('deleteRow', 50)
        ->assertSet('paginators.page', 1);
});
test('row needs stress correction resets correctly after a row is deleted and indices shift', function () {
    // Two rows: first has no accent (needs correction), second has accent (does not).
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [
            ['Je travaille.', 'Я работаю.'],
            ['Je reste.', 'Я ост<b>а</b>юсь.'],
        ])
        ->set('hasCsvLoaded', true)
        ->call('deleteRow', 0);

    // After deleting row 0, the former row 1 becomes row 0 — it has an accent.
    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
});
// ── Russian Accent Mode ─────────────────────────────────────────────────────
test('accent mode is active by default and the edit pencil button is visible for rows with russian text', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertSee('Edit Russian text');
});
test('accent mode pencil button is not rendered when the russian column is empty', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', '']])
        ->set('hasCsvLoaded', true)
        ->assertDontSee('Edit Russian text');
});
test('french pencil button is always rendered for editing the left column', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertSee('Edit French text');
});
test('retranslate button is rendered when both french and russian text exist', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertSee('Regenerate translation with ChatGPT');
});
test('retranslate button is not rendered when russian column is empty', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', '']])
        ->set('hasCsvLoaded', true)
        ->assertDontSee('Regenerate translation with ChatGPT');
});
// ── Accent Placement ────────────────────────────────────────────────────────
test('places accent on the correct russian vowel', function () {
    // Plain text: "работаю" → р(0) а(1) б(2) о(3) т(4) а(5) ю(6)
    // Clicking position 3 ('о') should produce 'раб<b>о</b>таю'.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'работаю']])
        ->set('hasCsvLoaded', true)
        ->call('placeAccentOnVowel', 0, 1, 3)
        ->assertSet('csvRows.0.1', 'раб<b>о</b>таю');
});
test('moves the accent from one vowel to another within the same word', function () {
    // Start with accent on 'о' (position 3); move it to 'а' (position 5).
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'раб<b>о</b>таю']])
        ->set('hasCsvLoaded', true)
        ->call('placeAccentOnVowel', 0, 1, 5)
        ->assertSet('csvRows.0.1', 'работ<b>а</b>ю');
});
test('does not alter text when a non-vowel character position is clicked', function () {
    // Position 2 = 'б' (a consonant) — text must remain unchanged.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'работаю']])
        ->set('hasCsvLoaded', true)
        ->call('placeAccentOnVowel', 0, 1, 2)
        ->assertSet('csvRows.0.1', 'работаю');
});
test('preserves accent marks on other words when accenting a vowel in one word', function () {
    // Plain text: "работаю из дома"
    // р(0)а(1)б(2)о(3)т(4)а(5)ю(6) (7)и(8)з(9) (10)д(11)о(12)м(13)а(14)
    // Accent 'о' at position 3 in "работаю"; "дома" keeps its existing accent.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'работаю из д<b>о</b>ма']])
        ->set('hasCsvLoaded', true)
        ->call('placeAccentOnVowel', 0, 1, 3)
        ->assertSet('csvRows.0.1', 'раб<b>о</b>таю из д<b>о</b>ма');
});
// ── CSV Download ───────────────────────────────────────────────────────────
test('downloading the csv triggers a file download', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'sample.csv')
        ->call('downloadCsv')
        ->assertFileDownloaded();
});
// ── Reset Editor ───────────────────────────────────────────────────────────
test('resetting the editor clears state and returns to the upload panel', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'sample.csv')
        ->call('resetEditor')
        ->assertSet('hasCsvLoaded', false)
        ->assertSet('csvRows', [])
        ->assertSet('originalFileName', '')
        ->assertSee('Upload a CSV file');
});
test('resetting the editor deletes the temp file if it exists', function () {
    file_put_contents(tempFilePath(), json_encode([
        'csvRows' => sampleRows(),
        'originalFileName' => 'sample.csv',
        'hasCsvLoaded' => true,
        'savedAt' => now()->toIso8601String(),
    ]));
    Livewire::test(CsvEditor::class)
        ->call('resetEditor');
    expect(file_exists(tempFilePath()))->toBeFalse();
});
// ── Temp File Persistence ───────────────────────────────────────────────────
test('restores editor state from the temp file on mount', function () {
    file_put_contents(tempFilePath(), json_encode([
        'csvRows' => sampleRows(),
        'originalFileName' => 'restored.csv',
        'hasCsvLoaded' => true,
        'savedAt' => now()->toIso8601String(),
    ]));
    Livewire::test(CsvEditor::class)
        ->assertSet('hasCsvLoaded', true)
        ->assertSet('originalFileName', 'restored.csv')
        ->assertCount('csvRows', 2)
        ->assertSet('csvRows.0.0', 'Je travaille depuis chez moi.');
});
test('starts fresh when no temp file exists', function () {
    Livewire::test(CsvEditor::class)
        ->assertSet('hasCsvLoaded', false)
        ->assertSet('csvRows', []);
});
// ── ChatGPT Translation ─────────────────────────────────────────────────────
test('shows an error when the openai api key is not configured', function () {
    config(['services.openai.api_key' => '']);
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', 'OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
});
test('translates french text to russian and stores the result', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Http::fake([
        'api.openai.com/*' => Http::response([
            'output' => [
                ['content' => [['text' => 'Я раб<b>о</b>таю из д<b>о</b>ма.']]],
            ],
        ], 200),
    ]);
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('csvRows.0.1', 'Я раб<b>о</b>таю из д<b>о</b>ма.')
        ->assertSet('translationError', '')
        ->assertSet('translatingRowIndex', -1);
});
test('sets a translation error when the chatgpt api returns a failure status', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Http::fake([
        'api.openai.com/*' => Http::response([], 429),
    ]);
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', 'ChatGPT API returned an error: 429. Check your API key and quota.');
});
test('does nothing when the french column is empty', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Http::fake();
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', '');
    Http::assertNothingSent();
});
// ── Stress Correction ───────────────────────────────────────────────────────
test('shows an error when the openai api key is not configured for stress correction', function () {
    config(['services.openai.api_key' => '']);
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0)
        ->assertSet('translationError', 'OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
});
test('corrects stress marks and sends french context with the russian text', function () {
    config(['services.openai.api_key' => 'test-api-key']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'output' => [
                ['content' => [['text' => 'Я раб<b>о</b>таю из д<b>о</b>ма.']]],
            ],
        ], 200),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', 'Я работаю из дома.']])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0)
        ->assertSet('csvRows.0.1', 'Я раб<b>о</b>таю из д<b>о</b>ма.')
        ->assertSet('correctingStressRowIndex', -1);

    Http::assertSent(function ($request) {
        $requestData = $request->data();

        expect($requestData['instructions'])->toBe(CsvEditor::STRESS_CORRECTION_PROMPT);
        expect($requestData['input'])->toContain('French source');
        expect($requestData['input'])->toContain('Je travaille depuis chez moi.');
        expect($requestData['input'])->toContain('Russian text to review and correct stress marks in:');
        expect($requestData['input'])->toContain('Я работаю из дома.');

        return true;
    });
});
test('row needs stress correction returns false when chatgpt returns already correct text', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    $alreadyCorrectText = 'Я раб<b>о</b>таю из д<b>о</b>ма.';
    Http::fake([
        'api.openai.com/*' => Http::response([
            'output' => [
                ['content' => [['text' => 'Я раб<b>о</b>таю из д<b>о</b>ма.']]],
            ],
        ], 200),
    ]);
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', $alreadyCorrectText]])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
});
// ── Russian TTS (Text-to-Speech) ────────────────────────────────────────────
test('does not call the tts api when the russian column is empty', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Storage::fake('local');
    Http::fake();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', '']])
        ->set('hasCsvLoaded', true)
        ->call('generateTtsAudio', 0);

    Http::assertNothingSent();
});

test('generates tts audio from the russian phrase and dispatches a playback event', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Storage::fake('local');
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response('fake-mp3-binary', 200),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я раб<b>о</b>таю.']])
        ->set('hasCsvLoaded', true)
        ->call('generateTtsAudio', 0)
        ->assertDispatched('tts-audio-ready')
        ->assertSet('ttsGeneratingRowIndex', -1)
        ->assertSet('ttsError', '');

    // Verify the API received the normalized text (tags stripped).
    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/v1/audio/speech')
            && $request->data()['input'] === 'Я работаю.'
            && $request->data()['model'] === 'gpt-4o-mini-tts';
    });
});

test('reuses the cached audio file without calling the tts api a second time', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Storage::fake('local');
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response('fake-mp3-binary', 200),
    ]);

    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я раб<b>о</b>таю.']])
        ->set('hasCsvLoaded', true);

    // First call: audio is generated and written to (fake) storage.
    $component->call('generateTtsAudio', 0);
    Http::assertSentCount(1);

    // Second call: the cached file is found, so no additional HTTP request is made.
    $component->call('generateTtsAudio', 0);
    Http::assertSentCount(1);
});

test('sets a tts error when the openai api returns a failure status', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Storage::fake('local');
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response([], 500),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('generateTtsAudio', 0)
        ->assertSet('ttsError', 'Audio generation failed: OpenAI TTS API returned an error: 500. Check your API key and quota.')
        ->assertSet('ttsGeneratingRowIndex', -1);
});

test('shows a tts error when the openai api key is not configured', function () {
    config(['services.openai.api_key' => '']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('generateTtsAudio', 0)
        ->assertSet('ttsError', 'OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
});

test('tts audio route serves a cached mp3 file', function () {
    Storage::fake('local');
    $cacheKey = hash('sha256', 'Я работаю.');
    Storage::disk('local')->put("tts/{$cacheKey}.mp3", 'fake-mp3-binary');

    $this->get(route('tts.serve', $cacheKey))
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
test('tts service builds a deterministic sha256 cache key from raw russian text', function () {
    $service = new RussianTextToSpeechService;
    $rawText = 'Я раб<b>о</b>таю.';

    expect($service->hashRawString($rawText))->toBe(hash('sha256', $rawText));
});

test('tts service strips bold tags and trims whitespace when normalizing for speech', function () {
    $service = new RussianTextToSpeechService;

    expect($service->normalizeForSpeech('  Я раб<b>о</b>таю.  '))->toBe('Я работаю.');
});

test('tts service uses different cache keys for phrases with different stress positions', function () {
    $service = new RussianTextToSpeechService;

    $phraseWithStressOnO = 'раб<b>о</b>таю';
    $phraseWithStressOnA = 'работ<b>а</b>ю';

    expect($service->hashRawString($phraseWithStressOnO))
        ->not->toBe($service->hashRawString($phraseWithStressOnA));
});

test('deletes cached tts audio file from storage', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Storage::fake('local');

    $rawRussianText = 'Я раб<b>о</b>таю.';
    $cacheKey = hash('sha256', $rawRussianText);
    Storage::disk('local')->put("tts/{$cacheKey}.mp3", 'fake-mp3-binary');

    // Verify the file exists before deletion.
    expect(Storage::disk('local')->exists("tts/{$cacheKey}.mp3"))->toBeTrue();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', $rawRussianText]])
        ->set('hasCsvLoaded', true)
        ->call('deleteTtsAudio', 0);

    // Verify the file was deleted.
    expect(Storage::disk('local')->exists("tts/{$cacheKey}.mp3"))->toBeFalse();
});

test('deleting tts audio for a row with no cached file does nothing gracefully', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Storage::fake('local');

    // No file stored — deletion should not throw.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('deleteTtsAudio', 0);

    expect(Storage::disk('local')->allFiles('tts'))->toBeEmpty();
});

test('tts audio exists for row returns true when cached file is present', function () {
    Storage::fake('local');

    $rawRussianText = 'Я раб<b>о</b>таю.';
    $cacheKey = hash('sha256', $rawRussianText);
    Storage::disk('local')->put("tts/{$cacheKey}.mp3", 'fake-mp3-binary');

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
    $hash = $service->hashRawString($rawText);
    Storage::disk('local')->put("tts/{$hash}.mp3", 'fake-mp3-binary');

    expect($service->deleteAudio($rawText))->toBeTrue();
    expect(Storage::disk('local')->exists("tts/{$hash}.mp3"))->toBeFalse();
});

test('tts service delete audio returns false when no file exists', function () {
    Storage::fake('local');

    $service = new RussianTextToSpeechService;

    expect($service->deleteAudio('Я работаю.'))->toBeFalse();
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
    $cacheKey = hash('sha256', $rawText);
    Storage::disk('local')->put("tts/{$cacheKey}.mp3", 'fake-mp3-binary');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', $rawText]])
        ->set('hasCsvLoaded', true)
        ->call('openTtsModal', 0)
        ->assertSet('ttsModalRowIndex', 0)
        ->assertDispatched('open-tts-modal', fn ($name, $params) => str_contains($params['audioUrl'], route('tts.serve', $cacheKey)));
});

test('refreshTtsAudio deletes the existing file and regenerates fresh audio', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    Storage::fake('local');
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response('fresh-mp3-binary', 200),
    ]);

    $rawText = 'Я раб<b>о</b>таю.';
    $cacheKey = hash('sha256', $rawText);
    Storage::disk('local')->put("tts/{$cacheKey}.mp3", 'old-mp3-binary');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', $rawText]])
        ->set('hasCsvLoaded', true)
        ->call('refreshTtsAudio', 0)
        ->assertDispatched('tts-audio-ready')
        ->assertSet('ttsError', '');

    // Verify audio was regenerated (new content written by the TTS API response).
    expect(Storage::disk('local')->get("tts/{$cacheKey}.mp3"))->toBe('fresh-mp3-binary');
});

test('deleteRow resets ttsModalRowIndex to prevent stale references', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.'], ['Bonjour.', 'Привет.']])
        ->set('hasCsvLoaded', true)
        ->set('ttsModalRowIndex', 0)
        ->call('deleteRow', 0)
        ->assertSet('ttsModalRowIndex', -1);
});
