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

    /**
     * Cache key pointing at the user's latest batch of the given type, so an
     * editor reloaded mid-run can find the batch UUID it lost.
     */
    public static function currentBatchCacheKey(int $userId, OperationType $operationType): string
    {
        return "mass_op:user:{$userId}:{$operationType->value}:current";
    }

    /**
     * Cache key holding the corrections of the user's last finished stress
     * batch, so the editor can revert them.
     */
    public static function stressUndoCacheKey(int $userId): string
    {
        return "mass_op:user:{$userId}:stress:undo";
    }

    /**
     * Cache key of one piece of a batch's state, shared by the service, the
     * jobs and the editor.
     *
     * Key pattern: mass_op:{batchId}:{operationType}:{suffix}
     * Example suffixes: 'processed', 'failed', 'status', 'row:42'
     */
    public static function batchCacheKey(string $batchId, OperationType $operationType, string $suffix): string
    {
        return "mass_op:{$batchId}:{$operationType->value}:{$suffix}";
    }

    // -------------------------------------------------------------------------
    // Stress-correction estimation
    // -------------------------------------------------------------------------
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
     * @param  int|null  $draftId  Draft the rows belong to (results are merged back into it).
     */
    public function dispatchStressBatch(array $csvRows, string $batchId, ?int $draftId = null): int
    {
        $qualifyingRows = $this->collectStressRows($csvRows);
        if (empty($qualifyingRows)) {
            return 0;
        }
        $total = count($qualifyingRows);
        $this->initialiseCacheKeys($batchId, OperationType::Stress, $total);

        // Remember which draft and rows this batch targets, so results are merged
        // into the right file even if the user switched files in the meantime.
        Cache::putMany([
            self::batchCacheKey($batchId, OperationType::Stress, 'draft_id') => $draftId,
            self::batchCacheKey($batchId, OperationType::Stress, 'row_indexes') => array_column($qualifyingRows, 0),
        ], 3600);
        $userId = Auth::id();

        $forceDebugError = config('app.debug') && session('debug_force_ai_error');

        foreach ($qualifyingRows as [$rowIndex, $sourceText, $russianText]) {
            MassOperationJob::dispatch(
                operationType: OperationType::Stress,
                batchId: $batchId,
                rowIndex: $rowIndex,
                totalRows: $total,
                sourceText: $sourceText,
                russianText: $russianText,
                userId: $userId,
                forceDebugError: $forceDebugError,
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
    public function dispatchTtsBatch(array $csvRows, string $batchId): int
    {
        $qualifyingRows = $this->collectTtsRows($csvRows);
        if (empty($qualifyingRows)) {
            return 0;
        }
        $total = count($qualifyingRows);
        $this->initialiseCacheKeys($batchId, OperationType::Tts, $total);
        $userId = Auth::id();

        $forceDebugError = config('app.debug') && session('debug_force_ai_error');

        foreach ($qualifyingRows as [$rowIndex, $sourceText, $russianText]) {
            MassOperationJob::dispatch(
                operationType: OperationType::Tts,
                batchId: $batchId,
                rowIndex: $rowIndex,
                totalRows: $total,
                sourceText: $sourceText,
                russianText: $russianText,
                userId: $userId,
                forceDebugError: $forceDebugError,
            );
        }

        return $total;
    }

    // -------------------------------------------------------------------------
    // Progress reading
    // -------------------------------------------------------------------------
    /**
     * Read the current progress counters from cache for the given operation.
     * Uses Cache::many() to fetch all four keys in a single round-trip.
     *
     * @return array{status: string, total: int, processed: int, failed: int}
     */
    public function getOperationProgress(string $batchId, OperationType $operationType): array
    {
        $key = fn (string $suffix): string => self::batchCacheKey($batchId, $operationType, $suffix);
        $values = Cache::many([
            $key('status'),
            $key('total'),
            $key('processed'),
            $key('failed'),
        ]);

        return [
            'status' => (string) ($values[$key('status')] ?? 'idle'),
            'total' => (int) ($values[$key('total')] ?? 0),
            'processed' => (int) ($values[$key('processed')] ?? 0),
            'failed' => (int) ($values[$key('failed')] ?? 0),
        ];
    }

    /**
     * Read the stress-batch completion report from cache.
     * Only meaningful after a stress batch has finished.
     * Uses Cache::many() to fetch all three keys in a single round-trip.
     *
     * @return array{corrected: int, promptTokens: int, completionTokens: int}
     */
    public function getStressReport(string $batchId): array
    {
        $key = fn (string $suffix): string => self::batchCacheKey($batchId, OperationType::Stress, $suffix);
        $values = Cache::many([
            $key('corrected'),
            $key('prompt_tokens'),
            $key('completion_tokens'),
        ]);

        return [
            'corrected' => (int) ($values[$key('corrected')] ?? 0),
            'promptTokens' => (int) ($values[$key('prompt_tokens')] ?? 0),
            'completionTokens' => (int) ($values[$key('completion_tokens')] ?? 0),
        ];
    }

    /**
     * Read the TTS-batch completion report from cache.
     * Only meaningful after a TTS batch has finished.
     * Uses Cache::many() to fetch both keys in a single round-trip.
     *
     * @return array{generated: int, actualChars: int}
     */
    public function getTtsReport(string $batchId): array
    {
        $key = fn (string $suffix): string => self::batchCacheKey($batchId, OperationType::Tts, $suffix);
        $values = Cache::many([
            $key('generated'),
            $key('actual_chars'),
        ]);

        return [
            'generated' => (int) ($values[$key('generated')] ?? 0),
            'actualChars' => (int) ($values[$key('actual_chars')] ?? 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Cancellation
    // -------------------------------------------------------------------------
    /**
     * Mark a batch as cancelled. Every job of the batch checks this flag at
     * the start of handle() and returns before any API call, so the jobs
     * still queued drain as no-ops.
     *
     * Pending jobs are deliberately not deleted from the queue: finding them
     * meant a LIKE scan over the whole jobs.payload column, tied to the
     * database queue driver.
     */
    public function cancelBatch(string $batchId, OperationType $operationType): void
    {
        // An empty ID would cancel a batch nobody can track.
        if ($batchId === '') {
            return;
        }

        // Outlives the other batch keys: queued jobs may wait for a worker a
        // long time, and they must still find the flag when they run.
        Cache::put(self::batchCacheKey($batchId, $operationType, 'cancelled'), true, ttl: now()->addWeek());
        Cache::put(self::batchCacheKey($batchId, $operationType, 'status'), 'done', ttl: 3600);
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
     * Uses audioFilesExistBatch(), which checks each distinct phrase once.
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
     * Uses Cache::putMany() to write all keys in a single round-trip.
     */
    private function initialiseCacheKeys(string $batchId, OperationType $operationType, int $total): void
    {
        $key = fn (string $suffix): string => self::batchCacheKey($batchId, $operationType, $suffix);
        Cache::forget($key('cancelled'));
        Cache::forget($key('stopped'));

        $typeCounters = match ($operationType) {
            OperationType::Stress => [
                $key('corrected') => 0,
                $key('prompt_tokens') => 0,
                $key('completion_tokens') => 0,
            ],
            OperationType::Tts => [
                $key('generated') => 0,
                $key('actual_chars') => 0,
            ],
        };

        Cache::putMany([
            $key('status') => 'running',
            $key('total') => $total,
            $key('processed') => 0,
            $key('failed') => 0,
            // Billing mode is fixed for the whole batch: a batch started on
            // credits stops when they run out instead of switching keys.
            $key('billing') => Auth::user()?->usesPlatformCredits() ? 'credits' : 'personal',
            ...$typeCounters,
        ], 3600);

        if (Auth::id() !== null) {
            Cache::put(self::currentBatchCacheKey(Auth::id(), $operationType), $batchId, 3600);
        }
    }
}
