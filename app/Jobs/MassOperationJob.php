<?php

namespace App\Jobs;

use App\Enums\OperationType;
use App\Enums\UsageOperation;
use App\Events\MassOperationProgressEvent;
use App\Models\User;
use App\Services\CreditService;
use App\Services\MassOperationService;
use App\Services\OpenAiTranslationService;
use App\Services\RussianAccentService;
use App\Services\RussianTextToSpeechService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\AiManager;
use RuntimeException;
use Throwable;

/**
 * Processes a single CSV row as part of a bulk operation (stress correction or
 * TTS audio generation). One job is dispatched per qualifying row so that:
 *   - each row gets independent error handling;
 *   - progress can be tracked atomically row by row;
 *   - a failed row does not block the rest of the batch.
 *
 * Row content is snapshotted in the constructor at dispatch time to avoid
 * any race condition with in-flight editor updates during a running batch.
 */
class MassOperationJob implements ShouldQueue
{
    use Queueable;

    /** No retries: failed rows are silently skipped; the user can re-run. */
    public int $tries = 1;

    /** 60 seconds is ample for a single AI or TTS API call. */
    public int $timeout = 60;

    /** User the job runs for, loaded at the start of handle(). */
    private ?User $user = null;

    /** True when this job's AI call is paid with platform credits. */
    private bool $billsPlatformCredits = false;

    /**
     * @param  OperationType  $operationType  The type of bulk operation to perform.
     * @param  string  $batchId  Per-batch UUID, scopes the cache keys of this batch.
     * @param  int  $rowIndex  Original CSV row index (0-based).
     * @param  int  $totalRows  Total jobs dispatched for this batch (used to detect completion).
     * @param  string  $sourceText  Column 0 snapshot (French source phrase).
     * @param  string  $russianText  Column 1 snapshot (Russian phrase, may contain <b> stress tags).
     * @param  int|null  $userId  Authenticated user ID for usage logging.
     */
    public function __construct(
        public readonly OperationType $operationType,
        public readonly string $batchId,
        public readonly int $rowIndex,
        public readonly int $totalRows,
        public readonly string $sourceText,
        public readonly string $russianText,
        public readonly ?int $userId = null,
        public readonly bool $forceDebugError = false,
    ) {}

    /**
     * Execute the job. Delegates to the correct handler based on operationType.
     *
     * Exceptions from the operation handler are caught here and recorded as
     * failures so that one failing row never stops the rest of the batch.
     * This is especially important with the `sync` queue driver, where an
     * uncaught exception from handle() is re-thrown by the driver and would
     * abort the entire dispatch loop in MassOperationService.
     */
    public function handle(
        RussianAccentService $accentService,
        OpenAiTranslationService $translationService,
        RussianTextToSpeechService $ttsService,
        CreditService $creditService,
        MassOperationService $massOperationService,
        AiManager $aiManager,
    ): void {
        if (Cache::get($this->cacheKey('cancelled'))) {
            return;
        }

        if (! $this->applyUserApiKey($aiManager)) {
            $this->stopBatchForInsufficientCredits($massOperationService);

            return;
        }

        try {
            if ($this->forceDebugError) {
                sleep(1);
                throw new RuntimeException('[Debug] Simulated AI error.');
            }

            match ($this->operationType) {
                OperationType::Stress => $this->handleStressCorrection($accentService, $translationService, $creditService),
                OperationType::Tts => $this->handleTtsGeneration($ttsService, $creditService),
            };
        } catch (Throwable $exception) {
            Log::error('MassOperationJob row failed.', [
                'operationType' => $this->operationType,
                'rowIndex' => $this->rowIndex,
                'batchId' => $this->batchId,
                'error' => $exception->getMessage(),
            ]);

            Cache::increment($this->cacheKey('failed'));
        }

        // Always increment processed (success or failure) so the batch total
        // is eventually reached and the status transitions to 'done'.
        Cache::increment($this->cacheKey('processed'));

        $this->broadcastProgress();
    }

    /**
     * Called by Laravel for external failures (e.g. job timeout, worker crash)
     * that bypass handle() entirely. Ensures the batch counters stay consistent
     * in those edge cases.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('MassOperationJob failed externally.', [
            'operationType' => $this->operationType,
            'rowIndex' => $this->rowIndex,
            'batchId' => $this->batchId,
            'error' => $exception->getMessage(),
        ]);

        Cache::increment($this->cacheKey('failed'));
        Cache::increment($this->cacheKey('processed'));

        $this->broadcastProgress();
    }

    // -------------------------------------------------------------------------
    // Operation handlers
    // -------------------------------------------------------------------------

    /**
     * Ask ChatGPT to correct stress marks for the Russian phrase in this row.
     * Stores the result in a per-row cache key so the Livewire component can
     * merge all results into $csvRows when the batch completes.
     * Also accumulates the exact API token usage in shared cache counters.
     */
    private function handleStressCorrection(
        RussianAccentService $accentService,
        OpenAiTranslationService $translationService,
        CreditService $creditService,
    ): void {
        // Skip rows that no longer need correction (e.g. edited manually after dispatch).
        if (! $accentService->textNeedsStressCorrection($this->russianText)) {
            return;
        }

        $result = $translationService->correctRussianStressMarksWithUsage(
            $this->russianText,
            $this->sourceText,
        );

        // Accumulate exact API token usage atomically (integers, so increment is safe).
        Cache::increment($this->cacheKey('prompt_tokens'), $result['promptTokens']);
        Cache::increment($this->cacheKey('completion_tokens'), $result['completionTokens']);

        // Count successfully corrected rows (distinct from total processed).
        Cache::increment($this->cacheKey('corrected'));

        // Store the corrected text with the text it was computed from, so the
        // Livewire component only applies it to a row that still holds that
        // exact text (rows may have been edited, deleted or reordered meanwhile).
        Cache::put(
            $this->cacheKey("row:{$this->rowIndex}"),
            ['original' => $this->russianText, 'corrected' => $result['text']],
            ttl: 3600,
        );

        if ($this->user !== null && $result['promptTokens'] > 0) {
            $creditService->recordTokenUsage(
                $this->user,
                UsageOperation::StressCorrection,
                $result['promptTokens'],
                $result['completionTokens'],
                billCredits: $this->billsPlatformCredits,
            );
        }
    }

    /**
     * Generate (or retrieve from cache) the TTS audio file for this row's
     * Russian phrase. The file lands on disk via RussianTextToSpeechService.
     * Accumulates the exact character count and generated-file count in shared
     * cache counters for post-batch reporting.
     */
    private function handleTtsGeneration(RussianTextToSpeechService $ttsService, CreditService $creditService): void
    {
        $normalizedText = $ttsService->normalizeForSpeech($this->russianText);

        if (empty($normalizedText)) {
            return;
        }

        $isNewGeneration = $ttsService->generateAudio($this->russianText);

        if ($isNewGeneration) {
            $charCount = mb_strlen($normalizedText);
            Cache::increment($this->cacheKey('actual_chars'), $charCount);
            Cache::increment($this->cacheKey('generated'));

            if ($this->user !== null) {
                $creditService->recordTtsUsage($this->user, $charCount, billCredits: $this->billsPlatformCredits);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Setup
    // -------------------------------------------------------------------------

    /**
     * Jobs bypass HTTP middleware, so the user's per-account OpenAI key is not
     * injected automatically. Load it from the database and set the config so
     * that all AI/TTS service calls in this job use the correct key.
     *
     * The key is always written explicitly (empty when unusable): a queue
     * worker is a long-lived process, so a conditional write would leave the
     * previous job's key active for the next user.
     *
     * Returns false when the user can no longer be billed: no credits and no
     * personal key, or a batch started on credits whose balance is now 0.
     */
    private function applyUserApiKey(AiManager $aiManager): bool
    {
        if ($this->userId === null) {
            Log::warning('MassOperationJob: userId is null, cannot apply API key.');
        }

        $this->user = $this->userId === null ? null : User::find($this->userId);
        $apiKey = $this->user?->resolveOpenAiKey();
        $this->billsPlatformCredits = $this->user?->usesPlatformCredits() ?? false;

        config(['ai.providers.openai.key' => $apiKey ?? '']);
        $aiManager->forgetInstance();

        if ($this->user === null) {
            return true;
        }

        $batchStartedOnCredits = Cache::get($this->cacheKey('billing')) === 'credits';

        return $apiKey !== null && ($this->billsPlatformCredits || ! $batchStartedOnCredits);
    }

    /**
     * Stop the whole batch: remaining jobs are cancelled and the batch is
     * reported as done so the editor merges the rows already processed and
     * tells the user why the rest was skipped.
     */
    private function stopBatchForInsufficientCredits(MassOperationService $massOperationService): void
    {
        $massOperationService->cancelBatch($this->batchId, $this->operationType);
        Cache::put($this->cacheKey('stopped'), 'insufficient_credits', ttl: 3600);

        $this->broadcastProgress(forceDone: true);
    }

    // -------------------------------------------------------------------------
    // Progress tracking
    // -------------------------------------------------------------------------

    /**
     * Read the current batch counters and broadcast a progress event.
     * When all rows have been processed, sets the status to 'done'.
     */
    private function broadcastProgress(bool $forceDone = false): void
    {
        $counters = Cache::many([$this->cacheKey('processed'), $this->cacheKey('failed')]);
        $processedCount = (int) ($counters[$this->cacheKey('processed')] ?? 0);
        $failedCount = (int) ($counters[$this->cacheKey('failed')] ?? 0);
        $isDone = $forceDone || $processedCount >= $this->totalRows;

        if ($isDone) {
            Cache::put($this->cacheKey('status'), 'done', ttl: 3600);
        }

        // Progress is broadcast on the user's private channel; without a user
        // there is no one to notify (the editor's polling still catches up).
        if ($this->userId === null) {
            return;
        }

        MassOperationProgressEvent::dispatch(
            $this->operationType->value,
            $this->batchId,
            $this->userId,
            $isDone ? 'done' : 'running',
            $processedCount,
            $this->totalRows,
            $failedCount,
        );
    }

    /**
     * Cache key of this job's batch state; see MassOperationService::batchCacheKey().
     */
    private function cacheKey(string $suffix): string
    {
        return MassOperationService::batchCacheKey($this->batchId, $this->operationType, $suffix);
    }
}
