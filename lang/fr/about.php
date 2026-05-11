<?php

return [
    'title' => 'À propos',
    'subtitle' => 'L\'histoire derrière l\'outil.',
    'paragraph_1' => 'J\'apprends le russe en tant que francophone, et j\'ai vite été confronté à un problème concret : les cartes Anki disponibles manquent souvent d\'accents toniques corrects, et il est difficile de trouver des ressources audio de qualité pour apprendre à prononcer correctement.',
    'paragraph_2' => 'J\'ai donc créé cet outil pour répondre à mes propres besoins : traduire mes phrases, placer les accents toniques grâce à l\'IA, générer un audio de synthèse haute qualité pour chaque carte, et tout exporter en un fichier .apkg prêt à importer dans Anki.',
    'paragraph_3' => 'Si vous apprenez le russe vous aussi — que vous soyez francophone ou non — j\'espère que cet outil vous sera utile autant qu\'il l\'est pour moi.',

    'how_title' => 'Comment ça marche ?',
    'how_intro' => 'Toutes les fonctionnalités IA de ce site s\'appuient sur l\'API d\'OpenAI, appelée depuis le serveur à chaque traitement. Rien ne tourne en local.',

    'how_translation_title' => 'Traduction et correction des accents',
    'how_translation_body' => 'Un modèle de langage (GPT) traduit vos phrases sources en russe naturel et place automatiquement les accents toniques sur chaque mot multi-syllabique. Une seconde passe peut être déclenchée pour corriger un texte russe déjà saisi.',

    'how_tts_title' => 'Synthèse vocale',
    'how_tts_body' => 'L\'API TTS d\'OpenAI génère un fichier audio MP3 haute qualité pour chaque phrase russe. Les fichiers sont mis en cache côté serveur : si une phrase a déjà été générée par n\'importe quel utilisateur, l\'audio existant est réutilisé directement — aucun appel API n\'est effectué, ce qui économise des tokens pour tout le monde.',

    'how_export_title' => 'Export Anki',
    'how_export_body' => 'Les cartes et leurs fichiers audio sont empaquetés dans un fichier .apkg (format natif d\'Anki) que vous pouvez importer directement dans l\'application Anki sur n\'importe quel appareil.',

    'key_title' => 'Pourquoi faut-il sa propre clé API ?',
    'key_paragraph_1' => 'Ce site ne détient pas de clé API partagée. Chaque utilisateur connecte sa propre clé OpenAI, et c\'est elle qui est utilisée pour toutes ses requêtes. Vous consommez et payez directement votre propre quota — sans intermédiaire.',
    'key_paragraph_2' => 'Ce choix est délibéré : il vous garantit un contrôle total sur votre usage et vos coûts. Pour un usage personnel courant (quelques dizaines de traductions et d\'audios), la facture OpenAI reste de l\'ordre de quelques centimes.',
    'key_paragraph_3' => 'Votre clé est stockée de façon chiffrée en base de données, n\'est jamais exposée et n\'est utilisée que pour vos propres requêtes. Vous pouvez la renseigner, la modifier ou la supprimer à tout moment depuis votre profil.',
];
