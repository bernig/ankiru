<?php

use App\Services\RussianAccentService;

/**
 * Ensures that the JS moveAccentToPosition function (csv-editor.js) produces
 * identical output to the PHP RussianAccentService::moveAccentToPosition() for
 * all representative inputs.
 *
 * The Node.js helper at tests/js/moveAccentToPosition.js is a standalone mirror
 * of the JS implementation; this test runs both and asserts equality.
 */
dataset('move_accent_cases', [
    'place accent on first vowel' => ['работать', 1],
    'place accent on middle vowel' => ['работать', 3],
    'place accent on last vowel' => ['работать', 5],
    'move accent to another vowel in same word' => ['раб<b>о</b>тать', 1],
    'idempotent: click already-accented vowel' => ['раб<b>о</b>тать', 3],
    'second word in multi-word string' => ['привет мир', 8],
    'preserve other word accent when adding' => ['пр<b>и</b>вет мир', 8],
    'move to first word, preserve second' => ['привет м<b>и</b>р', 2],
    'two accented words: move within second' => ['Как<b>о</b>й авт<b>о</b>бус', 6],
    'ё vowel in multi-vowel word' => ['идёт', 2],
    'move accent away from ё' => ['идёт', 0],
    'consonant position: no change' => ['работать', 0],
    'out-of-bounds position: no change' => ['работать', 99],
    'negative position: no change' => ['работать', -1],
    'empty string: no change' => ['', 0],
    'single-vowel word' => ['мир', 1],
]);

it('JS moveAccentToPosition matches PHP', function (string $rawText, int $charPosition) {
    $phpResult = (new RussianAccentService)->moveAccentToPosition($rawText, $charPosition);

    $script = __DIR__.'/../js/moveAccentToPosition.cjs';
    $textArg = escapeshellarg(json_encode($rawText, JSON_UNESCAPED_UNICODE));
    $jsOutput = shell_exec("node {$script} {$textArg} {$charPosition} 2>/dev/null");
    $jsResult = json_decode((string) $jsOutput, true);

    expect($jsResult)->toBe($phpResult);
})->with('move_accent_cases');
