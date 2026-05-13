<?php

return [
    'title' => 'About',
    'subtitle' => 'Why this app exists.',
    'paragraph_1' => 'As a French speaker learning Russian, I hit a wall pretty fast: most Anki decks you find online are missing correct stress marks, and getting good audio to nail the pronunciation is a hassle.',
    'paragraph_2' => 'So I built this to solve my own problem. It takes my phrases, uses AI to translate and add stress marks, generates high-quality audio, and bundles everything into an .apkg file I can dump straight into Anki.',
    'paragraph_3' => 'If you\'re learning Russian too, I hope this saves you as much time as it saves me.',

    'how_title' => 'How does it work under the hood?',
    'how_intro' => 'There\'s no magic here. All the smart features talk directly to OpenAI\'s API.',

    'how_translation_title' => 'Translation & stress marks',
    'how_translation_body' => 'The model (GPT) translates your phrases into natural Russian and figures out where the stress marks belong. It can also fix stress marks on Russian text you type in yourself.',

    'how_tts_title' => 'Voice generation',
    'how_tts_body' => 'OpenAI\'s TTS API generates a highly natural voice file for each phrase. To save everyone tokens, audio files are cached. If someone else already generated audio for the exact same phrase, you get the file instantly for free.',

    'how_export_title' => 'Exporting to Anki',
    'how_export_body' => 'Your cards and audio are packed into a neat .apkg file. Just import it into Anki, and you\'re good to go.',

    'key_title' => 'Why do you need your own API key?',
    'key_paragraph_1' => 'The app is free, but the AI is not. Instead of charging a monthly subscription or selling marked-up credits, I just ask you to plug in your own OpenAI key.',
    'key_paragraph_2' => 'This means no middlemen. You pay OpenAI exactly for what you use. For standard personal use (dozens of phrases and audios), your bill will literally be a few cents.',
    'key_paragraph_3' => 'Your key is securely encrypted in the database. It is never exposed, and you can delete it from your profile anytime.',
];
