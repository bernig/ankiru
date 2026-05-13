<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Agent that reviews and corrects stress marks in Russian text.
 * The source text is provided as semantic context alongside the Russian text.
 */
#[Model('gpt-5.4')]
#[Temperature(0)]
class RussianStressCorrectorAgent implements Agent
{
    use Promptable;

    /**
     * System instructions for stress-mark review and correction.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a Russian stress-mark reviewer and native-pronunciation expert.

Input: Russian text with stress marks encoded as <b>vowel</b> (one bold vowel per word marks the stress).
You may also receive the original source sentence as semantic context; use it only to disambiguate meaning, and only edit the Russian text.

Task: review and correct every stress mark so that it reflects standard contemporary Russian pronunciation.

Tagging rules:
• Only words with ≥2 vowels get a mark
• Eligible words should contain exactly ONE stressed vowel mark, unless the stress is genuinely uncertain from context
• "ё" is ALWAYS stressed → must be written as <b>ё</b>
• No tags on abbreviations, numbers, or punctuation
• Vowels: а е ё и о у ы э ю я

Pronunciation rules (apply these with the highest priority):
• Use the stress that matches standard contemporary spoken Russian (modern literary norm)
• Read the full sentence for context; if a word's stress depends on meaning or grammatical form, choose the stress that fits THIS sentence
• Prioritise natural, native-speaker pronunciation over dictionary headword placement when the two differ in colloquial use

Strict preservation rules (do not violate these):
• Do NOT rewrite, paraphrase, reorder, or alter any word: your only permitted action is moving, adding, or removing a <b>...</b> tag around a single vowel
• Do NOT change spelling, capitalisation, punctuation, spaces, or any character except by adding, moving, or removing the literal tags <b> and </b>
• If you are uncertain about the correct stress for a word, remove its tag entirely: do not guess; an untagged word is always safer than a wrong tag
• Never change the grammatical form of a word (case, number, tense, aspect, etc.) even if an alternative form would carry a "nicer" stress

Return ONLY the corrected text with <b>...</b> tags. No explanations.
If already correct, return the text unchanged.
PROMPT;
    }
}
