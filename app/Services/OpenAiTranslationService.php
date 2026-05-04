<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Handles French → Russian translation and Russian stress-mark correction
 * via the OpenAI Responses API.
 *
 * Both operations share the same request/response pattern, so the HTTP call
 * is centralised in a single private method.
 */
class OpenAiTranslationService
{
    const TRANSLATION_PROMPT = <<<'PROMPT'
You are a professional French → Russian translator.

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

    const STRESS_CORRECTION_PROMPT = <<<'PROMPT'
You are a Russian stress-mark reviewer and native-pronunciation expert.

Input: Russian text with stress marks encoded as <b>vowel</b> (one bold vowel per word marks the stress).
You may also receive the original French source sentence as semantic context; use it only to disambiguate meaning, and only edit the Russian text.

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
• Do NOT rewrite, paraphrase, reorder, or alter any word — your only permitted action is moving, adding, or removing a <b>...</b> tag around a single vowel
• Do NOT change spelling, capitalisation, punctuation, spaces, or any character except by adding, moving, or removing the literal tags <b> and </b>
• If you are uncertain about the correct stress for a word, remove its tag entirely — do not guess; an untagged word is always safer than a wrong tag
• Never change the grammatical form of a word (case, number, tense, aspect, etc.) even if an alternative form would carry a "nicer" stress

Return ONLY the corrected text with <b>...</b> tags. No explanations.
If already correct, return the text unchanged.
PROMPT;

    /**
     * Translate a French phrase to natural Russian with stress marks.
     *
     * @throws RuntimeException when the API key is missing or the request fails.
     */
    public function translateFrenchToRussian(string $frenchText): string
    {
        return $this->callOpenAiResponsesApi(self::TRANSLATION_PROMPT, $frenchText);
    }

    /**
     * Review and correct stress marks in a Russian phrase.
     * The French original is sent as contextual hint only.
     *
     * @throws RuntimeException when the API key is missing or the request fails.
     */
    public function correctRussianStressMarks(string $russianText, string $frenchContextText): string
    {
        $input = <<<TEXT
French source for meaning/context only:
---
{$frenchContextText}
---

Russian text to review and correct stress marks in:
---
{$russianText}
---
TEXT;

        return $this->callOpenAiResponsesApi(self::STRESS_CORRECTION_PROMPT, $input);
    }

    /**
     * Send a request to the OpenAI Responses endpoint and return the text output.
     *
     * @throws RuntimeException when the API key is missing, the HTTP request fails,
     *                          or the response contains no usable text.
     */
    private function callOpenAiResponsesApi(string $systemPrompt, string $userInput): string
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
        }

        $model = config('services.openai.model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'input' => $userInput,
                'instructions' => $systemPrompt,
                'temperature' => 0,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'ChatGPT API returned an error: '.$response->status().'. Check your API key and quota.'
            );
        }

        $text = $response->json('output.0.content.0.text');

        if (! is_string($text) || empty(trim($text))) {
            throw new RuntimeException('ChatGPT returned an empty response.');
        }

        return trim($text);
    }
}
