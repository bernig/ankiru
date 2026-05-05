<?php

return [
    // Layout
    'title' => 'Anki Editor',

    // Header
    'export' => 'Export',
    'export_csv' => 'Export CSV',
    'export_anki_package' => 'Export Anki Package (.apkg)',
    'load_new_file' => 'Load new file',
    'load_new_file_confirm' => 'This will discard the current file. Are you sure?',

    // Table
    'no_rows_yet' => 'No rows yet. Click "Add row" to add one.',
    'add_row' => 'Add row',

    // Table row actions (button tooltips)
    'edit_french_text' => 'Edit French text',
    'edit_russian_text' => 'Edit Russian text',
    'open_audio_player' => 'Open audio player',
    'translate_with_chatgpt' => 'Translate with ChatGPT',
    'retranslate_with_chatgpt' => 'Regenerate translation with ChatGPT',
    'fix_stress_marks' => 'Fix stress marks with ChatGPT',
    'delete_row' => 'Delete row',

    // TTS modal
    'generated_at' => 'Generated :date',
    'no_audio_yet' => 'No audio file generated yet for this phrase.',
    'regenerate' => 'Regenerate',
    'generate_audio' => 'Generate Audio',
    'close' => 'Close',

    // Upload panel
    'upload_heading' => 'Upload a CSV file',
    'upload_subtext' => 'Select a .csv file to start editing.',
    'dropzone_heading' => 'Drop your CSV here',
    'dropzone_text' => 'or click to browse',
    'parsing' => 'Parsing…',

    // Validation / error messages
    'error_cannot_read_file' => 'Could not read the uploaded file.',
    'error_csv_empty_or_malformed' => 'The CSV file appears to be empty or malformed.',
    'error_audio_generation_failed' => 'Audio generation failed: :message',
];
