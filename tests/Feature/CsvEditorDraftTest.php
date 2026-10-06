<?php

use App\Livewire\CsvEditor;
use App\Models\CsvDraft;
use App\Models\User;
use App\Services\AnkiPackageExporterService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create();

    $this->actingAs($authenticatedUser);
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

test('resetting the editor deletes the saved draft if it exists', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('originalFileName', 'sample.csv')
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 0, 'Je travaille depuis chez moi.');

    expect(CsvDraft::query()->count())->toBe(1);

    Livewire::test(CsvEditor::class)
        ->call('resetEditor');

    expect(CsvDraft::query()->count())->toBe(0);
});

// ── Draft Persistence ───────────────────────────────────────────────────────
test('restores editor state from the saved draft on mount', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('originalFileName', 'restored.csv')
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 0, 'Je travaille depuis chez moi.');

    Livewire::test(CsvEditor::class)
        ->assertSet('hasCsvLoaded', true)
        ->assertSet('originalFileName', 'restored.csv')
        ->assertCount('csvRows', 2)
        ->assertSet('csvRows.0.0', 'Je travaille depuis chez moi.');
});

test('starts fresh when no saved draft exists', function () {
    Livewire::test(CsvEditor::class)
        ->assertSet('hasCsvLoaded', false)
        ->assertSet('csvRows', []);
});

// ── Multiple Files ──────────────────────────────────────────────────────────
test('uploading a second csv creates a new draft and switches to it', function () {
    $csv1 = UploadedFile::fake()->createWithContent('first.csv', '"French","Russian"');
    $csv2 = UploadedFile::fake()->createWithContent('second.csv', '"Bonjour","Привет"');

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $csv1)
        ->assertSet('originalFileName', 'first.csv')
        ->set('uploadedCsvFile', $csv2)
        ->assertSet('originalFileName', 'second.csv');

    expect(CsvDraft::query()->count())->toBe(2);
});

test('allDraftsMeta lists all uploaded files', function () {
    $csv1 = UploadedFile::fake()->createWithContent('first.csv', '"French","Russian"');
    $csv2 = UploadedFile::fake()->createWithContent('second.csv', '"Bonjour","Привет"');

    $component = Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $csv1)
        ->set('uploadedCsvFile', $csv2);

    expect($component->get('allDraftsMeta'))->toHaveCount(2)
        ->and(collect($component->get('allDraftsMeta'))->pluck('original_file_name')->all())
        ->toBe(['first.csv', 'second.csv']);
});

test('switching drafts loads the rows of the selected file', function () {
    $csv1 = UploadedFile::fake()->createWithContent('first.csv', '"Row A","Row B"');
    $csv2 = UploadedFile::fake()->createWithContent('second.csv', '"Row X","Row Y"');

    $component = Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $csv1)
        ->set('uploadedCsvFile', $csv2)
        ->assertSet('originalFileName', 'second.csv');

    $firstDraftId = CsvDraft::query()->orderBy('id')->first()->id;

    $component
        ->call('switchToDraft', $firstDraftId)
        ->assertSet('originalFileName', 'first.csv')
        ->assertSet('csvRows.0.0', 'Row A');
});

test('resetting with multiple files switches to the next draft instead of showing the upload panel', function () {
    $csv1 = UploadedFile::fake()->createWithContent('first.csv', '"Row A","Row B"');
    $csv2 = UploadedFile::fake()->createWithContent('second.csv', '"Row X","Row Y"');

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $csv1)
        ->set('uploadedCsvFile', $csv2)
        ->call('resetEditor')
        ->assertSet('hasCsvLoaded', true)
        ->assertDontSee(__('csv_editor.upload_heading'));

    expect(CsvDraft::query()->count())->toBe(1);
});

test('renaming a file updates the original file name and persists it', function () {
    $csv = UploadedFile::fake()->createWithContent('old-name.csv', '"French","Russian"');

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $csv)
        ->call('startRenameDraft')
        ->assertSet('isRenamingFile', true)
        ->assertSet('renameInput', 'old-name')
        ->set('renameInput', 'new-name')
        ->call('confirmRenameDraft')
        ->assertSet('isRenamingFile', false)
        ->assertSet('originalFileName', 'new-name.csv');

    expect(CsvDraft::query()->first()->original_file_name)->toBe('new-name.csv');
});

test('cancelling a rename restores the original file name', function () {
    $csv = UploadedFile::fake()->createWithContent('keep-name.csv', '"French","Russian"');

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $csv)
        ->call('startRenameDraft')
        ->call('cancelRenameDraft')
        ->assertSet('isRenamingFile', false)
        ->assertSet('originalFileName', 'keep-name.csv');
});

test('creating a new file sets hasCsvLoaded and opens the rename prompt', function () {
    Livewire::test(CsvEditor::class)
        ->call('createNewFile')
        ->assertSet('hasCsvLoaded', true)
        ->assertSet('csvRows', [])
        ->assertSet('isRenamingFile', true);

    expect(CsvDraft::query()->count())->toBe(1);
});

test('creating a new file always produces a new draft even when one is already active', function () {
    $csv = UploadedFile::fake()->createWithContent('existing.csv', '"Row","Data"');

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $csv)
        ->call('createNewFile');

    expect(CsvDraft::query()->count())->toBe(2);
});

test('on reload, restores the last accessed draft rather than the most recently created one', function () {
    $csv1 = UploadedFile::fake()->createWithContent('first.csv', '"Row A","Row B"');
    $csv2 = UploadedFile::fake()->createWithContent('second.csv', '"Row X","Row Y"');

    $component = Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $csv1)
        ->set('uploadedCsvFile', $csv2);

    // second.csv was created last; switch back to first.csv
    $firstDraftId = CsvDraft::query()->orderBy('id')->first()->id;
    $component->call('switchToDraft', $firstDraftId);

    // A fresh mount should now restore first.csv, not second.csv
    Livewire::test(CsvEditor::class)
        ->assertSet('originalFileName', 'first.csv');
});

test('confirming a rename with an empty input dismisses the input without saving', function () {
    $csv = UploadedFile::fake()->createWithContent('keep.csv', '"French","Russian"');

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', $csv)
        ->call('startRenameDraft')
        ->set('renameInput', '   ')
        ->call('confirmRenameDraft')
        ->assertSet('isRenamingFile', false)
        ->assertSet('originalFileName', 'keep.csv');
});

// ── Ownership ────────────────────────────────────────────────────────────────

function foreignDraft(): CsvDraft
{
    return CsvDraft::query()->create([
        'user_id' => User::factory()->create()->id,
        'original_file_name' => 'secret.csv',
        'csv_rows' => [['Secret', 'Секрет']],
        'has_csv_loaded' => true,
        'last_accessed_at' => now()->subDay(),
    ]);
}

test('mount never restores a draft owned by another user', function () {
    foreignDraft();

    Livewire::test(CsvEditor::class)
        ->assertSet('hasCsvLoaded', false)
        ->assertSet('csvRows', [])
        ->assertDontSee('secret.csv');
});

test('switching to a draft owned by another user changes nothing', function () {
    $foreignDraft = foreignDraft();

    Livewire::test(CsvEditor::class)
        ->call('switchToDraft', $foreignDraft->id)
        ->assertSet('csvRows', [])
        ->assertSet('originalFileName', '');

    expect($foreignDraft->fresh()->last_accessed_at->isToday())->toBeFalse();
});

test('a tampered draft id cannot overwrite or delete another user\'s draft', function () {
    $foreignDraft = foreignDraft();

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $foreignDraft->id)
        ->set('hasCsvLoaded', true)
        ->set('csvRows', [['Overwritten', 'Перезаписано']])
        ->call('updateCell', 0, 0, 'Overwritten again')
        ->call('resetEditor');

    expect($foreignDraft->fresh())
        ->not->toBeNull()
        ->csv_rows->toBe([['Secret', 'Секрет']]);
});

test('the collection export only contains the current user\'s drafts', function () {
    foreignDraft();
    $exporter = Mockery::spy(AnkiPackageExporterService::class);
    $exporter->shouldReceive('exportCollection')->andReturnUsing(fn () => tempnam(sys_get_temp_dir(), 'colpkg_'));
    app()->instance(AnkiPackageExporterService::class, $exporter);

    Livewire::test(CsvEditor::class)
        ->set('uploadedCsvFile', UploadedFile::fake()->createWithContent('mine.csv', '"Bonjour","Привет"'))
        ->call('downloadColpkg');

    $exporter->shouldHaveReceived('exportCollection')
        ->withArgs(fn (array $decks) => array_column($decks, 'deckName') === ['mine'])
        ->once();
});
