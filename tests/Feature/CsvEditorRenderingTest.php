<?php

use App\Livewire\CsvEditor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create();

    $this->actingAs($authenticatedUser);
});

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

test('normalises colour+bold font wrappers to plain bold tags on import', function (): void {
    $csvContent = '"Bonjour","раб<font color=""#ff0000""><b>о</b></font>тать"';
    $uploadedFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $uploadedFile)
        ->assertSet('csvRows.0.1', 'раб<b>о</b>тать');
});

test('normalises colour-only font wrappers to bold tags on import', function (): void {
    $csvContent = '"Bonjour","раб<font color=""#ff0000"">о</font>тать"';
    $uploadedFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $uploadedFile)
        ->assertSet('csvRows.0.1', 'раб<b>о</b>тать');
});

test('normalises combining acute accent to bold tags on import', function (): void {
    // CSV exported from unicode mode: о + U+0301
    $csvContent = "\"Bonjour\",\"рабо\u{0301}тать\"";
    $uploadedFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $uploadedFile)
        ->assertSet('csvRows.0.1', 'раб<b>о</b>тать');
});

test('rejects a non-csv file with a validation error', function () {
    $uploadedFile = UploadedFile::fake()->create('image.png', 100, 'image/png');

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $uploadedFile)
        ->assertHasErrors(['uploadedCsvFile'])
        ->assertSet('hasCsvLoaded', false);
});

test('rejects an empty csv file with a validation error', function () {
    $uploadedFile = UploadedFile::fake()->createWithContent('empty.csv', '');

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $uploadedFile)
        ->assertSet('hasCsvLoaded', false)
        ->assertSet('validationError', __('csv_editor.error_csv_empty_or_malformed'));
});
