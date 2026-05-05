<?php

use App\Ai\Agents\FrenchToRussianTranslatorAgent;
use App\Ai\Agents\RussianStressCorrectorAgent;
use App\Livewire\CsvEditor;
use App\Services\RussianTextToSpeechService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Audio;
use Livewire\Livewire;

function sampleRows(): array
{
    return [
        ['Je travaille depuis chez moi.', 'Я раб<b>о</b>таю из д<b>о</b>ма.'],
        ['Je suis développeur web.', 'Я веб-разраб<b>о</b>тчик.'],
    ];
}

/**
 * Remove any leftover temp files regardless of session ID to avoid cross-test
 * contamination when the session changes between the test body and Livewire requests.
 * The local disk root is storage/app/private per filesystems config.
 */
function cleanUpTempFiles(): void
{
    foreach (glob(storage_path('app/private/csv_editor_temp_*.json')) ?: [] as $file) {
        @unlink($file);
    }
}
beforeEach(fn () => cleanUpTempFiles());
afterEach(fn () => cleanUpTempFiles());
// ── Rendering ──────────────────────────────────────────────────────────────
test('component renders successfully', function () {
    Livewire::test(CsvEditor::class)
        ->assertStatus(200);
});
test('shows the upload panel when no csv is loaded', function () {
    Livewire::test(CsvEditor::class)
        ->assertSet('hasCsvLoaded', false)
        ->assertSee(__('csv_editor.upload_heading'));
});
test('hides the upload panel and shows rows when a csv is loaded', function () {
    // Cell content is rendered via Alpine x-html (client-side), so assertSee is not
    // appropriate here. We verify state instead, and confirm the upload panel is gone.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertDontSee(__('csv_editor.upload_heading'))
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
        ->assertSet('validationError', __('csv_editor.error_csv_empty_or_malformed'));
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
        ->assertSee(__('csv_editor.edit_russian_text'));
});
test('accent mode pencil button is not rendered when the russian column is empty', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', '']])
        ->set('hasCsvLoaded', true)
        ->assertDontSee(__('csv_editor.edit_russian_text'));
});
test('french pencil button is always rendered for editing the left column', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertSee(__('csv_editor.edit_french_text'));
});
test('retranslate button is rendered when both french and russian text exist', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertSee(__('csv_editor.retranslate_with_chatgpt'));
});
test('retranslate button is not rendered when russian column is empty', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', '']])
        ->set('hasCsvLoaded', true)
        ->assertDontSee(__('csv_editor.retranslate_with_chatgpt'));
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
test('single-syllable words are never flagged as needing a stress mark', function () {
    // "я из" — "я" (1 vowel) and "из" (1 vowel) are both single-syllable; neither needs an accent.
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Phrase.', 'я из']])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
});
test('single-syllable words mixed with multi-syllable words only flag the multi-syllable word', function () {
    // "он работает" — "он" (1 vowel, single-syllable) should not affect the flag;
    // "работает" (4 vowels, no accent) should trigger the flag.
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Phrase.', 'он работает']])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeTrue();
});

test('two-vowel word without a stress mark is flagged as needing correction', function () {
    // "яма" has 2 vowels and no accent — the ≥2-vowel rule means it should be flagged.
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Phrase.', 'яма']])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeTrue();
});

test('two-vowel word with a stress mark is not flagged as needing correction', function () {
    // "я<b>м</b>а" — stress mark present, should not be flagged.
    // Actually testing "я<b>м</b>а" is a consonant. Let's use "я<b>м</b>а" targeting a vowel: "яm<b>а</b>".
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Phrase.', 'ям<b>а</b>']])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
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
        ->assertSee(__('csv_editor.upload_heading'));
});
test('resetting the editor deletes the temp file if it exists', function () {
    // Trigger a save through a component action so the file is written with the
    // correct session-scoped path inside the request context.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('originalFileName', 'sample.csv')
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 0, 'Je travaille depuis chez moi.'); // Triggers autoSave.

    // At least one temp file should now exist on the local disk.
    expect(glob(storage_path('app/private/csv_editor_temp_*.json')))->not->toBeEmpty();

    Livewire::test(CsvEditor::class)
        ->call('resetEditor');

    // The reset should have deleted the file.
    expect(glob(storage_path('app/private/csv_editor_temp_*.json')))->toBeEmpty();
});
// ── Temp File Persistence ───────────────────────────────────────────────────
test('restores editor state from the temp file on mount', function () {
    // Step 1: Save state through a component action so the file is written with the
    // correct session-scoped path inside the request context.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('originalFileName', 'restored.csv')
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 0, 'Je travaille depuis chez moi.'); // Triggers autoSave.

    // Step 2: Fresh mount (same session via test cookie) should restore the saved state.
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
test('translates french text to russian and stores the result', function () {
    FrenchToRussianTranslatorAgent::fake(['Я раб<b>о</b>таю из д<b>о</b>ма.']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('csvRows.0.1', 'Я раб<b>о</b>таю из д<b>о</b>ма.')
        ->assertSet('translationError', '')
        ->assertSet('translatingRowIndex', -1);

    FrenchToRussianTranslatorAgent::assertPrompted('Je travaille depuis chez moi.');
});
test('sets a translation error when the agent throws an exception', function () {
    FrenchToRussianTranslatorAgent::fake(function () {
        throw new RuntimeException('Service unavailable.');
    });

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', 'Service unavailable.');
});
test('does nothing when the french column is empty', function () {
    FrenchToRussianTranslatorAgent::fake()->preventStrayPrompts();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', '');

    FrenchToRussianTranslatorAgent::assertNeverPrompted();
});
// ── Stress Correction ───────────────────────────────────────────────────────
test('shows an error when the stress correction agent throws an exception', function () {
    RussianStressCorrectorAgent::fake(function () {
        throw new RuntimeException('Service unavailable.');
    });

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0)
        ->assertSet('translationError', 'Service unavailable.');
});
test('corrects stress marks and sends french context with the russian text', function () {
    RussianStressCorrectorAgent::fake(['Я раб<b>о</b>таю из д<b>о</b>ма.']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', 'Я работаю из дома.']])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0)
        ->assertSet('csvRows.0.1', 'Я раб<b>о</b>таю из д<b>о</b>ма.')
        ->assertSet('correctingStressRowIndex', -1);

    RussianStressCorrectorAgent::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'French source')
            && str_contains($prompt->prompt, 'Je travaille depuis chez moi.')
            && str_contains($prompt->prompt, 'Russian text to review and correct stress marks in:')
            && str_contains($prompt->prompt, 'Я работаю из дома.');
    });
});
test('row needs stress correction returns false when the agent returns already correct text', function () {
    $alreadyCorrectText = 'Я раб<b>о</b>таю из д<b>о</b>ма.';
    RussianStressCorrectorAgent::fake([$alreadyCorrectText]);

    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', $alreadyCorrectText]])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
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

    // Verify the file exists before deletion.
    expect(Storage::disk('local')->exists("tts/{$filenameHash}.mp3"))->toBeTrue();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', $rawRussianText]])
        ->set('hasCsvLoaded', true)
        ->call('deleteTtsAudio', 0);

    // Verify the file was deleted.
    expect(Storage::disk('local')->exists("tts/{$filenameHash}.mp3"))->toBeFalse();
});

test('deleting tts audio for a row with no cached file does nothing gracefully', function () {
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

    // Verify audio was regenerated (new content written by the TTS API response).
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

test('translation is blocked and an error is set after exceeding the rate limit', function () {
    FrenchToRussianTranslatorAgent::fake()->preventStrayPrompts();

    // Exhaust the 30-attempt limit without triggering real agent calls.
    $rateLimitKey = 'ai-translation:'.session()->getId();
    RateLimiter::clear($rateLimitKey);
    for ($i = 0; $i < 30; $i++) {
        RateLimiter::hit($rateLimitKey, 60);
    }

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', __('csv_editor.error_rate_limit'));

    FrenchToRussianTranslatorAgent::assertNeverPrompted();
    RateLimiter::clear($rateLimitKey);
});

test('tts generation is blocked and an error is set after exceeding the rate limit', function () {
    Storage::fake('local');
    Audio::fake()->preventStrayAudio();

    $rateLimitKey = 'tts-generation:'.session()->getId();
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
