<?php

namespace App\Services;

use App\Ai\Agents\RussianStressCorrectorAgent;
use App\Ai\Agents\SourceToRussianTranslatorAgent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Handles source-language → Russian translation and Russian stress-mark correction
 * via the Laravel AI SDK agents backed by OpenAI.
 */
class OpenAiTranslationService
{
    /**
     * Translate a source-language phrase to natural Russian with stress marks.
     *
     * @throws RuntimeException when the AI request fails.
     */
    public function translateSourceToRussian(string $sourceText): string
    {
        $sourceText = trim($sourceText);

        Log::debug('Translating source → Russian.', ['input' => Str::limit($sourceText, 120)]);

        $response = (new SourceToRussianTranslatorAgent)->prompt($sourceText);
        $result = trim((string) $response);

        Log::debug('Translation complete.', ['output' => Str::limit($result, 120)]);

        return $result;
    }

    /**
     * Review and correct stress marks in a Russian phrase.
     * The source text is sent as contextual hint only.
     *
     * @throws RuntimeException when the AI request fails.
     */
    public function correctRussianStressMarks(string $russianText, string $sourceContextText): string
    {
        $russianText = trim($russianText);
        $sourceContextText = trim($sourceContextText);

        Log::debug('Correcting Russian stress marks.', [
            'russian' => Str::limit($russianText, 120),
            'source_context' => Str::limit($sourceContextText, 120),
        ]);

        $input = <<<TEXT
Source text for meaning/context only:
---
{$sourceContextText}
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
