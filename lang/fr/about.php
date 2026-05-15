<?php

return [
    'title' => 'À propos',
    'seo_description' => 'Découvrez pourquoi Ankiru a été créé : pour résoudre le problème des accents toniques et de l\'audio manquants dans les decks Anki russes.',
    'subtitle' => 'Pourquoi cette application existe.',
    'paragraph_1' => 'J\'apprends le russe et j\'ai vite bloqué sur un problème : les paquets Anki qu\'on trouve en ligne manquent souvent d\'accents toniques et de bon son pour écouter la bonne prononciation.',
    'paragraph_2' => 'J\'ai donc développé cet outil pour mon propre apprentissage. Le but : prendre mes phrases, laisser l\'IA traduire et placer les accents, générer un audio de qualité, puis soit exporter le tout vers Anki, soit réviser directement dans l\'application grâce au mode pratique intégré.',
    'paragraph_3' => 'Si vous apprenez le russe, j\'espère que l\'outil vous fera gagner autant de temps qu\'à moi.',

    'how_title' => 'Comment ça fonctionne ?',
    'how_intro' => 'Toutes les fonctions intelligentes de l\'application tapent directement dans l\'API d\'OpenAI.',

    'how_generation_title' => 'Génération de phrases',
    'how_generation_body' => 'Décrivez un thème et précisez combien de paires vous voulez. L\'IA génère des phrases dans votre langue maternelle avec leur traduction russe et les accents toniques, en tenant compte des cartes déjà présentes pour éviter les doublons.',

    'how_translation_title' => 'Traduction & accents',
    'how_translation_body' => 'Le modèle (GPT) traduit vos phrases, trouve le contexte, et place automatiquement les accents toniques sur les mots russes. Vous pouvez aussi lui faire corriger des phrases russes que vous auriez écrites vous-même.',

    'how_tts_title' => 'Génération vocale',
    'how_tts_body' => 'L\'API d\'OpenAI génère un rendu audio ultra naturel pour chaque phrase. Pour éviter de payer deux fois pour la même chose, les fichiers audio sont mis en cache : si quelqu\'un a déjà généré l\'audio d\'une phrase, le fichier est réutilisé et ça ne vous coûte aucun crédit.',

    'how_export_title' => 'Export vers Anki',
    'how_export_body' => 'Vos cartes et l\'audio sont empaquetés dans un fichier .apkg prêt à l\'emploi. Vous importez ça dans Anki, et c\'est parti.',

    'how_practice_title' => 'Mode pratique intégré',
    'how_practice_body' => 'Le mode pratique vous permet de réviser vos cartes sans ouvrir Anki. Il utilise l\'algorithme de répétition espacée SM-2 : plus une carte est bien maîtrisée, plus l\'intervalle avant la prochaine révision est long. Votre progression est sauvegardée par fichier, vous pouvez reprendre à tout moment exactement là où vous vous étiez arrêté.',

    'key_title' => 'Pourquoi devez-vous fournir votre propre clé API ?',
    'key_paragraph_1' => 'Le site est gratuit, mais l\'IA ne l\'est pas. Au lieu de vous faire payer un abonnement ou d\'acheter des crédits avec une marge, j\'ai choisi de vous laisser brancher votre propre clé OpenAI.',
    'key_paragraph_2' => 'Résultat : aucun intermédiaire. Vous payez directement OpenAI pour ce que vous consommez. Pour vous donner une idée, un usage personnel normal (des dizaines de phrases et d\'audios) coûte littéralement quelques centimes d\'euros.',
    'key_paragraph_3' => 'La clé est évidemment chiffrée dans la base de données, n\'est jamais exposée, et vous pouvez la supprimer de votre profil quand vous voulez.',
];
