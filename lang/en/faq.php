<?php

return [
    'title' => 'FAQ',
    'seo_description' => 'Frequently asked questions about Ankiru: AI reliability, stress mark correction, API key, costs, Anki export, and more.',
    'subtitle' => 'Straight answers to your most common questions.',

    // Section AI & Quality
    'section_ai_title' => 'AI & quality',

    'q_ai_reliable' => 'Are the AI-generated phrases reliable?',
    'a_ai_reliable' => 'Most of the time, the AI comes up with natural and accurate phrases, but it\'s not completely foolproof. GPT can sometimes spit out slightly awkward or imprecise wording, especially with niche topics. Treat its suggestions as a really good starting point, and give them a quick read-through before saving your cards.',

    'q_stress_unreliable' => 'The automatic stress marks don\'t always work. Why is that?',
    'a_stress_unreliable' => 'Russian stress marks (ударения) are a real headache: they move around depending on grammar, verb forms, and even context. Honestly, native speakers get them wrong sometimes too. GPT does a solid job overall, but it can trip up on rare words, weird conjugations, or homographs. If a stress mark looks wrong, you can just click the table cell and fix it yourself. Pro tip: generate and listen to the audio for the phrase! The text-to-speech engine is usually much more accurate with stress marks, making it a great way to double-check.',

    'q_translation_quality' => 'How good are the translations?',
    'a_translation_quality' => 'For everyday language, they\'re spot on. GPT understands context well and writes fluid Russian. It mostly struggles with heavy slang, ultra-specific jargon, or wordplay. Taking a quick glance over the translations is always a good idea.',

    'q_tts_quality' => 'Does the generated audio sound good?',
    'a_tts_quality' => 'Definitely. OpenAI\'s text-to-speech sounds incredibly natural, with great pronunciation. To save you some money, we also cache the audio files on our servers. If someone else has already generated audio for the exact same phrase, you get it instantly for free.',

    // Section API Key & Costs
    'section_api_title' => 'API Key & costs',

    'q_why_own_key' => 'Why do I need to bring my own OpenAI API key?',
    'a_why_own_key' => 'The app itself is free to use, but querying OpenAI\'s API costs money. Instead of charging a subscription or marking up the price of credits, I figured it\'d be fairer to let you plug in your own key. That way, you only pay OpenAI for exactly what you use.',

    'q_cost_estimate' => 'How much does it actually cost?',
    'a_cost_estimate' => 'For normal daily use (generating a few dozen phrases and audios), you\'re looking at pennies. Generating text and fixing stress marks costs almost nothing. Text-to-speech is a bit pricier, but still very cheap. Either way, you\'ll always see a cost estimate before running bulk operations.',

    'q_key_security' => 'Is my API key safe?',
    'a_key_security' => 'Yes. Your key is securely encrypted in the database and will never show up in the app interface or our server logs. Plus, you can change or delete it whenever you want right from your profile.',

    'q_api_credits' => 'Does the app know how many OpenAI credits I have left?',
    'a_api_credits' => 'No, not at all. We don\'t have any access to your OpenAI account or billing info. If your generations suddenly start failing, it might mean you\'ve run out of credits. You\'ll need to check your balance directly on your OpenAI dashboard.',

    // Section How it works
    'section_usage_title' => 'How it works',

    'q_offline' => 'Can I use the app offline?',
    'a_offline' => 'No. The app requires an active internet connection to work. Your phrases and audio files are stored online, and all the AI features obviously need to reach OpenAI\'s servers to run. Without internet, nothing will load.',

    'q_other_languages' => 'Does the app support other languages?',
    'a_other_languages' => 'Right now, it\'s tailor-made for Russian. Handling Russian stress marks is a pretty specific challenge, and our AI prompts are fine-tuned strictly for that. We don\'t have plans to add other languages in the near future.',

    'q_data_privacy' => 'Is my data shared with third parties?',
    'a_data_privacy' => 'Your phrases do have to be sent to OpenAI\'s API: that\'s just how the tool works. However, your personal data is never sold or shared with anyone else for marketing. You can check out our privacy policy for the full rundown.',

    // Section Anki basics
    'section_anki_title' => 'What is Anki?',

    'q_what_is_anki' => 'I\'ve never heard of Anki. What is it?',
    'a_what_is_anki' => 'Anki is a free, open-source flashcard app built around spaced repetition, a scientifically proven method that shows you each card just before you\'re about to forget it. Instead of reviewing everything every day, you only study what actually needs reviewing. It\'s widely used by language learners, medical students, and anyone who needs to retain a large volume of information for the long term.',

    'q_download_anki' => 'Where can I download Anki?',
    'a_download_anki' => '<strong>Anki Desktop</strong> (Windows, macOS, Linux) is <a class="underline" href="https://apps.ankiweb.net" target="_blank" rel="noopener noreferrer">free to download at apps.ankiweb.net</a>. <strong>AnkiDroid</strong> for Android is <a class="underline" href="https://play.google.com/store/apps/details?id=com.ichi2.anki" target="_blank" rel="noopener noreferrer">free on the Play Store</a>. <strong>AnkiMobile</strong> for iPhone and iPad is <a class="underline" href="https://apps.apple.com/us/app/ankimobile-flashcards/id373493387" target="_blank" rel="noopener noreferrer">a one-time $24.99 purchase on the App Store</a>, the only paid version, and its sales fund the free desktop and Android apps.',

    'q_need_anki' => 'Do I need to install Anki to use Ankiru?',
    'a_need_anki' => 'No. You can create cards and test them with the built-in test mode without ever installing Anki. Anki (or AnkiDroid) only becomes necessary if you want to sync your cards to your phone with Anki\'s own system, or extend an existing Anki collection you already have.',

    'q_import_apkg' => 'How do I import the .apkg file into Anki?',
    'a_import_apkg' => 'On desktop, just double-click the .apkg file: Anki opens and imports everything automatically. You can also go to <em>File → Import</em> from inside Anki. On AnkiDroid, transfer the file to your device and tap it to open it with AnkiDroid. Either way, your cards and audio land directly in your collection.',

    // Section Export
    'section_export_title' => 'Anki export',

    'q_how_export_works' => 'How does the Anki export work?',
    'a_how_export_works' => 'Your vocabulary and audio get neatly packed into a standard .apkg file. Just open it up in Anki, and it\'ll automatically import everything into your deck, with the front and back of the cards and all the audio attached.',

    'q_export_all_at_once' => 'Can I export everything in one go?',
    'a_export_all_at_once' => 'Absolutely. Just hit "Export collection" in the menu. You\'ll get one master .apkg file containing all your decks. It\'s perfect if you need to reinstall Anki or want to share your decks with someone else.',

    'q_missing_audio_in_anki' => 'Some cards in Anki are missing audio. What gives?',
    'a_missing_audio_in_anki' => 'Take a quick look at your phrases in the editor: if the audio icon is grey, the audio hasn\'t been generated yet. Just generate the missing audio, export the .apkg file again, and reimport it into Anki to update your cards.',
];
