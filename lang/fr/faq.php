<?php

return [
    'title' => 'FAQ',
    'seo_description' => 'Questions fréquentes sur Ankiru : fiabilité de l\'IA, correction des accents toniques, clé API, coûts, export Anki et plus encore.',
    'subtitle' => 'Les questions les plus fréquentes, en toute transparence.',

    // Section IA & Qualité
    'section_ai_title' => 'IA & qualité',

    'q_ai_reliable' => 'Les phrases générées par l\'IA sont-elles fiables ?',
    'a_ai_reliable' => 'L\'IA génère des phrases naturelles et correctes la plupart du temps, mais elle n\'est pas infaillible. Il arrive que GPT propose des tournures un peu lourdes ou imprécises, surtout sur des sujets pointus. Considérez plutôt ses suggestions comme une bonne base de travail, à relire avant de valider vos cartes définitives.',

    'q_stress_unreliable' => 'La correction automatique des accents ne fonctionne pas toujours. Pourquoi ?',
    'a_stress_unreliable' => 'L\'accentuation russe (ударение) est un vrai casse-tête : la place de l\'accent tonique varie selon la déclinaison, la conjugaison ou même le niveau de langue, et il n\'est pas rare que les Russes eux-mêmes se trompent. GPT s\'en sort bien en général, mais il trébuche parfois sur des mots rares, des formes complexes ou des homophones. Si un accent vous paraît suspect, vous pouvez simplement le corriger à la main dans le tableau. Petite astuce : écoutez l\'audio de la phrase ! La synthèse vocale se trompe beaucoup plus rarement sur les accents toniques, c\'est un excellent moyen de vérifier en cas de doute.',

    'q_translation_quality' => 'Les traductions sont-elles de bonne qualité ?',
    'a_translation_quality' => 'Pour le langage de tous les jours, c\'est du solide. GPT saisit bien le contexte et donne un russe fluide. Il montre ses limites sur les expressions très imagées, le jargon ultra-technique ou les jeux de mots. Un petit coup d\'œil rapide reste donc toujours utile.',

    'q_tts_quality' => 'L\'audio généré est-il de bonne qualité ?',
    'a_tts_quality' => 'Oui. La synthèse vocale d\'OpenAI offre un rendu bluffant et une prononciation soignée. Et pour vous faire faire des économies, le son est mis en cache sur nos serveurs : si quelqu\'un a déjà fait générer la même phrase, vous la récupérez instantanément, sans que ça ne vous coûte un centime.',

    // Section Clé API & Coûts
    'section_api_title' => 'Clé API & coûts',

    'q_why_own_key' => 'Pourquoi dois-je fournir ma propre clé API OpenAI ?',
    'a_why_own_key' => 'L\'application est gratuite, mais interroger l\'API d\'OpenAI coûte un peu d\'argent. Plutôt que d\'imposer un abonnement ou de revendre des crédits en prenant une marge, je préfère vous laisser utiliser votre propre clé. Comme ça, vous ne payez à OpenAI que ce que vous consommez.',

    'q_cost_estimate' => 'Combien ça coûte en pratique ?',
    'a_cost_estimate' => 'Pour un usage quotidien (quelques dizaines de phrases et d\'audios), ça se chiffre en centimes. Le texte et la correction d\'accents ne coûtent presque rien. La génération audio est un poil plus chère, mais ça reste très modeste. De toute façon, vous avez toujours une estimation avant de lancer un traitement par lots.',

    'q_key_security' => 'Ma clé API est-elle en sécurité ?',
    'a_key_security' => 'Votre clé est stockée sous forme chiffrée dans la base de données. Elle n\'apparaîtra d\'ailleurs jamais ni dans l\'interface, ni dans les journaux système. Et bien sûr, vous gardez la main pour la changer ou la supprimer quand vous voulez depuis votre profil.',

    'q_api_credits' => 'L\'application sait-elle combien de crédits OpenAI il me reste ?',
    'a_api_credits' => 'Non, pas du tout. Nous n\'avons aucun accès à votre compte OpenAI ou à son solde. Si vos générations commencent à échouer systématiquement, il se peut que votre solde de crédits soit épuisé. Il faudra alors vérifier directement sur votre tableau de bord OpenAI.',

    // Section Fonctionnement
    'section_usage_title' => 'Fonctionnement',

    'q_offline' => 'Puis-je utiliser l\'application sans connexion internet ?',
    'a_offline' => 'Non. L\'application a besoin d\'une connexion internet pour fonctionner. Vos données et vos audios sont stockés en ligne. De plus tous les traitements IA nécessitent de contacter les serveurs d\'OpenAI, une connexion est donc indispensable.',

    'q_other_languages' => 'L\'application supporte-t-elle d\'autres langues que le russe ?',
    'a_other_languages' => 'Elle est pour l\'instant taillée sur mesure pour le russe. La gestion des accents toniques, en particulier, est un besoin assez spécifique, et les instructions qu\'on donne à l\'IA sont optimisées pour ce cas de figure. On n\'a pas prévu de l\'ouvrir à d\'autres langues dans l\'immédiat.',

    'q_data_privacy' => 'Mes données sont-elles partagées avec des tiers ?',
    'a_data_privacy' => 'Elles passent forcément par l\'API d\'OpenAI puisque c\'est le cœur du système. En revanche, aucune de vos données personnelles n\'est revendue ou partagée à d\'autres fins commerciales. Tout est détaillé dans notre politique de confidentialité.',

    // Section bases Anki
    'section_anki_title' => 'C\'est quoi Anki ?',

    'q_what_is_anki' => 'Je ne connais pas Anki. C\'est quoi ?',
    'a_what_is_anki' => 'Anki est une application de flashcards gratuite et open source, basée sur la répétition espacée — une méthode scientifiquement prouvée qui vous présente chaque carte juste avant que vous soyez sur le point de l\'oublier. Au lieu de tout réviser tous les jours, vous ne travaillez que ce qui en a vraiment besoin. Elle est très utilisée par les apprenants de langues, les étudiants en médecine, et tous ceux qui ont beaucoup de choses à mémoriser sur le long terme.',

    'q_download_anki' => 'Où puis-je télécharger Anki ?',
    'a_download_anki' => '<strong>Anki Desktop</strong> (Windows, macOS, Linux) est <a class="underline" href="https://apps.ankiweb.net" target="_blank" rel="noopener noreferrer">gratuit sur apps.ankiweb.net</a>. <strong>AnkiDroid</strong> pour Android est <a class="underline" href="https://play.google.com/store/apps/details?id=com.ichi2.anki" target="_blank" rel="noopener noreferrer">gratuit sur le Play Store</a>. <strong>AnkiMobile</strong> pour iPhone et iPad est <a class="underline" href="https://apps.apple.com/fr/app/ankimobile-flashcards/id373493387" target="_blank" rel="noopener noreferrer">disponible sur l\'App Store pour environ 30 €</a> — la seule version payante, dont les ventes financent les versions desktop et Android gratuites.',

    'q_need_anki' => 'Faut-il installer Anki pour utiliser Ankiru ?',
    'a_need_anki' => 'Non. Vous pouvez créer des cartes et les réviser directement avec le mode pratique intégré, sans rien installer. Anki (ou AnkiDroid) n\'est utile que si vous souhaitez synchroniser vos cartes sur votre téléphone avec le système de sync d\'Anki, ou étendre une collection Anki existante.',

    'q_import_apkg' => 'Comment importer le fichier .apkg dans Anki ?',
    'a_import_apkg' => 'Sur le bureau, il suffit de double-cliquer sur le fichier .apkg — Anki s\'ouvre et importe tout automatiquement. Vous pouvez aussi passer par <em>Fichier → Importer</em> depuis Anki. Sur AnkiDroid, transférez le fichier sur votre appareil et appuyez dessus pour l\'ouvrir avec AnkiDroid. Dans les deux cas, vos cartes et vos fichiers audio arrivent directement dans votre collection.',

    // Section Export
    'section_export_title' => 'Export Anki',

    'q_how_export_works' => 'Comment fonctionne l\'export vers Anki ?',
    'a_how_export_works' => 'Vos phrases et audios sont emballés dans un fichier .apkg classique. Il suffit de l\'ouvrir directement dans Anki pour importer le vocabulaire. Toutes les cartes, avec leur audio, seront rangées proprement dans votre deck.',

    'q_export_all_at_once' => 'Puis-je exporter tous mes fichiers en une seule fois ?',
    'a_export_all_at_once' => 'Absolument. Il vous suffit d\'utiliser « Exporter la collection » depuis le menu. Vous obtiendrez un unique fichier .apkg qui compile tous vos paquets. L\'idéal si vous réinstallez Anki ou souhaitez partager votre travail.',

    'q_missing_audio_in_anki' => 'Il manque de l\'audio sur certaines cartes dans Anki. Que faire ?',
    'a_missing_audio_in_anki' => 'Un petit coup d\'œil dans l\'éditeur s\'impose : vérifiez si les audios de ces phrases existent bien (l\'icône est grise sinon). Une fois que c\'est généré, il n\'y a plus qu\'à réexporter le tout et relancer l\'import dans Anki pour mettre les cartes à jour.',
];
