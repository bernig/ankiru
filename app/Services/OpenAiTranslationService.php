<?php

namespace App\Services;

use App\Ai\Agents\FrenchToRussianTranslatorAgent;
use App\Ai\Agents\RussianStressCorrectorAgent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Handles French → Russian translation and Russian stress-mark correction
 * via the Laravel AI SDK agents backed by OpenAI.
 */
class OpenAiTranslationService
{
    /**
     * Translate a French phrase to natural Russian with stress marks.
     *
     * @throws RuntimeException when the AI request fails.
     */
    public function translateFrenchToRussian(string $frenchText): string
    {
        $frenchText = trim($frenchText);

        Log::debug('Translating French → Russian.', ['input' => Str::limit($frenchText, 120)]);

        $response = (new FrenchToRussianTranslatorAgent)->prompt($frenchText);
        $result = trim((string) $response);

        Log::debug('Translation complete.', ['output' => Str::limit($result, 120)]);

        return $result;
    }

    /**
     * Review and correct stress marks in a Russian phrase.
     * The French original is sent as contextual hint only.
     *
     * @throws RuntimeException when the AI request fails.
     */
    public function correctRussianStressMarks(string $russianText, string $frenchContextText): string
    {
        $russianText = trim($russianText);
        $frenchContextText = trim($frenchContextText);

        Log::debug('Correcting Russian stress marks.', [
            'russian' => Str::limit($russianText, 120),
            'french_context' => Str::limit($frenchContextText, 120),
        ]);

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

        $response = (new RussianStressCorrectorAgent)->prompt($input);
        $result = trim((string) $response);

        Log::debug('Stress correction complete.', ['output' => Str::limit($result, 120)]);

        return $result;
    }
}
