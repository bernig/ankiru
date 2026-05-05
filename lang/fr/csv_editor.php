<?php

return [
    // Disposition
    'title' => 'Éditeur Anki',

    // En-tête
    'export' => 'Exporter',
    'export_csv' => 'Exporter en CSV',
    'export_anki_package' => 'Exporter le paquet Anki (.apkg)',
    'load_new_file' => 'Charger un nouveau fichier',
    'load_new_file_confirm' => 'Cela supprimera le fichier actuel. Êtes-vous sûr ?',
    'source_column' => 'Texte source',
    'russian_column' => 'Texte russe',

    // Tableau
    'no_rows_yet' => "Aucune ligne pour l'instant. Cliquez sur « Ajouter une ligne » pour en créer une.",
    'add_row' => 'Ajouter une ligne',

    // Actions sur les lignes (infobulles des boutons)
    'edit_source_text' => 'Modifier le texte source',
    'edit_russian_text' => 'Modifier le texte russe',
    'open_audio_player' => 'Ouvrir le lecteur audio',
    'translate_with_chatgpt' => 'Traduire avec ChatGPT',
    'retranslate_with_chatgpt' => 'Régénérer la traduction avec ChatGPT',
    'fix_stress_marks' => 'Corriger les accents avec ChatGPT',
    'delete_row' => 'Supprimer la ligne',

    // Fenêtre TTS
    'generated_at' => 'Généré le :date',
    'no_audio_yet' => 'Aucun fichier audio généré pour cette phrase.',
    'regenerate' => 'Régénérer',
    'generate_audio' => "Générer l'audio",
    'close' => 'Fermer',

    // Panneau d'import
    'upload_heading' => 'Importer un fichier CSV',
    'upload_subtext' => "Sélectionnez un fichier .csv pour commencer l'édition.",
    'dropzone_heading' => 'Déposez votre CSV ici',
    'dropzone_text' => 'ou cliquez pour parcourir',
    'parsing' => 'Traitement…',

    // Actions groupées
    'bulk_actions' => 'Actions groupées',
    'bulk_stress_title' => 'Corriger les accents manquants',
    'bulk_tts_title' => 'Générer les audios manquants',
    'bulk_done' => 'Terminé',
    'bulk_running' => 'En cours…',
    'bulk_last_run' => 'Dernier lancement',
    'bulk_rows_need_stress' => ':count phrase à corriger|:count phrases à corriger',
    'bulk_rows_missing_audio' => ':count phrase sans audio|:count phrases sans audio',
    'bulk_all_stress_done' => 'Tous les accents sont déjà en place.',
    'bulk_all_audio_done' => 'Tous les fichiers audio sont déjà générés.',
    'bulk_failed' => ':count échoué(s)',
    'bulk_run_stress' => 'Corriger :count phrase|Corriger :count phrases',
    'bulk_run_tts' => 'Générer :count fichier audio|Générer :count fichiers audio',
    'bulk_report_corrected' => ':count corrigé(s)',
    'bulk_report_tokens' => ':input entrée / :output sortie tokens (exact API)',
    'bulk_report_generated' => ':count généré(s)',
    'bulk_report_chars' => ':chars caractères (exact API)',
    'bulk_widget_details' => 'Détails',
    'bulk_widget_tokens' => 'tokens',
    'bulk_widget_chars' => 'caractères',
    'bulk_stress_in_progress' => 'Correction groupée des accents en cours',
    'bulk_tts_in_progress' => 'Génération audio groupée en cours',
    'bulk_estimate_tokens' => '~:input entrée / :output sortie tokens (≈ $:cost) · :model',
    'bulk_estimate_chars' => '~:chars caractères (≈ $:cost) · :model',
    'bulk_warning_queue' => "Le driver de file d'attente est réglé sur 'sync'. Les traitements groupés bloqueront la requête au lieu de s'exécuter en arrière-plan.",
    'bulk_warning_broadcast' => "Le driver de broadcast est réglé sur 'log'. Les mises à jour en temps réel ne seront pas disponibles.",

    // Messages de validation / erreur
    'error_cannot_read_file' => 'Impossible de lire le fichier importé.',
    'error_csv_empty_or_malformed' => 'Le fichier CSV semble vide ou malformé.',
    'error_audio_generation_failed' => 'Échec de la génération audio : :message',
    'error_rate_limit' => 'Trop de requêtes. Veuillez patienter un moment avant de réessayer.',
];
