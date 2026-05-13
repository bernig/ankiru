<?php

return [
    'title' => 'FAQ',
    'seo_description' => 'Questions fréquentes sur Ankiru : fiabilité de l\'IA, correction des accents toniques, clé API, coûts, export Anki et plus encore.',
    'subtitle' => 'Les questions les plus courantes, avec des réponses honnêtes.',

    // Section IA & Qualité
    'section_ai_title' => 'IA & qualité',

    'q_ai_reliable' => 'Les phrases générées par l\'IA sont-elles fiables ?',
    'a_ai_reliable' => 'L\'IA produit généralement des phrases naturelles et correctes, mais elle n\'est pas infaillible. GPT peut parfois introduire des formulations maladroites ou légèrement inexactes, en particulier sur des sujets spécialisés. Traitez les résultats comme un excellent point de départ et relisez-les avant d\'en faire vos cartes définitives.',

    'q_stress_unreliable' => 'La correction automatique des accents ne fonctionne pas toujours. Pourquoi ?',
    'a_stress_unreliable' => 'Les accents toniques russes (ударения) sont particulièrement complexes : leur position peut changer selon le cas grammatical, la forme conjuguée ou le registre. Même les Russes natifs font parfois des erreurs. Le modèle GPT fait de son mieux, mais peut se tromper sur des mots rares, des formes verbales atypiques ou des homophones. Si un accent vous semble incorrect, vous pouvez le corriger manuellement directement dans la cellule du tableau.',

    'q_translation_quality' => 'Les traductions sont-elles de bonne qualité ?',
    'a_translation_quality' => 'Pour des phrases de la vie courante, la qualité est généralement très bonne. GPT comprend le contexte et produit un russe naturel. Les limites apparaissent surtout pour des expressions très idiomatiques, du jargon technique ou des calembours. Là encore, une relecture rapide reste conseillée.',

    'q_tts_quality' => 'L\'audio généré est-il de bonne qualité ?',
    'a_tts_quality' => 'Oui. L\'API TTS d\'OpenAI produit un rendu très naturel avec une prononciation russe soignée. Pour économiser vos crédits, les fichiers audio sont mis en cache côté serveur : si une phrase identique a déjà été générée par un autre utilisateur, vous récupérez le fichier instantanément sans dépenser un seul crédit.',

    // Section Clé API & Coûts
    'section_api_title' => 'Clé API & coûts',

    'q_why_own_key' => 'Pourquoi dois-je fournir ma propre clé API OpenAI ?',
    'a_why_own_key' => 'L\'application est gratuite, mais les appels à l\'API d\'OpenAI ont un coût. Plutôt que de vous facturer un abonnement ou de revendre des crédits avec une marge, j\'ai fait le choix de vous laisser brancher votre propre clé. Vous payez directement OpenAI pour ce que vous consommez, sans intermédiaire.',

    'q_cost_estimate' => 'Combien ça coûte en pratique ?',
    'a_cost_estimate' => 'Pour un usage personnel normal (quelques dizaines de phrases et d\'audios), attendez-vous à quelques centimes d\'euros. La génération de texte et les corrections d\'accents coûtent très peu avec les modèles récents. Le TTS est légèrement plus cher à la minute, mais reste très abordable. Une estimation du coût est affichée avant chaque opération groupée.',

    'q_key_security' => 'Ma clé API est-elle en sécurité ?',
    'a_key_security' => 'Votre clé est chiffrée en base de données et n\'est jamais exposée dans le frontend ni dans les logs applicatifs. Vous pouvez la modifier ou la supprimer à tout moment depuis votre profil.',

    // Section Fonctionnement
    'section_usage_title' => 'Fonctionnement',

    'q_offline' => 'Puis-je utiliser l\'application sans connexion internet ?',
    'a_offline' => 'Partiellement. La navigation dans vos phrases et l\'écoute des fichiers audio déjà générés fonctionnent localement. En revanche, toutes les fonctions IA (génération, traduction, correction d\'accents, synthèse vocale) nécessitent un appel à l\'API d\'OpenAI et donc une connexion active.',

    'q_other_languages' => 'L\'application supporte-t-elle d\'autres langues que le russe ?',
    'a_other_languages' => 'Pour l\'instant, l\'application est spécialement conçue pour l\'apprentissage du russe. La fonctionnalité de correction des accents toniques est une particularité propre au russe, et les prompts envoyés à l\'IA sont calibrés pour cette langue. Un support d\'autres langues n\'est pas prévu à court terme.',

    'q_data_privacy' => 'Mes données sont-elles partagées avec des tiers ?',
    'a_data_privacy' => 'Vos phrases sont envoyées à l\'API d\'OpenAI pour être traitées : c\'est inhérent au fonctionnement de l\'outil. Aucune donnée personnelle n\'est vendue ni partagée à des fins commerciales. Consultez notre politique de confidentialité pour les détails.',

    // Section Export
    'section_export_title' => 'Export Anki',

    'q_how_export_works' => 'Comment fonctionne l\'export vers Anki ?',
    'a_how_export_works' => 'Vos phrases et fichiers audio sont empaquetés dans un fichier .apkg standard. Ouvrez ce fichier depuis l\'application Anki (ou double-cliquez dessus) pour importer toutes les cartes dans un deck. Le fichier contient les champs recto/verso ainsi que les fichiers audio liés.',

    'q_export_all_at_once' => 'Puis-je exporter tous mes fichiers en une seule fois ?',
    'a_export_all_at_once' => 'Oui. Utilisez l\'option « Exporter la collection » dans le menu d\'export. Elle regroupe tous vos decks dans un seul fichier .apkg, pratique pour une réinstallation d\'Anki ou pour partager votre collection.',

    'q_missing_audio_in_anki' => 'Il manque de l\'audio sur certaines cartes dans Anki. Que faire ?',
    'a_missing_audio_in_anki' => 'Vérifiez d\'abord que les fichiers audio ont bien été générés pour ces lignes dans l\'éditeur (l\'icône audio apparaît en gris si l\'audio est manquant). Une fois l\'audio généré, ré-exportez le paquet .apkg et réimportez-le dans Anki.',
];
