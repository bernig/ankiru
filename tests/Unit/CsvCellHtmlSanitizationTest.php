<?php

/**
 * Runs the real resources/js/csv-editor.js through tests/js/csvAccentModeHtml.cjs
 * to make sure cell values rendered with x-html cannot inject markup.
 */
function runCsvAccentMode(string $functionName, string $input): string
{
    $script = __DIR__.'/../js/csvAccentModeHtml.cjs';
    $inputArg = escapeshellarg(json_encode($input, JSON_UNESCAPED_UNICODE));
    $output = shell_exec('node '.escapeshellarg($script)." {$functionName} {$inputArg} 2>/dev/null");

    return json_decode((string) $output, true);
}

test('sanitizeRichText escapes markup but keeps bare stress tags', function (string $input, string $expected) {
    expect(runCsvAccentMode('sanitizeRichText', $input))->toBe($expected);
})->with([
    'script tag' => ['<script>alert(1)</script>', '&lt;script&gt;alert(1)&lt;/script&gt;'],
    'event handler on bold' => ['<b onclick="x">a</b>', '&lt;b onclick=&quot;x&quot;&gt;a</b>'],
    'bare stress tags kept' => ['при<b>в</b>ет', 'при<b>в</b>ет'],
    'ampersand' => ['pain & beurre', 'pain &amp; beurre'],
]);

test('buildHtml escapes non-vowel characters of the Russian cell', function () {
    $html = runCsvAccentMode('buildHtml', '<img src=x onerror=alert(1)> мир');

    expect($html)->toStartWith('&lt;img src=x onerror=alert(1)&gt; ')
        ->not->toContain('<img');
});
