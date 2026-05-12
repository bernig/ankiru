<?php

namespace App\Services;

use App\Enums\OperationType;
use App\Jobs\MassOperationJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Orchestrates bulk operations over all CSV rows.
 *
 * Responsibilities:
 *   - Estimate token / character counts and costs before a batch starts.
 *   - Initialise cache progress keys and dispatch one MassOperationJob per
 *     qualifying row.
 *   - Expose current batch progress for the Livewire component to read on
 *     mount (e.g. after a page refresh mid-run).
 */
class MassOperationService
{
    /** System-prompt token count for RussianStressCorrectorAgent (measured). */
    private const int STRESS_SYSTEM_PROMPT_TOKENS = 420;

    /**
     * Conservative chars-to-tokens ratio for mixed Latin/Cyrillic text.
     * GPT tokenises Cyrillic text at roughly 1 token per 2–3 characters;
     * 3.5 is a practical midpoint that avoids systematic under-estimation.
     */
    private const float CHARS_PER_TOKEN = 3.5;

    public function __construct(
        private readonly RussianAccentService $accentService,
        private readonly RussianTextToSpeechService $ttsService,
    ) {}

    // -------------------------------------------------------------------------
    // Stress-correction estimation
    // -------------------------------------------------------------------------
    /**
     * Count the rows that still need stress-mark correction.
     *
     * @param  array<int, array<int, string>>  $csvRows
     */
    public function countRowsMissingStress(array $csvRows): int
    {
        return count($this->collectStressRows($csvRows));
    }

    /**
     * Estimate input/output token counts and USD cost for the stress batch.
     *
     * @param  array<int, array<int, string>>  $csvRows
     * @return array{rowCount: int, inputTokens: int, outputTokens: int, estimatedCost: float}
     */
    public function estimateStressBatchCost(array $csvRows): array
    {
        $qualifyingRows = $this->collectStressRows($csvRows);
        $inputTokens = 0;
        $outputTokens = 0;
        foreach ($qualifyingRows as [, $sourceText, $russianText]) {
            $inputTokens += self::STRESS_SYSTEM_PROMPT_TOKENS
                + (int) ceil((mb_strlen($sourceText) + mb_strlen($russianText)) / self::CHARS_PER_TOKEN);
            $outputTokens += (int) ceil(mb_strlen($russianText) / self::CHARS_PER_TOKEN);
        }
        $inputPrice = (float) config('services.openai.gpt_5_4_input_price_per_million', 3.00);
        $outputPrice = (float) config('services.openai.gpt_5_4_output_price_per_million', 15.00);
        $estimatedCost = ($inputTokens / 1_000_000) * $inputPrice
            + ($outputTokens / 1_000_000) * $outputPrice;

        return [
            'rowCount' => count($qualifyingRows),
            'inputTokens' => $inputTokens,
            'outputTokens' => $outputTokens,
            'estimatedCost' => round($estimatedCost, 6),
        ];
    }

    // -------------------------------------------------------------------------
    // TTS estimation
    // -------------------------------------------------------------------------
    /**
     * Count the rows that are missing a cached audio file.
     *
     * @param  array<int, array<int, string>>  $csvRows
     */
    public function countRowsMissingAudio(array $csvRows): int
    {
        return count($this->collectTtsRows($csvRows));
    }

    /**
     * Estimate total character count and USD cost for the TTS batch.
     *
     * @param  array<int, array<int, string>>  $csvRows
     * @return array{rowCount: int, totalChars: int, estimatedCost: float}
     */
    public function estimateTtsBatchCost(array $csvRows): array
    {
        $qualifyingRows = $this->collectTtsRows($csvRows);
        $totalChars = 0;
        foreach ($qualifyingRows as [, , $russianText]) {
            $totalChars += mb_strlen($this->ttsService->normalizeForSpeech($russianText));
        }
        $pricePerMillion = (float) config('services.openai.tts_price_per_million_chars', 30.00);
        $estimatedCost = ($totalChars / 1_000_000) * $pricePerMillion;

        return [
            'rowCount' => count($qualifyingRows),
            'totalChars' => $totalChars,
            'estimatedCost' => round($estimatedCost, 6),
        ];
    }

    // -------------------------------------------------------------------------
    // Batch dispatching
    // -------------------------------------------------------------------------
    /**
     * Initialize cache progress keys and dispatch one stress-correction job
     * per qualifying row. Returns the number of jobs dispatched (0 if none).
     *
     * @param  array<int, array<int, string>>  $csvRows
     */
    public function dispatchStressBatch(array $csvRows, string $sessionId): int
    {
        $qualifyingRows = $this->collectStressRows($csvRows);
        if (empty($qualifyingRows)) {
            return 0;
        }
        $total = count($qualifyingRows);
        $this->initialiseCacheKeys($sessionId, OperationType::Stress, $total);
        $userId = Auth::id();

        foreach ($qualifyingRows as [$rowIndex, $sourceText, $russianText]) {
            MassOperationJob::dispatch(
                operationType: OperationType::Stress,
                sessionId: $sessionId,
                rowIndex: $rowIndex,
                totalRows: $total,
                sourceText: $sourceText,
                russianText: $russianText,
                userId: $userId,
            );
        }

        return $total;
    }

    /**
     * Initialise cache progress keys and dispatch one TTS-generation job
     * per qualifying row. Returns the number of jobs dispatched (0 if none).
     *
     * @param  array<int, array<int, string>>  $csvRows
     */
    public function dispatchTtsBatch(array $csvRows, string $sessionId): int
    {
        $qualifyingRows = $this->collectTtsRows($csvRows);
        if (empty($qualifyingRows)) {
            return 0;
        }
        $total = count($qualifyingRows);
        $this->initialiseCacheKeys($sessionId, OperationType::Tts, $total);
        $userId = Auth::id();

        foreach ($qualifyingRows as [$rowIndex, $sourceText, $russianText]) {
            MassOperationJob::dispatch(
                operationType: OperationType::Tts,
                sessionId: $sessionId,
                rowIndex: $rowIndex,
                totalRows: $total,
                sourceText: $sourceText,
                russianText: $russianText,
                userId: $userId,
            );
        }

        return $total;
    }

    // -------------------------------------------------------------------------
    // Progress reading
    // -------------------------------------------------------------------------
    /**
     * Read the current progress counters from cache for the given operation.
     *
     * @return array{status: string, total: int, processed: int, failed: int}
     */
    public function getOperationProgress(string $sessionId, OperationType $operationType): array
    {
        $prefix = "mass_op:{$sessionId}:{$operationType->value}";

        return [
            'status' => (string) Cache::get("{$prefix}:status", 'idle'),
            'total' => (int) Cache::get("{$prefix}:total", 0),
            'processed' => (int) Cache::get("{$prefix}:processed", 0),
            'failed' => (int) Cache::get("{$prefix}:failed", 0),
        ];
    }

    /**
     * Read the stress-batch completion report from cache.
     * Only meaningful after a stress batch has finished.
     *
     * @return array{corrected: int, promptTokens: int, completionTokens: int}
     */
    public function getStressReport(string $sessionId): array
    {
        $prefix = "mass_op:{$sessionId}:stress";

        return [
            'corrected' => (int) Cache::get("{$prefix}:corrected", 0),
            'promptTokens' => (int) Cache::get("{$prefix}:prompt_tokens", 0),
            'completionTokens' => (int) Cache::get("{$prefix}:completion_tokens", 0),
        ];
    }

    /**
     * Read the TTS-batch completion report from cache.
     * Only meaningful after a TTS batch has finished.
     *
     * @return array{generated: int, actualChars: int}
     */
    public function getTtsReport(string $sessionId): array
    {
        $prefix = "mass_op:{$sessionId}:tts";

        return [
            'generated' => (int) Cache::get("{$prefix}:generated", 0),
            'actualChars' => (int) Cache::get("{$prefix}:actual_chars", 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------
    /**
     * Collect rows where textNeedsStressCorrection() is true.
     *
     * @param  array<int, array<int, string>>  $csvRows
     * @return array<int, array{0: int, 1: string, 2: string}> [rowIndex, sourceText, russianText]
     */
    private function collectStressRows(array $csvRows): array
    {
        $qualifying = [];
        foreach ($csvRows as $rowIndex => $row) {
            $russianText = $row[1] ?? '';
            if ($this->accentService->textNeedsStressCorrection($russianText)) {
                $qualifying[] = [$rowIndex, $row[0] ?? '', $russianText];
            }
        }

        return $qualifying;
    }

    /**
     * Collect rows whose Russian phrase has no cached audio file.
     *
     * Uses audioFilesExistBatch() for a single filesystem scan instead of
     * one Storage::exists() call per row.
     *
     * @param  array<int, array<int, string>>  $csvRows
     * @return array<int, array{0: int, 1: string, 2: string}> [rowIndex, sourceText, russianText]
     */
    private function collectTtsRows(array $csvRows): array
    {
        $russianPhrases = [];
        foreach ($csvRows as $rowIndex => $row) {
            $russianText = $row[1] ?? '';
            if (! empty(trim($russianText))) {
                $russianPhrases[$rowIndex] = $russianText;
            }
        }
        if (empty($russianPhrases)) {
            return [];
        }
        $existenceMap = $this->ttsService->audioFilesExistBatch(array_values($russianPhrases));
        $qualifying = [];
        foreach ($russianPhrases as $rowIndex => $russianText) {
            if (! ($existenceMap[$russianText] ?? false)) {
                $qualifying[] = [$rowIndex, $csvRows[$rowIndex][0] ?? '', $russianText];
            }
        }

        return $qualifying;
    }

    /**
     * Write the initial cache keys for a fresh batch so progress starts at zero.
     */
    private function initialiseCacheKeys(string $sessionId, OperationType $operationType, int $total): void
    {
        $prefix = "mass_op:{$sessionId}:{$operationType->value}";
        Cache::forget("{$prefix}:cancelled");
        Cache::put("{$prefix}:status", 'running', ttl: 3600);
        Cache::put("{$prefix}:total", $total, ttl: 3600);
        Cache::put("{$prefix}:processed", 0, ttl: 3600);
        Cache::put("{$prefix}:failed", 0, ttl: 3600);

        // Stress-only counters for the post-batch report.
        if ($operationType === OperationType::Stress) {
            Cache::put("{$prefix}:corrected", 0, ttl: 3600);
            Cache::put("{$prefix}:prompt_tokens", 0, ttl: 3600);
            Cache::put("{$prefix}:completion_tokens", 0, ttl: 3600);
        }

        // TTS-only counters for the post-batch report.
        if ($operationType === OperationType::Tts) {
            Cache::put("{$prefix}:generated", 0, ttl: 3600);
            Cache::put("{$prefix}:actual_chars", 0, ttl: 3600);
        }
    }
}
