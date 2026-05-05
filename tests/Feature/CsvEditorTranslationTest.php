<?php

/**
 * Verify that all csv_editor translation keys resolve to non-empty strings
 * in both the English and French locales.
 */
$translationKeys = [
    'title',
    'export',
    'export_csv',
    'export_anki_package',
    'load_new_file',
    'load_new_file_confirm',
    'no_rows_yet',
    'add_row',
    'edit_french_text',
    'edit_russian_text',
    'open_audio_player',
    'translate_with_chatgpt',
    'retranslate_with_chatgpt',
    'fix_stress_marks',
    'delete_row',
    'generated_at',
    'no_audio_yet',
    'regenerate',
    'generate_audio',
    'close',
    'upload_heading',
    'upload_subtext',
    'dropzone_heading',
    'dropzone_text',
    'parsing',
    'error_cannot_read_file',
    'error_csv_empty_or_malformed',
    'error_audio_generation_failed',
];

it('resolves every csv_editor key in English', function (string $key) {
    app()->setLocale('en');
    $translated = __("csv_editor.{$key}", ['date' => 'today', 'message' => 'err']);

    // The key must not fall back to its raw key (which would mean it is missing).
    expect($translated)->not->toBe("csv_editor.{$key}");
    expect($translated)->not->toBeEmpty();
})->with($translationKeys);

it('resolves every csv_editor key in French', function (string $key) {
    app()->setLocale('fr');
    $translated = __("csv_editor.{$key}", ['date' => 'today', 'message' => 'err']);

    expect($translated)->not->toBe("csv_editor.{$key}");
    expect($translated)->not->toBeEmpty();
})->with($translationKeys);
