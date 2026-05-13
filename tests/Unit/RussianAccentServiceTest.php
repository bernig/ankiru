<?php

use App\Services\RussianAccentService;

// ── normalizeYoAccent ─────────────────────────────────────────────────────────

test('normalizeYoAccent wraps bare ё in bold tags when the word has ≥2 vowels', function () {
    $service = new RussianAccentService;

    expect($service->normalizeYoAccent('идёт'))->toBe('ид<b>ё</b>т');
});

test('normalizeYoAccent wraps bare Ё (uppercase) in bold tags', function () {
    $service = new RussianAccentService;

    expect($service->normalizeYoAccent('Ёлка'))->toBe('<b>Ё</b>лка');
});

test('normalizeYoAccent does not tag ё when it is the only vowel in the word', function () {
    $service = new RussianAccentService;

    // "всё" has only one vowel (ё), so no tag is needed.
    expect($service->normalizeYoAccent('всё'))->toBe('всё');
});

test('normalizeYoAccent does not add a duplicate tag when ё is already tagged', function () {
    $service = new RussianAccentService;

    expect($service->normalizeYoAccent('ид<b>ё</b>т'))->toBe('ид<b>ё</b>т');
});

test('normalizeYoAccent replaces a wrong accent with the ё tag', function () {
    $service = new RussianAccentService;

    // AI incorrectly stressed и; normalizer must move the tag to ё.
    expect($service->normalizeYoAccent('<b>и</b>дёт'))->toBe('ид<b>ё</b>т');
});

test('normalizeYoAccent fixes ё and leaves other words untouched', function () {
    $service = new RussianAccentService;

    $input = 'Как<b>о</b>й авт<b>о</b>бус идёт в ц<b>е</b>нтр?';
    $expected = 'Как<b>о</b>й авт<b>о</b>бус ид<b>ё</b>т в ц<b>е</b>нтр?';

    expect($service->normalizeYoAccent($input))->toBe($expected);
});

test('normalizeYoAccent handles multiple words with ё in the same sentence', function () {
    $service = new RussianAccentService;

    // Both "идёт" and "найдёт" must be fixed; "всё" (1 vowel) must stay untagged.
    $input = 'Он идёт, всё найдёт.';
    $expected = 'Он ид<b>ё</b>т, всё найд<b>ё</b>т.';

    expect($service->normalizeYoAccent($input))->toBe($expected);
});

test('normalizeYoAccent returns plain text with no ё unchanged', function () {
    $service = new RussianAccentService;

    $input = 'Я раб<b>о</b>таю из д<b>о</b>ма.';

    expect($service->normalizeYoAccent($input))->toBe($input);
});

// ── textNeedsStressCorrection (bare ё still signals "needs correction") ────────

test('textNeedsStressCorrection returns true when bare ё is the only missing accent', function () {
    $service = new RussianAccentService;

    // "идёт" has 2 vowels (и + ё); ё is not tagged → the row must be processed
    // (normalizeYoAccent will fix it without calling the AI).
    expect($service->textNeedsStressCorrection('идёт'))->toBeTrue();
});

test('textNeedsStressCorrection returns true when a non-ё multi-vowel word has no accent', function () {
    $service = new RussianAccentService;

    expect($service->textNeedsStressCorrection('работаю'))->toBeTrue();
});

test('textNeedsStressCorrection returns false when all multi-vowel words are either accented or contain ё', function () {
    $service = new RussianAccentService;

    // Both "идёт" (ё tagged) and "домой" (о tagged) are correctly marked.
    expect($service->textNeedsStressCorrection('Он ид<b>ё</b>т д<b>о</b>мой.'))->toBeFalse();
});

// ── normalizeImportedCellValue ────────────────────────────────────────────────

test('normalizeImportedCellValue leaves plain text unchanged', function () {
    $service = new RussianAccentService;

    expect($service->normalizeImportedCellValue('работать'))->toBe('работать');
});

test('normalizeImportedCellValue leaves canonical <b> tags unchanged', function () {
    $service = new RussianAccentService;

    expect($service->normalizeImportedCellValue('раб<b>о</b>тать'))->toBe('раб<b>о</b>тать');
});

test('normalizeImportedCellValue strips font wrapper from colour+bold export format', function () {
    $service = new RussianAccentService;

    expect($service->normalizeImportedCellValue('раб<font color="#ff0000"><b>о</b></font>тать'))
        ->toBe('раб<b>о</b>тать');
});

test('normalizeImportedCellValue wraps vowel in bold when stripping colour-only font wrapper', function () {
    $service = new RussianAccentService;

    expect($service->normalizeImportedCellValue('раб<font color="#ff0000">о</font>тать'))
        ->toBe('раб<b>о</b>тать');
});

test('normalizeImportedCellValue converts combining acute accent to bold tag', function () {
    $service = new RussianAccentService;

    // о + U+0301 combining acute
    expect($service->normalizeImportedCellValue("рабо\u{0301}тать"))->toBe('раб<b>о</b>тать');
});

test('normalizeImportedCellValue handles combining accent on uppercase vowel', function () {
    $service = new RussianAccentService;

    expect($service->normalizeImportedCellValue("А\u{0301}"))->toBe('<b>А</b>');
});

test('normalizeImportedCellValue handles full sentence with multiple accent formats', function () {
    $service = new RussianAccentService;

    // Mix of combining accent and font wrapper in the same cell
    $input = "Я рабо\u{0301}таю из <font color=\"#d97706\"><b>д</b></font>ома.";
    $expected = 'Я раб<b>о</b>таю из <b>д</b>ома.';

    expect($service->normalizeImportedCellValue($input))->toBe($expected);
});

test('normalizeImportedCellValue strips bold-outer font-inner wrapper', function () {
    $service = new RussianAccentService;

    // Anki can re-export colour+bold with the tags in reversed order: <b><font>X</font></b>
    expect($service->normalizeImportedCellValue('раб<b><font color="#ff0000">о</font></b>тать'))
        ->toBe('раб<b>о</b>тать');
});

test('normalizeImportedCellValue collapses double-nested bold tags', function () {
    $service = new RussianAccentService;

    // Ensure any double-wrapped <b><b>X</b></b> is reduced to <b>X</b>
    expect($service->normalizeImportedCellValue('Ч<b><b>е</b></b>рез'))
        ->toBe('Ч<b>е</b>рез');
});

test('normalizeImportedCellValue collapses double-nested bold in full sentence', function () {
    $service = new RussianAccentService;

    $input = 'Ч<b><b>е</b></b>рез час у мен<b><b>я</b></b> звон<b><b>о</b></b>к.';
    $expected = 'Ч<b>е</b>рез час у мен<b>я</b> звон<b>о</b>к.';

    expect($service->normalizeImportedCellValue($input))->toBe($expected);
});
