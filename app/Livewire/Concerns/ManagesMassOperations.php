<?php

namespace App\Livewire\Concerns;

use App\Services\MassOperationService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

/**
 * Manages the "Bulk Actions" feature for the CsvEditor component.
 *
 * Provides two batch operations (stress-mark correction and TTS audio
 * generation) that run in the background via queued jobs. Real-time progress
 * is pushed to the component via Laravel Echo (Reverb WebSocket broadcast).
 *
 * @property array<int, array<int, string>> $csvRows
 * @property bool $hasCsvLoaded
 * @property MassOperationService $massOperationService
 */
trait ManagesMassOperations
{
    // -------------------------------------------------------------------------
    // Public state (serialised by Livewire between requests)
    // -------------------------------------------------------------------------
    /** 'idle' | 'running' | 'done' */
    public string $stressBatchStatus = 'idle';

    public int $stressBatchTotal = 0;

    /** Rows processed so far (successes + failures). */
    public int $stressBatchProgress = 0;

    public int $stressBatchFailed = 0;

    /** Rows where the API was actually called and returned corrected text. */
    public int $stressBatchCorrectedCount = 0;

    /** Actual prompt (input) tokens consumed by the batch, from the API. */
    public int $stressBatchPromptTokens = 0;

    /** Actual completion (output) tokens consumed by the batch, from the API. */
    public int $stressBatchCompletionTokens = 0;

    /** 'idle' | 'running' | 'done' */
    public string $ttsBatchStatus = 'idle';

    public int $ttsBatchTotal = 0;

    public int $ttsBatchProgress = 0;

    public int $ttsBatchFailed = 0;

    /** Audio files actually generated during the batch. */
    public int $ttsBatchGeneratedCount = 0;

    /** Exact character count sent to the TTS API during the batch. */
    public int $ttsBatchActualChars = 0;

    // Estimates — only populated after openBulkActionsModal() is called.
    public int $estimatedStressInputTokens = 0;

    public int $estimatedStressOutputTokens = 0;

    public float $estimatedStressCost = 0.0;

    public int $missingStressRowCount = 0;

    public int $estimatedTtsChars = 0;

    public float $estimatedTtsCost = 0.0;

    public int $missingAudioRowCount = 0;

    // -------------------------------------------------------------------------
    // Echo listener
    // -------------------------------------------------------------------------
    /**
     * Register a dynamic Laravel Echo listener on the session-scoped channel.
     * Must use getListeners() instead of the #[On] attribute because the
     * channel name contains a runtime value (session ID).
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            'echo:mass-op.'.$this->getMassOpSessionId().',.operation.progress' => 'handleBatchProgressUpdate',
        ];
    }

    // -------------------------------------------------------------------------
    // Modal opening
    // -------------------------------------------------------------------------
    /**
     * Recompute cost estimates and sync in-flight progress from cache.
     * Called when the user opens the Bulk Actions modal.
     *
     * syncProgressFromCache() is called first so that any corrections that were
     * merged during that call are visible to refreshEstimates().
     */
    public function openBulkActionsModal(): void
    {
        $this->syncProgressFromCache();
        $this->refreshEstimates();
    }

    // -------------------------------------------------------------------------
    // Batch dispatching
    // -------------------------------------------------------------------------
    /**
     * Dispatch one stress-correction job per qualifying row.
     * Guarded against double-dispatch while a batch is already running.
     */
    public function dispatchStressBatch(): void
    {
        if (! $this->hasCsvLoaded || $this->stressBatchStatus === 'running') {
            return;
        }
        $sessionId = $this->getMassOpSessionId();
        $dispatched = $this->massOperationService->dispatchStressBatch(
            $this->csvRows,
            $sessionId,
        );
        if ($dispatched > 0) {
            $this->stressBatchStatus = 'running';
            $this->stressBatchTotal = $dispatched;
            $this->stressBatchProgress = 0;
            $this->stressBatchFailed = 0;
            $this->stressBatchCorrectedCount = 0;
            $this->stressBatchPromptTokens = 0;
            $this->stressBatchCompletionTokens = 0;

            // With a synchronous queue driver all jobs execute inline inside
            // dispatchStressBatch() above, before returning here.  The 'done'
            // WebSocket broadcast is sent during that call — before the browser
            // is connected to the Echo channel — so it is never received as an
            // Echo event.  Read the cache immediately to catch this case.
            $this->applyStressBatchCompletionIfAlreadyDone($sessionId);
        }
    }

    /**
     * Dispatch one TTS-generation job per qualifying row.
     * Guarded against double-dispatch while a batch is already running.
     */
    public function dispatchTtsBatch(): void
    {
        if (! $this->hasCsvLoaded || $this->ttsBatchStatus === 'running') {
            return;
        }
        $sessionId = $this->getMassOpSessionId();
        $dispatched = $this->massOperationService->dispatchTtsBatch(
            $this->csvRows,
            $sessionId,
        );
        if ($dispatched > 0) {
            $this->ttsBatchStatus = 'running';
            $this->ttsBatchTotal = $dispatched;
            $this->ttsBatchProgress = 0;
            $this->ttsBatchFailed = 0;
            $this->ttsBatchGeneratedCount = 0;
            $this->ttsBatchActualChars = 0;

            // Same sync-queue guard as dispatchStressBatch(): if all jobs already
            // finished inline, handle completion now before the Livewire response
            // is sent back to the browser.
            $this->applyTtsBatchCompletionIfAlreadyDone($sessionId);
        }
    }

    // -------------------------------------------------------------------------
    // Echo broadcast handler
    // -------------------------------------------------------------------------
    /**
     * Handle a progress broadcast from MassOperationJob.
     * Updates the batch state properties; when a stress batch completes,
     * merges the per-row corrected texts from cache back into $csvRows.
     *
     * @param  array{operationType: string, status: string, processedCount: int, totalCount: int, failedCount: int}  $event
     */
    public function handleBatchProgressUpdate(array $event): void
    {
        $operationType = $event['operationType'] ?? '';
        $status = $event['status'] ?? 'running';
        $processedCount = (int) ($event['processedCount'] ?? 0);
        $totalCount = (int) ($event['totalCount'] ?? 0);
        $failedCount = (int) ($event['failedCount'] ?? 0);
        if ($operationType === 'stress') {
            $this->stressBatchStatus = $status;
            $this->stressBatchProgress = $processedCount;
            $this->stressBatchTotal = $totalCount;
            $this->stressBatchFailed = $failedCount;
            if ($status === 'done') {
                $this->mergeStressResultsFromCache();
                $this->refreshEstimates();
            }
        } elseif ($operationType === 'tts') {
            $this->ttsBatchStatus = $status;
            $this->ttsBatchProgress = $processedCount;
            $this->ttsBatchTotal = $totalCount;
            $this->ttsBatchFailed = $failedCount;
            if ($status === 'done') {
                $this->mergeTtsReportFromCache();
                // Force Livewire to recompute audioExistenceByRowIndex on the
                // next render by clearing the computed cache.
                unset($this->audioExistenceByRowIndex);
                $this->refreshEstimates();
            }
        }
    }

    // -------------------------------------------------------------------------
    // Computed helpers (used by Blade to conditionally disable per-row buttons)
    // -------------------------------------------------------------------------
    #[Computed]
    public function isStressBatchRunning(): bool
    {
        return $this->stressBatchStatus === 'running';
    }

    #[Computed]
    public function isTtsBatchRunning(): bool
    {
        return $this->ttsBatchStatus === 'running';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------
    /**
     * Recompute cost and row-count estimates from the current CSV rows.
     * Called when the modal opens and after each batch completes.
     */
    private function refreshEstimates(): void
    {
        if (! $this->hasCsvLoaded) {
            return;
        }
        $stressEstimate = $this->massOperationService->estimateStressBatchCost($this->csvRows);
        $ttsEstimate = $this->massOperationService->estimateTtsBatchCost($this->csvRows);
        $this->missingStressRowCount = $stressEstimate['rowCount'];
        $this->estimatedStressInputTokens = $stressEstimate['inputTokens'];
        $this->estimatedStressOutputTokens = $stressEstimate['outputTokens'];
        $this->estimatedStressCost = $stressEstimate['estimatedCost'];
        $this->missingAudioRowCount = $ttsEstimate['rowCount'];
        $this->estimatedTtsChars = $ttsEstimate['totalChars'];
        $this->estimatedTtsCost = $ttsEstimate['estimatedCost'];
    }

    /**
     * Read the current batch progress from the cache and update state.
     * Allows the modal to show accurate progress after a page refresh mid-run.
     *
     * When a batch is already marked 'done' in the cache (either because it
     * finished while the component was unmounted, or because the closing
     * WebSocket event was missed), the merge/report helpers are called here so
     * the corrected data is never lost.
     */
    private function syncProgressFromCache(): void
    {
        $sessionId = $this->getMassOpSessionId();
        $stressProgress = $this->massOperationService->getOperationProgress($sessionId, 'stress');
        $ttsProgress = $this->massOperationService->getOperationProgress($sessionId, 'tts');
        if ($stressProgress['status'] !== 'idle') {
            $this->stressBatchStatus = $stressProgress['status'];
            $this->stressBatchTotal = $stressProgress['total'];
            $this->stressBatchProgress = $stressProgress['processed'];
            $this->stressBatchFailed = $stressProgress['failed'];

            if ($stressProgress['status'] === 'done') {
                $this->mergeStressResultsFromCache();
            }
        }
        if ($ttsProgress['status'] !== 'idle') {
            $this->ttsBatchStatus = $ttsProgress['status'];
            $this->ttsBatchTotal = $ttsProgress['total'];
            $this->ttsBatchProgress = $ttsProgress['processed'];
            $this->ttsBatchFailed = $ttsProgress['failed'];

            if ($ttsProgress['status'] === 'done') {
                $this->mergeTtsReportFromCache();
                unset($this->audioExistenceByRowIndex);
            }
        }
    }

    /**
     * Read all per-row stress-correction results from cache and merge them
     * into $csvRows. Called once when the stress batch status transitions to
     * 'done'. Also reads the token-usage report accumulated by the jobs.
     * Clears the cache keys after merging to avoid stale data.
     */
    private function mergeStressResultsFromCache(): void
    {
        $sessionId = $this->getMassOpSessionId();
        $anyMerged = false;
        foreach (array_keys($this->csvRows) as $rowIndex) {
            $cacheKey = "mass_op:{$sessionId}:stress:row:{$rowIndex}";
            $correctedText = Cache::get($cacheKey);
            if ($correctedText !== null) {
                $this->csvRows[$rowIndex][1] = $correctedText;
                Cache::forget($cacheKey);
                $anyMerged = true;
            }
        }
        if ($anyMerged) {
            $this->autoSaveToTempFile();
        }

        // Read the exact token usage and corrected-row count accumulated by the jobs.
        $report = $this->massOperationService->getStressReport($sessionId);
        $this->stressBatchCorrectedCount = $report['corrected'];
        $this->stressBatchPromptTokens = $report['promptTokens'];
        $this->stressBatchCompletionTokens = $report['completionTokens'];
    }

    /**
     * Read the TTS-batch completion report from cache and populate the
     * report props. Called once when the TTS batch transitions to 'done'.
     */
    private function mergeTtsReportFromCache(): void
    {
        $report = $this->massOperationService->getTtsReport($this->getMassOpSessionId());
        $this->ttsBatchGeneratedCount = $report['generated'];
        $this->ttsBatchActualChars = $report['actualChars'];
    }

    /**
     * If the stress batch has already reached 'done' in the cache, apply the
     * same post-completion actions that the Echo event handler would normally
     * trigger.  This handles the sync queue driver, where all jobs finish before
     * the browser can connect and listen on the WebSocket channel.
     */
    private function applyStressBatchCompletionIfAlreadyDone(string $sessionId): void
    {
        $progress = $this->massOperationService->getOperationProgress($sessionId, 'stress');

        if ($progress['status'] !== 'done') {
            return;
        }

        $this->stressBatchStatus = 'done';
        $this->stressBatchProgress = $progress['processed'];
        $this->stressBatchFailed = $progress['failed'];
        $this->mergeStressResultsFromCache();
        $this->refreshEstimates();
    }

    /**
     * If the TTS batch has already reached 'done' in the cache, apply the
     * same post-completion actions that the Echo event handler would normally
     * trigger.  Mirrors applyStressBatchCompletionIfAlreadyDone() for TTS.
     */
    private function applyTtsBatchCompletionIfAlreadyDone(string $sessionId): void
    {
        $progress = $this->massOperationService->getOperationProgress($sessionId, 'tts');

        if ($progress['status'] !== 'done') {
            return;
        }

        $this->ttsBatchStatus = 'done';
        $this->ttsBatchProgress = $progress['processed'];
        $this->ttsBatchFailed = $progress['failed'];
        $this->mergeTtsReportFromCache();
        unset($this->audioExistenceByRowIndex);
        $this->refreshEstimates();
    }

    /**
     * Return the session ID used to namespace cache keys and broadcast channels.
     * Kept as a method so it can be overridden or mocked in tests.
     */
    private function getMassOpSessionId(): string
    {
        return session()->getId();
    }
}
