<?php

return [
    // Layout
    'title' => 'Anki Editor',

    // Header
    'export' => 'Export',
    'export_csv' => 'Export CSV',
    'export_anki_package' => 'Export Anki Package (.apkg)',
    'export_collection_package' => 'Export All Files (.apkg) — all decks combined',
    'collection_name_modal_title' => 'Name your collection',
    'collection_name_label' => 'Collection name',
    'collection_name_placeholder' => 'Collection',
    'load_new_file' => 'Load new file',
    'load_new_file_confirm' => 'This will discard the current file. Are you sure?',
    'add_file' => 'Import CSV',
    'create_new_file' => 'Create a new file',
    'new_file_default_name' => 'Untitled file',
    'rename_file' => 'Rename',
    'delete_file' => 'Delete file',
    'delete_file_confirm' => 'Delete this file from the list? This cannot be undone.',
    'language_french' => 'French',
    'language_english' => 'English',
    'language_russian' => 'Russian',
    'logout' => 'Logout',

    // Search
    'search_placeholder' => 'Search…',
    'no_search_results' => 'No rows match your search.',

    // Table
    'no_rows_yet' => 'No rows yet. Click "Add row" to add one.',
    'add_row' => 'Add row',
    'per_page' => 'Rows per page',
    'source_column' => 'Source text',
    'russian_column' => 'Russian text',

    // Table row actions (button tooltips)
    'edit_source_text' => 'Edit source text',
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
    'or' => 'or',

    // Bulk Actions modal
    'bulk_actions' => 'Bulk Actions',
    'bulk_stress_title' => 'Fix Missing Stress Marks',
    'bulk_tts_title' => 'Generate Missing Audio',
    'bulk_done' => 'Done',
    'bulk_running' => 'Running…',
    'bulk_last_run' => 'Previous run',
    'bulk_rows_need_stress' => ':count phrase needs fixing|:count phrases need fixing',
    'bulk_rows_missing_audio' => ':count phrase missing audio|:count phrases missing audio',
    'bulk_all_stress_done' => 'All phrases already have stress marks.',
    'bulk_all_audio_done' => 'All phrases already have audio files.',
    'bulk_failed' => ':count failed',
    'bulk_run_stress' => 'Fix :count phrase|Fix :count phrases',
    'bulk_run_tts' => 'Generate :count audio file|Generate :count audio files',
    'bulk_report_corrected' => ':count corrected',
    'bulk_report_tokens' => ':input input / :output output tokens (exact)',
    'bulk_report_generated' => ':count generated',
    'bulk_report_chars' => ':chars characters (exact)',
    'bulk_widget_details' => 'Details',
    'bulk_widget_tokens' => 'tokens',
    'bulk_widget_chars' => 'chars',
    'bulk_stress_in_progress' => 'Bulk stress correction in progress',
    'bulk_tts_in_progress' => 'Bulk audio generation in progress',
    'bulk_estimate_tokens' => '~:input input / :output output tokens (≈ $:cost) · :model',
    'bulk_estimate_chars' => '~:chars characters (≈ $:cost) · :model',
    'bulk_warning_queue' => "Queue driver is set to 'sync'. Bulk jobs will block the request instead of running in the background.",
    'bulk_warning_broadcast' => "Broadcast driver is set to 'log'. Real-time progress updates will not be available.",

    // Accent style
    'accent_style' => 'Accent style',
    'accent_style_title' => 'Accented Letters Style',
    'accent_color' => 'Color',
    'accent_bold' => 'Bold',
    'accent_none_note' => 'No style active — the accent position is preserved in the data but not visible on screen.',
    'accent_unicode' => 'Combining accent (á, é, о́…)',
    'accent_unicode_description' => 'Replaces the stressed vowel with its combining acute accent version.',
    'accent_style_save' => 'Apply',

    // Validation / error messages
    'error_cannot_read_file' => 'Could not read the uploaded file.',
    'error_csv_empty_or_malformed' => 'The CSV file appears to be empty or malformed.',
    'error_audio_generation_failed' => 'Audio generation failed: :message',
    'error_rate_limit' => 'Too many requests. Please wait a moment before trying again.',
];
