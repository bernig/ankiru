<?php

use App\Livewire\CsvEditor;
use App\Models\CsvDraft;
use App\Models\User;
use App\Services\AnkiPackageExporterService;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create();

    $this->actingAs($authenticatedUser);
});

// ── CSV Download ───────────────────────────────────────────────────────────
test('downloading the csv triggers a file download', function () {
    Livewire::test(CsvEditor::class)
        ->setRows(sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'sample.csv')
        ->call('downloadCsv')
        ->assertFileDownloaded();
});

test('anki package export keeps russian stress tags in back fields', function (): void {
    $capturedCards = [];

    $this->mock(AnkiPackageExporterService::class)
        ->shouldReceive('export')
        ->once()
        ->withArgs(function (array $cards, string $deckName) use (&$capturedCards): bool {
            $capturedCards = $cards;

            return $deckName === 'sample';
        })
        ->andReturnUsing(function (): string {
            $temporaryPackagePath = sys_get_temp_dir().'/'.uniqid('anki_test_', true).'.apkg';
            file_put_contents($temporaryPackagePath, 'fake-apkg-content');

            return $temporaryPackagePath;
        });

    Livewire::test(CsvEditor::class)
        ->setRows([['Je travaille.', 'Я раб<b>о</b>таю.']])
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'sample.csv')
        ->call('downloadAnkiPackage')
        ->assertFileDownloaded();

    expect($capturedCards)->toHaveCount(1)
        ->and($capturedCards[0]['back'])->toBe('Я раб<b>о</b>таю.');
});

test('anki package export gives notes guids that survive re-exports and stay unique', function (): void {
    $exportedGuids = [];

    $this->mock(AnkiPackageExporterService::class)
        ->shouldReceive('export')
        ->twice()
        ->withArgs(function (array $cards) use (&$exportedGuids): bool {
            $exportedGuids[] = array_column($cards, 'guid');

            return true;
        })
        ->andReturnUsing(function (): string {
            $temporaryPackagePath = sys_get_temp_dir().'/'.uniqid('anki_test_', true).'.apkg';
            file_put_contents($temporaryPackagePath, 'fake-apkg-content');

            return $temporaryPackagePath;
        });

    $draft = CsvDraft::factory()->create([
        'user_id' => auth()->id(),
        'original_file_name' => 'sample.csv',
        'csv_rows' => [['Bonjour', 'Привет'], ['Merci', 'Спасибо'], ['Bonjour', 'Здравствуйте']],
    ]);

    $editor = Livewire::test(CsvEditor::class)->assertSet('activeDraftId', $draft->id);
    $editor->call('downloadAnkiPackage');
    $editor->call('downloadAnkiPackage');

    expect($exportedGuids[0])->toBe($exportedGuids[1])
        ->and(array_unique($exportedGuids[0]))->toHaveCount(3)
        ->and($exportedGuids[0][0])->toBe(AnkiPackageExporterService::noteGuid($draft->id, 'Bonjour'));
});

test('colpkg export groups all drafts as separate decks', function (): void {
    $capturedDecks = [];

    $this->mock(AnkiPackageExporterService::class)
        ->shouldReceive('exportCollection')
        ->once()
        ->withArgs(function (array $decks) use (&$capturedDecks): bool {
            $capturedDecks = $decks;

            return true;
        })
        ->andReturnUsing(function (): string {
            $temporaryPackagePath = sys_get_temp_dir().'/'.uniqid('anki_test_', true).'.colpkg';
            file_put_contents($temporaryPackagePath, 'fake-colpkg-content');

            return $temporaryPackagePath;
        });

    CsvDraft::factory()->create([
        'user_id' => auth()->id(),
        'original_file_name' => 'first.csv',
        'csv_rows' => sampleRows(),
    ]);

    CsvDraft::factory()->create([
        'user_id' => auth()->id(),
        'original_file_name' => 'second.csv',
        'csv_rows' => [['Hello', 'Привет']],
    ]);

    Livewire::test(CsvEditor::class)
        ->call('downloadColpkg')
        ->assertFileDownloaded();

    expect($capturedDecks)->toHaveCount(2)
        ->and($capturedDecks[0]['deckName'])->toBe('first')
        ->and($capturedDecks[1]['deckName'])->toBe('second');
});

test('colpkg export option is shown only when at least two files exist', function (): void {
    CsvDraft::factory()->create([
        'user_id' => auth()->id(),
        'original_file_name' => 'first.csv',
    ]);

    Livewire::test(CsvEditor::class)
        ->assertDontSee(__('csv_editor.export_collection_package'));

    CsvDraft::factory()->create([
        'user_id' => auth()->id(),
        'original_file_name' => 'second.csv',
    ]);

    Livewire::test(CsvEditor::class)
        ->assertSee(__('csv_editor.export_collection_package'));
});
