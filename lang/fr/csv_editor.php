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

    // Messages de validation / erreur
    'error_cannot_read_file' => 'Impossible de lire le fichier import.',
    'error_csv_empty_or_malformed' => 'Le fichier CSV semble vide ou mal form.',
    'error_audio_generation_failed' => 'chec de la gnration audio: :message',
    'error_rate_limit' => 'Trop de requêtes. Veuillez patienter un moment avant de réessayer.',
];
