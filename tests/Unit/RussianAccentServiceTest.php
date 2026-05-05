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

    // AI incorrectly stressed и — normalizer must move the tag to ё.
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
