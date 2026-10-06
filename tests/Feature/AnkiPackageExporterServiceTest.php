<?php

use App\Services\AnkiPackageExporterService;
use Illuminate\Support\Facades\Storage;

/**
 * @param  array{front: string, back: string, mp3StoragePath?: string|null, mp3FileName?: string|null}  $card
 * @return array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}
 */
function exporterCard(array $card): array
{
    return $card + ['mp3StoragePath' => null, 'mp3FileName' => null];
}

/**
 * Open a generated .apkg, extract its collection database and media entries.
 *
 * @return array{db: PDO, media: string, entries: list<string>, mediaFiles: array<string, string>}
 */
function openApkg(string $apkgPath): array
{
    $zip = new ZipArchive;
    expect($zip->open($apkgPath))->toBeTrue();

    $entries = [];
    $mediaFiles = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        $entries[] = $name;
        if (ctype_digit($name)) {
            $mediaFiles[$name] = $zip->getFromName($name);
        }
    }

    $dbPath = tempnam(sys_get_temp_dir(), 'apkg_test_');
    file_put_contents($dbPath, $zip->getFromName('collection.anki2'));
    $media = $zip->getFromName('media');
    $zip->close();
    unlink($apkgPath);

    extractedApkgDatabases()[] = $dbPath;

    return ['db' => new PDO('sqlite:'.$dbPath), 'media' => $media, 'entries' => $entries, 'mediaFiles' => $mediaFiles];
}

/**
 * @return list<string>
 */
function &extractedApkgDatabases(): array
{
    static $paths = [];

    return $paths;
}

beforeEach(function (): void {
    Storage::fake('local');
});

afterEach(function (): void {
    $paths = &extractedApkgDatabases();
    array_map('unlink', $paths);
    $paths = [];
});

test('exports one note and one card per flashcard into the named deck', function (): void {
    $apkg = openApkg(app(AnkiPackageExporterService::class)->export([
        exporterCard(['front' => 'Bonjour', 'back' => 'Здр<b>а</b>вствуйте']),
        exporterCard(['front' => 'Merci', 'back' => 'Спас<b>и</b>бо']),
    ], 'Russe A1'));

    $notes = $apkg['db']->query('SELECT flds, sfld FROM notes ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $cardDeckIds = $apkg['db']->query('SELECT DISTINCT did FROM cards')->fetchAll(PDO::FETCH_COLUMN);
    $decks = json_decode($apkg['db']->query('SELECT decks FROM col')->fetchColumn(), true);

    expect($notes)->toBe([
        ['flds' => 'Bonjour'.chr(0x1F).'Здр<b>а</b>вствуйте', 'sfld' => 'Bonjour'],
        ['flds' => 'Merci'.chr(0x1F).'Спас<b>и</b>бо', 'sfld' => 'Merci'],
    ])
        ->and((int) $apkg['db']->query('SELECT COUNT(*) FROM cards')->fetchColumn())->toBe(2)
        ->and($cardDeckIds)->toHaveCount(1)
        ->and($decks[(string) $cardDeckIds[0]]['name'])->toBe('Russe A1');
});

test('writes an empty media map as a JSON object when no card has audio', function (): void {
    $apkg = openApkg(app(AnkiPackageExporterService::class)->export([
        exporterCard(['front' => 'Oui', 'back' => 'Да']),
    ], 'Deck'));

    expect($apkg['media'])->toBe('{}')
        ->and($apkg['entries'])->toEqualCanonicalizing(['collection.anki2', 'media']);
});

test('stores shared audio once and skips cards without audio or with a missing file', function (): void {
    Storage::disk('local')->put('tts/shared.mp3', 'shared-audio-bytes');
    Storage::disk('local')->put('tts/other.mp3', 'other-audio-bytes');

    $apkg = openApkg(app(AnkiPackageExporterService::class)->export([
        exporterCard(['front' => 'A', 'back' => 'А', 'mp3StoragePath' => 'tts/shared.mp3', 'mp3FileName' => 'shared.mp3']),
        exporterCard(['front' => 'B', 'back' => 'Б', 'mp3StoragePath' => 'tts/shared.mp3', 'mp3FileName' => 'shared.mp3']),
        exporterCard(['front' => 'C', 'back' => 'В']),
        exporterCard(['front' => 'D', 'back' => 'Г', 'mp3StoragePath' => 'tts/missing.mp3', 'mp3FileName' => 'missing.mp3']),
        exporterCard(['front' => 'E', 'back' => 'Д', 'mp3StoragePath' => 'tts/other.mp3', 'mp3FileName' => 'other.mp3']),
    ], 'Deck'));

    expect(json_decode($apkg['media'], true))->toBe(['0' => 'shared.mp3', '1' => 'other.mp3'])
        ->and($apkg['mediaFiles'])->toBe(['0' => 'shared-audio-bytes', '1' => 'other-audio-bytes'])
        ->and((int) $apkg['db']->query('SELECT COUNT(*) FROM notes')->fetchColumn())->toBe(5);
});

test('exports a collection as sub-decks of the parent deck', function (): void {
    $apkg = openApkg(app(AnkiPackageExporterService::class)->exportCollection([
        ['deckName' => 'Salutations', 'cards' => [
            exporterCard(['front' => 'Bonjour', 'back' => 'Привет']),
            exporterCard(['front' => 'Salut', 'back' => 'Пока']),
        ]],
        ['deckName' => 'Nombres', 'cards' => [
            exporterCard(['front' => 'Un', 'back' => 'Один']),
        ]],
    ], 'Russe'));

    $decks = json_decode($apkg['db']->query('SELECT decks FROM col')->fetchColumn(), true);
    $deckNameById = array_column($decks, 'name', 'id');
    $cardsPerDeck = $apkg['db']->query('SELECT did, COUNT(*) FROM cards GROUP BY did')->fetchAll(PDO::FETCH_KEY_PAIR);
    $cardsPerDeckName = collect($cardsPerDeck)->mapWithKeys(fn ($count, $deckId) => [$deckNameById[$deckId] => (int) $count])->all();

    expect($deckNameById)->toContain('Default', 'Russe', 'Russe::Salutations', 'Russe::Nombres')
        ->and($cardsPerDeckName)->toEqualCanonicalizing(['Russe::Salutations' => 2, 'Russe::Nombres' => 1]);
});
