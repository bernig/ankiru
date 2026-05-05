<?php

namespace App\Services;

use App\Ai\Agents\FrenchToRussianTranslatorAgent;
use App\Ai\Agents\RussianStressCorrectorAgent;
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
        $response = (new FrenchToRussianTranslatorAgent)->prompt($frenchText);

        return trim((string) $response);
    }

    /**
     * Review and correct stress marks in a Russian phrase.
     * The French original is sent as contextual hint only.
     *
     * @throws RuntimeException when the AI request fails.
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

        $response = (new RussianStressCorrectorAgent)->prompt($input);

        return trim((string) $response);
    }
}
