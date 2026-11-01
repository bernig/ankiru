<?php

use App\Services\DeckExportService;
use App\Services\RussianTextToSpeechService;

function deckExportService(): DeckExportService
{
    return new DeckExportService(new RussianTextToSpeechService);
}

test('exports the canonical bold tags unchanged in the default style', function (): void {
    expect(deckExportService()->toCsv([['Travailler', 'раб<b>о</b>тать']], null, true, false))
        ->toBe("Travailler,раб<b>о</b>тать\n");
});

test('exports a colored accent with or without bold', function (bool $bold, string $expectedVowel): void {
    expect(deckExportService()->toCsv([['раб<b>о</b>тать']], '#ff0000', $bold, false))
        ->toBe("\"раб<font color=\"\"#ff0000\"\">{$expectedVowel}</font>тать\"\n");
})->with([
    'bold' => [true, '<b>о</b>'],
    'color only' => [false, 'о'],
]);

test('exports a combining accent but leaves ё without one', function (): void {
    expect(deckExportService()->toCsv([['раб<b>о</b>тать', 'ид<b>ё</b>т']], null, false, true))
        ->toBe("раб\u{043E}\u{0301}тать,идёт\n");
});

test('escapes a color value before writing it into the font tag', function (): void {
    expect(deckExportService()->toCsv([['<b>а</b>']], '"><script>', false, false))
        ->not->toContain('<script>');
});

test('turns a name with separators or control characters into a download file name', function (string $name, string $expected): void {
    expect(deckExportService()->toDownloadFileName($name, 'export'))->toBe($expected);
})->with([
    'slash' => ['Russe/Français', 'Russe-Français'],
    'backslash and tab' => ["Mes\\\tphrases", 'Mes-phrases'],
    'only separators' => ['/ .', 'export'],
]);
