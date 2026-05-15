<?php

namespace App\Services;

use App\Ai\Agents\RowGeneratorAgent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Handles AI-based Anki flashcard row generation.
 */
class RowGenerationService
{
    /**
     * Generate phrase pairs via the AI.
     *
     * @param  string[]  $existingSourceTexts  Source phrases to include verbatim (when $willSendActualRows is true)
     * @return array{pairs: array<array{source: string, russian: string}>, promptTokens: int, completionTokens: int}
     *
     * @throws RuntimeException when the AI request fails or the response cannot be parsed.
     */
    public function generateRowPairs(
        string $prompt,
        int $count,
        string $context,
        bool $willSendActualRows,
        bool $willSendCountOnly,
        array $existingSourceTexts,
        int $existingCount,
    ): array {
        $userMessage = $this->buildUserMessage(
            prompt: $prompt,
            count: $count,
            context: $context,
            willSendActualRows: $willSendActualRows,
            willSendCountOnly: $willSendCountOnly,
            existingSourceTexts: $existingSourceTexts,
            existingCount: $existingCount,
        );

        if (config('app.debug') && session('debug_force_ai_error')) {
            sleep(1);
            throw new RuntimeException('[Debug] Simulated AI error.');
        }

        Log::debug('Generating flashcard row pairs.', [
            'count' => $count,
            'prompt' => Str::limit($prompt, 120),
        ]);

        $response = (new RowGeneratorAgent)->prompt($userMessage);
        $pairs = $this->parseJsonPairs(trim($response->text));

        Log::debug('Row generation complete.', ['pairs_count' => count($pairs)]);

        return [
            'pairs' => $pairs,
            'promptTokens' => $response->usage->promptTokens,
            'completionTokens' => $response->usage->completionTokens,
        ];
    }

    private function buildUserMessage(
        string $prompt,
        int $count,
        string $context,
        bool $willSendActualRows,
        bool $willSendCountOnly,
        array $existingSourceTexts,
        int $existingCount,
    ): string {
        $parts = ["GENERATE: {$count} flashcard pairs"];

        if ($context !== '') {
            $parts[] = "LEARNER CONTEXT:\n{$context}";
        }

        if ($willSendActualRows && $existingSourceTexts !== []) {
            $list = implode("\n", array_map(fn ($t) => "- {$t}", $existingSourceTexts));
            $parts[] = "EXISTING PHRASES (avoid duplicates and overly similar phrases):\n{$list}";
        } elseif ($willSendCountOnly && $existingCount > 0) {
            $parts[] = "NOTE: The student already has {$existingCount} existing phrases in this deck. "
                .'Generate phrases that complement a deck of that size; avoid very basic phrases likely already covered.';
        }

        $parts[] = "TOPIC / PROMPT:\n{$prompt}";

        return implode("\n\n", $parts);
    }

    /**
     * @return array<array{source: string, russian: string}>
     *
     * @throws RuntimeException
     */
    private function parseJsonPairs(string $raw): array
    {
        // Strip markdown code fences if the model wrapped the output.
        $json = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $json = preg_replace('/\s*```$/m', '', $json ?? $raw);
        $json = trim($json ?? $raw);

        // Extract the outermost JSON array in case of extra surrounding text.
        if (preg_match('/\[.*\]/s', $json, $matches)) {
            $json = $matches[0];
        }

        $decoded = json_decode($json, associative: true);

        if (! is_array($decoded)) {
            throw new RuntimeException('The AI returned an invalid response: '.$raw);
        }

        return collect($decoded)
            ->filter(fn ($item) => is_array($item) && isset($item['source'], $item['russian']))
            ->map(fn ($item) => [
                'source' => trim((string) $item['source']),
                'russian' => trim((string) $item['russian']),
            ])
            ->filter(fn ($item) => $item['source'] !== '' && $item['russian'] !== '')
            ->values()
            ->toArray();
    }
}
