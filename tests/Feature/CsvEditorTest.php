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
test('editing the right column clears the stress correction status for that row', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('stressCorrectionStatus', [0 => 'corrected'])
        ->call('updateCell', 0, 1, 'Я ост<b>а</b>юсь д<b>о</b>ма.')
        ->assertSet('stressCorrectionStatus', []);
});
test('editing the left column does not clear the stress correction status', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('stressCorrectionStatus', [0 => 'corrected'])
        ->call('updateCell', 0, 0, 'Je rentre chez moi.')
        ->assertSet('stressCorrectionStatus.0', 'corrected');
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
test('deleting a row clears per-row accent mode to avoid stale indices', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('accentModeRowIndex', 1)
        ->call('deleteRow', 0)
        ->assertSet('accentModeRowIndex', -1);
});
test('deleting a row clears all stress correction statuses to avoid stale indices', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('stressCorrectionStatus', [0 => 'ok', 1 => 'corrected'])
        ->call('deleteRow', 0)
        ->assertSet('stressCorrectionStatus', []);
});
// ── Russian Accent Mode ─────────────────────────────────────────────────────
test('toggles global russian accent mode on', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('toggleRussianAccentMode')
        ->assertSet('isRussianAccentMode', true);
});
test('toggles global russian accent mode off when already active', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('isRussianAccentMode', true)
        ->call('toggleRussianAccentMode')
        ->assertSet('isRussianAccentMode', false);
});
test('toggling global accent mode resets the per-row accent mode index', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('accentModeRowIndex', 1)
        ->call('toggleRussianAccentMode')
        ->assertSet('accentModeRowIndex', -1);
});
test('enables per-row accent mode for a specific row', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('toggleRowAccentMode', 1)
        ->assertSet('accentModeRowIndex', 1);
});
test('clicking the same row again disables per-row accent mode', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('accentModeRowIndex', 0)
        ->call('toggleRowAccentMode', 0)
        ->assertSet('accentModeRowIndex', -1);
});
test('switching per-row accent mode to another row updates the active index', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('accentModeRowIndex', 0)
        ->call('toggleRowAccentMode', 1)
        ->assertSet('accentModeRowIndex', 1);
});
test('hides per-row accent mode buttons when global accent mode is active', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('isRussianAccentMode', true)
        ->assertDontSee('Enable accent mode for this row')
        ->assertDontSee('Exit accent mode (return to edit mode)');
});
test('shows per-row accent mode buttons when global accent mode is inactive', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('isRussianAccentMode', false)
        ->assertSee('Enable accent mode for this row');
});
test('per-row accent button title changes to exit message when local accent mode is active for that row', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('accentModeRowIndex', 0)
        ->assertSee('Exit accent mode (return to edit mode)');
});
test('per-row accent button title shows enable message when local accent mode is inactive', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('accentModeRowIndex', -1)
        ->assertSee('Enable accent mode for this row');
});
test('per-row accent button is hidden when global accent mode is toggled on via the toggle action', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertSee('Enable accent mode for this row')
        ->call('toggleRussianAccentMode')
        ->assertDontSee('Enable accent mode for this row');
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
        ->assertSet('stressCorrectionStatus.0', 'corrected')
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
test('records ok status when chatgpt returns the text unchanged', function () {
    config(['services.openai.api_key' => 'test-api-key']);
    $alreadyCorrectText = 'Я раб<b>о</b>таю из д<b>о</b>ма.';
    Http::fake([
        'api.openai.com/*' => Http::response([
            'output' => [
                ['content' => [['text' => 'Я раб<b>о</b>таю из д<b>о</b>ма.']]],
            ],
        ], 200),
    ]);
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', $alreadyCorrectText]])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0)
        ->assertSet('stressCorrectionStatus.0', 'ok');
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
            && $request->data()['model'] === 'tts-1-hd';
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

    expect($service->buildCacheKey($rawText))->toBe(hash('sha256', $rawText));
});

test('tts service strips bold tags and trims whitespace when normalizing for speech', function () {
    $service = new RussianTextToSpeechService;

    expect($service->normalizeForSpeech('  Я раб<b>о</b>таю.  '))->toBe('Я работаю.');
});

test('tts service uses different cache keys for phrases with different stress positions', function () {
    $service = new RussianTextToSpeechService;

    $phraseWithStressOnO = 'раб<b>о</b>таю';
    $phraseWithStressOnA = 'работ<b>а</b>ю';

    expect($service->buildCacheKey($phraseWithStressOnO))
        ->not->toBe($service->buildCacheKey($phraseWithStressOnA));
});
