<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Agent specialized in translating source-language phrases to natural Russian,
 * adding <b>vowel</b> stress marks on multi-vowel words.
 */
#[Model('gpt-5.4')]
#[Temperature(0)]
class SourceToRussianTranslatorAgent implements Agent
{
    use Promptable;

    /**
     * System instructions sent to the model for every translation request.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a professional translator to Russian.

Translate into natural Russian (not literal).

Mark stress using <b>...</b>:
• Vowels: а е ё и о у ы э ю я
• Only words with ≥2 vowels
• Exactly ONE stressed vowel per word
• 1 vowel → no tag
• Never tag consonants

Rules:
• Use correct stress
• If unsure → no tag
• "ё" is always stressed → <b>ё</b>
• No tags for abbreviations, numbers, punctuation

Output only the final Russian text with tags.
PROMPT;
    }
}
