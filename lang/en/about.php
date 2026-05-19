<?php

return [
    'title' => 'About',
    'seo_description' => 'Learn why Ankiru was built: to fix missing stress marks and audio in Russian Anki decks. Discover how it works under the hood.',
    'subtitle' => 'Why this app exists.',
    'paragraph_1' => 'As a French speaker learning Russian, I hit a wall pretty fast: most Anki decks you find online are missing correct stress marks, and getting good audio to nail the pronunciation is a hassle.',
    'paragraph_2' => 'So I built this to solve my own problem. It takes my phrases, uses AI to translate and add stress marks, generates high-quality audio, and either bundles everything into an .apkg file for Anki or lets me test the cards directly in the app.',
    'paragraph_3' => 'If you\'re learning Russian too, I hope this saves you as much time as it saves me.',

    'how_title' => 'How does it work under the hood?',
    'how_intro' => 'There\'s no magic here. All the smart features talk directly to OpenAI\'s API.',

    'how_generation_title' => 'Phrase generation',
    'how_generation_body' => 'Describe a topic and say how many pairs you want. The AI generates phrases in your native language with their Russian translation and stress marks, taking your existing cards into account to avoid duplicates.',

    'how_translation_title' => 'Translation & stress marks',
    'how_translation_body' => 'The model (GPT) translates your phrases into natural Russian and figures out where the stress marks belong. It can also fix stress marks on Russian text you type in yourself.',

    'how_tts_title' => 'Voice generation',
    'how_tts_body' => 'OpenAI\'s TTS API generates a highly natural voice file for each phrase. To save everyone tokens, audio files are cached. If someone else already generated audio for the exact same phrase, you get the file instantly for free.',

    'how_export_title' => 'Exporting to Anki',
    'how_export_body' => 'Your cards and audio are packed into a neat .apkg file. Just import it into Anki, and you\'re good to go.',

    'how_practice_title' => 'Built-in practice mode',
    'how_practice_body' => 'The practice mode lets you review your cards without opening Anki. It uses the SM-2 spaced repetition algorithm: the more easily you recall a card, the longer the app waits before showing it again. Your progress is stored per deck so you can pick up exactly where you left off.',

    'key_title' => 'Your own API key, or platform credits',
    'key_paragraph_1' => 'The app is free, but AI is not. The first option: plug in your own OpenAI key. You pay OpenAI directly for exactly what you use, with no markup or middlemen. For standard personal use, your bill will literally be a few cents.',
    'key_paragraph_2' => 'Coming soon: a second option. You\'ll be able to buy credit packs directly on the platform, without needing to create an OpenAI account or manage an API key yourself.',
    'key_paragraph_3' => 'Either way, your data stays protected. API keys are stored encrypted and are never exposed. You can update or delete yours from your profile anytime.',
];
