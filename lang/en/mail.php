<?php

return [
    'daily_summary' => [
        'subject' => '[%s] Summary for %s',
        'heading' => 'Summary for :date',
        'intro' => 'Here is the activity recorded on **:app** on :date.',
        'col_event' => 'Event',
        'col_count' => 'Count',
        'new_users' => 'New users',
        'new_files' => 'New files created',
        'translations' => 'Translations performed',
        'stress_corrections' => 'Stress corrections',
        'tts_generations' => 'Audio generations (TTS)',
    ],
    'new_file' => [
        'subject' => '[%s] New file created',
        'heading' => 'New file created',
        'intro' => 'A user has just created or imported a new file on **:app**.',
        'col_file' => 'File',
        'col_user' => 'User',
        'col_email' => 'Email',
        'col_date' => 'Date',
        'unnamed' => '(unnamed)',
    ],
    'new_user' => [
        'subject' => '[%s] New user registered',
        'heading' => 'New user registered',
        'intro' => 'A new account has just been created on **:app**.',
        'col_name' => 'Name',
        'col_email' => 'Email',
        'col_registered_at' => 'Registration date',
    ],
];
