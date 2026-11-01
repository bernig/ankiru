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
    $this->travelTo(now()->setDateTime(2026, 11, 1, 12, 0, 0));
});

/**
 * @param  array<int, array<int, string>>  $rows
 */
function exportDraft(string $fileName = 'sample.csv', ?array $rows = null): CsvDraft
{
    return CsvDraft::factory()->for(auth()->user())->create([
        'original_file_name' => $fileName,
        'csv_rows' => $rows ?? sampleRows(),
    ]);
}

/**
 * Replace the package builder with a fake that writes a small temporary file,
 * and hand the arguments of each call to $capture.
 */
function fakeAnkiExporter(string $method, ?Closure $capture = null, int $times = 1): void
{
    test()->mock(AnkiPackageExporterService::class)
        ->shouldReceive($method)
        ->times($times)
        ->andReturnUsing(function (...$arguments) use ($capture): string {
            if ($capture !== null) {
                $capture(...$arguments);
            }

            $temporaryPackagePath = sys_get_temp_dir().'/'.uniqid('anki_test_', true).'.apkg';
            file_put_contents($temporaryPackagePath, 'fake-apkg-content');

            return $temporaryPackagePath;
        });
}

// ── CSV ────────────────────────────────────────────────────────────────────
test('downloads the draft as a csv file', function (): void {
    $response = $this->get(route('exports.csv', exportDraft()));

    $response->assertOk()->assertDownload('sample_edited_20261101_120000.csv');
    expect($response->streamedContent())->toContain('Je travaille depuis chez moi.');
});

test('a file renamed with a backslash still downloads as csv', function (): void {
    $this->get(route('exports.csv', exportDraft('Mes\\phrases.csv')))
        ->assertDownload('Mes-phrases_edited_20261101_120000.csv');
});

test('stamps the file name with the time of the browser time zone', function (): void {
    $this->get(route('exports.csv', exportDraft()).'?tz=Europe/Moscow')
        ->assertDownload('sample_edited_20261101_150000.csv');
});

test('falls back to the application time zone for an unknown one', function (): void {
    $this->get(route('exports.csv', exportDraft()).'?tz=Mars/Olympus')
        ->assertDownload('sample_edited_20261101_120000.csv');
});

test('does not export another user\'s draft', function (string $routeName): void {
    $foreignDraft = CsvDraft::factory()->for(User::factory())->create();

    $this->get(route($routeName, $foreignDraft))->assertNotFound();
})->with(['exports.csv', 'exports.apkg']);

test('requires a signed-in user', function (): void {
    $draft = exportDraft();
    auth()->logout();

    $this->get(route('exports.csv', $draft))->assertRedirect(route('login'));
});

// ── Anki package ───────────────────────────────────────────────────────────
test('anki package export keeps russian stress tags in back fields', function (): void {
    $capturedCards = [];
    fakeAnkiExporter('export', function (array $cards, string $deckName) use (&$capturedCards): void {
        expect($deckName)->toBe('sample');
        $capturedCards = $cards;
    });

    $this->get(route('exports.apkg', exportDraft(rows: [['Je travaille.', 'Я раб<b>о</b>таю.']])))
        ->assertOk()
        ->assertDownload('sample_20261101_120000.apkg');

    expect($capturedCards)->toHaveCount(1)
        ->and($capturedCards[0]['back'])->toBe('Я раб<b>о</b>таю.');
});

test('deletes the temporary package once it has been sent', function (): void {
    $packagePath = null;
    $this->mock(AnkiPackageExporterService::class)
        ->shouldReceive('export')
        ->andReturnUsing(function () use (&$packagePath): string {
            $packagePath = sys_get_temp_dir().'/'.uniqid('anki_test_', true).'.apkg';
            file_put_contents($packagePath, 'fake-apkg-content');

            return $packagePath;
        });

    $response = $this->get(route('exports.apkg', exportDraft()));
    ob_start();
    $response->baseResponse->sendContent();
    ob_end_clean();

    expect(file_exists($packagePath))->toBeFalse();
});

test('anki package export gives notes guids that survive re-exports and stay unique', function (): void {
    $exportedGuids = [];
    fakeAnkiExporter('export', function (array $cards) use (&$exportedGuids): void {
        $exportedGuids[] = array_column($cards, 'guid');
    }, times: 2);

    $draft = exportDraft(rows: [['Bonjour', 'Привет'], ['Merci', 'Спасибо'], ['Bonjour', 'Здравствуйте']]);
    $this->get(route('exports.apkg', $draft));
    $this->get(route('exports.apkg', $draft));

    expect($exportedGuids[0])->toBe($exportedGuids[1])
        ->and(array_unique($exportedGuids[0]))->toHaveCount(3)
        ->and($exportedGuids[0][0])->toBe(AnkiPackageExporterService::noteGuid($draft->id, 'Bonjour'));
});

test('a file renamed with a slash keeps its whole name in the download and the deck', function (): void {
    fakeAnkiExporter('export', function (array $cards, string $deckName): void {
        expect($deckName)->toBe('Russe/Verbes');
    });

    $editor = Livewire::test(CsvEditor::class)
        ->setRows(sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('startRenameDraft')
        ->set('renameInput', 'Russe/Verbes')
        ->call('confirmRenameDraft')
        ->assertSet('originalFileName', 'Russe/Verbes.csv')
        ->assertSee('Russe/Verbes');

    $draftId = $editor->get('activeDraftId');

    $this->get(route('exports.csv', $draftId))->assertDownload('Russe-Verbes_edited_20261101_120000.csv');
    $this->get(route('exports.apkg', $draftId))->assertDownload('Russe-Verbes_20261101_120000.apkg');
});

// ── Collection ─────────────────────────────────────────────────────────────
test('collection export groups all drafts as separate decks', function (): void {
    $capturedDecks = [];
    fakeAnkiExporter('exportCollection', function (array $decks, string $parentName) use (&$capturedDecks): void {
        expect($parentName)->toBe('Collection');
        $capturedDecks = $decks;
    });

    exportDraft('first.csv');
    exportDraft('second.csv', [['Hello', 'Привет']]);

    $this->get(route('exports.collection'))->assertOk()->assertDownload('Collection_20261101_120000.apkg');

    expect(array_column($capturedDecks, 'deckName'))->toBe(['first', 'second']);
});

test('a collection name with a slash downloads instead of failing', function (): void {
    fakeAnkiExporter('exportCollection', function (array $decks, string $parentName): void {
        expect($parentName)->toBe('Russe/Français');
    });

    exportDraft('first.csv');
    exportDraft('second.csv');

    $response = $this->get(route('exports.collection', ['name' => 'Russe/Français']));

    // Browsers use the UTF-8 name (filename*); filename= is only an ASCII fallback.
    expect($response->headers->get('Content-Disposition'))
        ->toContain("filename*=utf-8''".rawurlencode('Russe-Français_20261101_120000.apkg'));
});

test('collection export option is shown only when at least two files exist', function (): void {
    exportDraft('first.csv');

    Livewire::test(CsvEditor::class)
        ->assertDontSee(__('csv_editor.export_collection_package'));

    exportDraft('second.csv');

    Livewire::test(CsvEditor::class)
        ->assertSee(__('csv_editor.export_collection_package'));
});

test('the export menu links to the active draft', function (): void {
    $draft = exportDraft();

    Livewire::test(CsvEditor::class)
        ->assertSeeHtml(route('exports.csv', $draft))
        ->assertSeeHtml(route('exports.apkg', $draft));
});
