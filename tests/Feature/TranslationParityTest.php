<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;

/**
 * Keys present in English (the fallback locale) must also exist in French and
 * Russian. Rendering checks cannot catch a missing key: __() silently falls
 * back to English, leaving English text in the middle of a translated page.
 *
 * Russian validation messages are excluded from the full comparison: the file
 * only translates the rules the application uses (checked separately below).
 */
$translationFiles = array_map(
    fn (string $path): string => basename($path, '.php'),
    glob(__DIR__.'/../../lang/en/*.php'),
);

$untranslatedFilesByLocale = [
    'ru' => ['validation'],
];

test('every english translation key exists in the other supported locales', function (string $file) use ($untranslatedFilesByLocale): void {
    $englishKeys = array_keys(Arr::dot(require lang_path("en/{$file}.php")));

    foreach (['fr', 'ru'] as $locale) {
        if (in_array($file, $untranslatedFilesByLocale[$locale] ?? [], true)) {
            continue;
        }

        $localeKeys = array_keys(Arr::dot(require lang_path("{$locale}/{$file}.php")));

        expect(array_values(array_diff($englishKeys, $localeKeys)))
            ->toBe([], "lang/{$locale}/{$file}.php is missing keys");
    }
})->with($translationFiles);

test('russian validation messages exist for every rule the application uses', function (string $key): void {
    expect(Lang::has("validation.{$key}", 'ru', false))->toBeTrue();
})->with([
    'required', 'string', 'email', 'boolean', 'confirmed', 'file', 'mimes', 'unique', 'exists', 'integer', 'lowercase',
    'between.numeric', 'min.numeric', 'min.string', 'max.numeric', 'max.string', 'max.file',
]);
