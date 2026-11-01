<?php

use App\Services\CsvImportService;
use App\Services\RussianAccentService;

test('parses a comma-separated file', function (): void {
    expect((new CsvImportService(new RussianAccentService))->parse("Bonjour,Привет\nMerci,Спасибо\n"))
        ->toBe([['Bonjour', 'Привет'], ['Merci', 'Спасибо']]);
});

test('detects semicolons even when the sentences contain commas', function (): void {
    $content = "Oui, merci;Да, спасибо\nBonjour;Привет\nNon, pas encore, désolé;Нет, ещё нет\n";

    expect((new CsvImportService(new RussianAccentService))->parse($content))->toBe([
        ['Oui, merci', 'Да, спасибо'],
        ['Bonjour', 'Привет'],
        ['Non, pas encore, désolé', 'Нет, ещё нет'],
    ]);
});

test('detects tab-separated files', function (): void {
    expect((new CsvImportService(new RussianAccentService))->parse("Oui, merci\tДа, спасибо\nBonjour\tПривет\n"))
        ->toBe([['Oui, merci', 'Да, спасибо'], ['Bonjour', 'Привет']]);
});

test('strips the UTF-8 byte order mark', function (): void {
    expect((new CsvImportService(new RussianAccentService))->parse("\xEF\xBB\xBFBonjour,Привет\n"))
        ->toBe([['Bonjour', 'Привет']]);
});

test('converts Windows-1251 Russian files to UTF-8', function (): void {
    $content = mb_convert_encoding("Lete;Лето\nCafe;Кофе, ёлка\n", 'Windows-1251', 'UTF-8');

    expect((new CsvImportService(new RussianAccentService))->parse($content))->toBe([['Lete', 'Лето'], ['Cafe', 'Кофе, ёлка']]);
});

test('converts Windows-1252 French files to UTF-8', function (): void {
    $content = mb_convert_encoding("Été;Summer\nCafé crème;Coffee\n", 'Windows-1252', 'UTF-8');

    expect((new CsvImportService(new RussianAccentService))->parse($content))->toBe([['Été', 'Summer'], ['Café crème', 'Coffee']]);
});

test('decodes UTF-16 "Unicode text" exports', function (): void {
    $content = "\xFF\xFE".mb_convert_encoding("Été\tЛето\nCafé\tКофе\n", 'UTF-16LE', 'UTF-8');

    expect((new CsvImportService(new RussianAccentService))->parse($content))->toBe([['Été', 'Лето'], ['Café', 'Кофе']]);
});

test('keeps quoted fields spanning several lines and skips blank lines', function (): void {
    $content = "\"Ligne une\nligne deux\";Привет\n\nMerci;Спасибо\n";

    expect((new CsvImportService(new RussianAccentService))->parse($content))
        ->toBe([["Ligne une\nligne deux", 'Привет'], ['Merci', 'Спасибо']]);
});

test('reads a single-column file', function (): void {
    expect((new CsvImportService(new RussianAccentService))->parse("Bonjour\nMerci\n"))->toBe([['Bonjour'], ['Merci']]);
});

test('returns false for an empty file', function (): void {
    expect((new CsvImportService(new RussianAccentService))->parse("\n\n"))->toBeFalse();
});

test('pads or truncates rows to the first row and converts accent formats to bold tags', function (): void {
    $content = "Travailler,рабо\u{0301}тать\nSeul\nTrop,раб<font color=\"#ff0000\">о</font>тать,extra\n";

    expect((new CsvImportService(new RussianAccentService))->parse($content))->toBe([
        ['Travailler', 'раб<b>о</b>тать'],
        ['Seul', ''],
        ['Trop', 'раб<b>о</b>тать'],
    ]);
});
