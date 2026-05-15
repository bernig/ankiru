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
    public function __construct(private readonly RussianAccentService $accentService) {}

    /**
     * Translate a source-language phrase to Russian, returning the text and
     * exact token usage reported by the API.
     *
     * @return array{text: string, promptTokens: int, completionTokens: int}
     *
     * @throws RuntimeException when the AI request fails.
     */
    public function translateSourceToRussianWithUsage(string $sourceText): array
    {
        $sourceText = trim($sourceText);

        $this->throwIfDebugErrorForced();

        Log::debug('Translating source → Russian.', ['input' => Str::limit($sourceText, 120)]);

        $response = (new SourceToRussianTranslatorAgent)->prompt($sourceText);
        $result = trim($response->text);

        Log::debug('Translation complete.', ['output' => Str::limit($result, 120)]);

        return [
            'text' => $result,
            'promptTokens' => $response->usage->promptTokens,
            'completionTokens' => $response->usage->completionTokens,
        ];
    }

    /**
     * Review and correct stress marks, returning the corrected text and exact
     * token usage reported by the API.
     *
     * Strategy:
     *   1. Deterministically tag any bare ё first (always correct, no AI needed).
     *   2. If the text no longer needs correction after that, return immediately
     *      with zero token usage, saving an unnecessary API call.
     *   3. Otherwise pass the pre-normalised text to the AI, then apply
     *      normalisation again on the result as a safety net.
     *
     * @return array{text: string, promptTokens: int, completionTokens: int}
     *
     * @throws RuntimeException when the AI request fails.
     */
    public function correctRussianStressMarksWithUsage(string $russianText, string $sourceContextText): array
    {
        $russianText = trim($russianText);
        $sourceContextText = trim($sourceContextText);

        $this->throwIfDebugErrorForced();

        Log::debug('Correcting Russian stress marks.', [
            'russian' => Str::limit($russianText, 120),
            'source_context' => Str::limit($sourceContextText, 120),
        ]);

        // Step 1: fix bare ё without touching the AI.
        $preNormalized = $this->accentService->normalizeYoAccent($russianText);

        // Step 2: if normalization alone resolved all issues, skip the AI call.
        if (! $this->accentService->textNeedsStressCorrection($preNormalized)) {
            Log::debug('Stress correction resolved by ё normalisation; AI call skipped.', [
                'output' => Str::limit($preNormalized, 120),
            ]);

            return [
                'text' => $preNormalized,
                'promptTokens' => 0,
                'completionTokens' => 0,
            ];
        }

        // Step 3: remaining issues need AI; pass the pre-normalised text so the
        // model starts with ё already correctly tagged.
        $input = <<<TEXT
Source text for meaning/context only:
---
{$sourceContextText}
---

Russian text to review and correct stress marks in:
---
{$preNormalized}
---
TEXT;

        $response = (new RussianStressCorrectorAgent)->prompt($input);
        $result = trim($response->text);

        // Safety net: re-apply normalisation in case the AI untagged any ё.
        $result = $this->accentService->normalizeYoAccent($result);

        Log::debug('Stress correction complete.', ['output' => Str::limit($result, 120)]);

        return [
            'text' => $result,
            'promptTokens' => $response->usage->promptTokens,
            'completionTokens' => $response->usage->completionTokens,
        ];
    }

    private function throwIfDebugErrorForced(): void
    {
        if (config('app.debug') && session('debug_force_ai_error')) {
            sleep(1);
            throw new RuntimeException('[Debug] Simulated AI error.');
        }
    }
}
