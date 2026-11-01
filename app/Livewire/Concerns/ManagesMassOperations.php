<?php

namespace App\Livewire\Concerns;

use App\Enums\OperationType;
use App\Models\CsvDraft;
use App\Services\CreditService;
use App\Services\MassOperationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Manages the "Bulk Actions" feature for the CsvEditor component.
 *
 * Provides two batch operations (stress-mark correction and TTS audio
 * generation) that run in the background via queued jobs. Real-time progress
 * is pushed to the component via Laravel Echo (Reverb WebSocket broadcast).
 *
 * @property array<int, array<int, string>> $csvRows
 * @property bool $hasCsvLoaded
 * @property int $activeDraftId
 * @property MassOperationService $massOperationService
 * @property CreditService $creditService
 */
trait ManagesMassOperations
{
    // -------------------------------------------------------------------------
    // Public state (serialised by Livewire between requests)
    // -------------------------------------------------------------------------
    /** UUID of the stress batch this editor tracks ('' when none yet). */
    #[Locked]
    public string $stressBatchId = '';

    /** UUID of the TTS batch this editor tracks ('' when none yet). */
    #[Locked]
    public string $ttsBatchId = '';

    /** 'idle' | 'running' | 'done' */
    #[Locked]
    public string $stressBatchStatus = 'idle';

    #[Locked]
    public int $stressBatchTotal = 0;

    /** Rows processed so far (successes + failures). */
    #[Locked]
    public int $stressBatchProgress = 0;

    #[Locked]
    public int $stressBatchFailed = 0;

    /** Rows where the API was actually called and returned corrected text. */
    #[Locked]
    public int $stressBatchCorrectedCount = 0;

    /** Actual prompt (input) tokens consumed by the batch, from the API. */
    #[Locked]
    public int $stressBatchPromptTokens = 0;

    /** Actual completion (output) tokens consumed by the batch, from the API. */
    #[Locked]
    public int $stressBatchCompletionTokens = 0;

    /** 'idle' | 'running' | 'done' */
    #[Locked]
    public string $ttsBatchStatus = 'idle';

    #[Locked]
    public int $ttsBatchTotal = 0;

    #[Locked]
    public int $ttsBatchProgress = 0;

    #[Locked]
    public int $ttsBatchFailed = 0;

    /** Audio files actually generated during the batch. */
    #[Locked]
    public int $ttsBatchGeneratedCount = 0;

    /** Exact character count sent to the TTS API during the batch. */
    #[Locked]
    public int $ttsBatchActualChars = 0;

    /**
     * True while the corrections of the last finished stress batch can be
     * reverted with undoStressBatch(). The corrections themselves are kept
     * in the cache (stressUndoCacheKey()), not in the snapshot.
     */
    #[Locked]
    public bool $canUndoStressBatch = false;

    // Estimates - only populated after openBulkActionsModal() is called.
    #[Locked]
    public int $estimatedStressInputTokens = 0;

    #[Locked]
    public int $estimatedStressOutputTokens = 0;

    #[Locked]
    public float $estimatedStressCost = 0.0;

    #[Locked]
    public int $missingStressRowCount = 0;

    #[Locked]
    public int $estimatedTtsChars = 0;

    #[Locked]
    public float $estimatedTtsCost = 0.0;

    #[Locked]
    public int $missingAudioRowCount = 0;

    // -------------------------------------------------------------------------
    // Batch cancellation
    // -------------------------------------------------------------------------
    public function cancelStressBatch(): void
    {
        $this->cancelBatch(OperationType::Stress);
        $this->stressBatchStatus = 'done';
    }

    public function cancelTtsBatch(): void
    {
        $this->cancelBatch(OperationType::Tts);
        $this->ttsBatchStatus = 'done';
    }

    // -------------------------------------------------------------------------
    // Echo listener
    // -------------------------------------------------------------------------
    /**
     * Register a dynamic Laravel Echo listener on the user's private channel.
     * Must use getListeners() instead of the #[On] attribute because the
     * channel name contains a runtime value (user ID).
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        if (Auth::id() === null) {
            return [];
        }

        return [
            'echo-private:App.Models.User.'.Auth::id().',.operation.progress' => 'handleBatchProgressUpdate',
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
        if ($this->apiKeyMissing()) {
            return;
        }

        if (! $this->hasCsvLoaded || $this->stressBatchStatus === 'running') {
            return;
        }

        $this->forgetStressBatchUndo();

        if ($this->adoptRunningBatchFromAnotherTab(OperationType::Stress)) {
            return;
        }

        if (Auth::user()->usesPlatformCredits()) {
            $estimate = $this->massOperationService->estimateStressBatchCost($this->csvRows);

            if (! $this->ensureCanAfford($this->creditService->tokensToCredits($estimate['inputTokens'], $estimate['outputTokens']))) {
                return;
            }
        }

        // Step 1: Deterministically fix bare ё in every row right now, before
        // any async job is dispatched.  This covers the common case where ё is
        // the only missing accent and no AI call is needed at all; the fix is
        // immediate regardless of the queue driver or WebSocket availability.
        $this->applyYoNormalizationToAllRows();

        $batchId = (string) Str::uuid();
        $dispatched = $this->massOperationService->dispatchStressBatch(
            $this->csvRows,
            $batchId,
            $this->activeDraftId > 0 ? $this->activeDraftId : null,
        );
        if ($dispatched > 0) {
            $this->stressBatchId = $batchId;
            $this->stressBatchStatus = 'running';
            $this->stressBatchTotal = $dispatched;
            $this->stressBatchProgress = 0;
            $this->stressBatchFailed = 0;
            $this->stressBatchCorrectedCount = 0;
            $this->stressBatchPromptTokens = 0;
            $this->stressBatchCompletionTokens = 0;

            // With a synchronous queue driver all jobs execute inline inside
            // dispatchStressBatch() above, before returning here.  The 'done'
            // WebSocket broadcast is sent during that call, before the browser
            // is connected to the Echo channel, so it is never received as an
            // Echo event.  Read the cache immediately to catch this case.
            $this->applyStressBatchCompletionIfAlreadyDone($batchId);
        }
    }

    /**
     * Dispatch one TTS-generation job per qualifying row.
     * Guarded against double-dispatch while a batch is already running.
     */
    public function dispatchTtsBatch(): void
    {
        if ($this->apiKeyMissing()) {
            return;
        }

        if (! $this->hasCsvLoaded || $this->ttsBatchStatus === 'running') {
            return;
        }

        if ($this->adoptRunningBatchFromAnotherTab(OperationType::Tts)) {
            return;
        }

        if (Auth::user()->usesPlatformCredits()) {
            $estimate = $this->massOperationService->estimateTtsBatchCost($this->csvRows);

            if (! $this->ensureCanAfford($this->creditService->ttsCharsToCredits($estimate['totalChars']))) {
                return;
            }
        }

        $batchId = (string) Str::uuid();
        $dispatched = $this->massOperationService->dispatchTtsBatch(
            $this->csvRows,
            $batchId,
        );
        if ($dispatched > 0) {
            $this->ttsBatchId = $batchId;
            $this->ttsBatchStatus = 'running';
            $this->ttsBatchTotal = $dispatched;
            $this->ttsBatchProgress = 0;
            $this->ttsBatchFailed = 0;
            $this->ttsBatchGeneratedCount = 0;
            $this->ttsBatchActualChars = 0;

            // Same sync-queue guard as dispatchStressBatch(): if all jobs already
            // finished inline, handle completion now before the Livewire response
            // is sent back to the browser.
            $this->applyTtsBatchCompletionIfAlreadyDone($batchId);
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
     * Events for another batch (started from another tab, or an older batch)
     * are ignored: they share the user's channel but not this editor's state.
     *
     * @param  array{operationType: string, batchId: string, status: string, processedCount: int, totalCount: int, failedCount: int}  $event
     */
    public function handleBatchProgressUpdate(array $event): void
    {
        $operationType = $event['operationType'] ?? '';
        $type = OperationType::tryFrom($operationType);

        if ($type === null || ($event['batchId'] ?? '') === '' || $event['batchId'] !== $this->batchIdFor($type)) {
            return;
        }

        $status = $event['status'] ?? 'running';
        $processedCount = (int) ($event['processedCount'] ?? 0);
        $totalCount = (int) ($event['totalCount'] ?? 0);
        $failedCount = (int) ($event['failedCount'] ?? 0);
        match ($type) {
            OperationType::Stress => $this->applyStressProgressUpdate($status, $processedCount, $totalCount, $failedCount),
            OperationType::Tts => $this->applyTtsProgressUpdate($status, $processedCount, $totalCount, $failedCount),
        };
    }

    /**
     * Fallback progress refresh used when a WebSocket update is missed during a
     * deploy or temporary Reverb reconnect window.
     */
    public function refreshRunningBatchProgress(): void
    {
        if ($this->stressBatchStatus !== 'running' && $this->ttsBatchStatus !== 'running') {
            return;
        }

        $stressWasRunning = $this->stressBatchStatus === 'running';
        $ttsWasRunning = $this->ttsBatchStatus === 'running';

        $this->syncProgressFromCache();

        if (($stressWasRunning && $this->stressBatchStatus === 'done')
            || ($ttsWasRunning && $this->ttsBatchStatus === 'done')) {
            $this->refreshEstimates();
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

    /**
     * Revert the corrections of the last finished stress batch. A row is only
     * reverted while it still holds the corrected text: a row edited, moved
     * or re-corrected since keeps its current value.
     */
    public function undoStressBatch(): void
    {
        $undo = Cache::pull($this->stressUndoCacheKey());
        $this->canUndoStressBatch = false;

        if (! is_array($undo) || $undo['draftId'] !== $this->activeDraftId) {
            return;
        }

        $anyReverted = false;

        foreach ($undo['corrections'] as $rowIndex => $correction) {
            if (($this->csvRows[$rowIndex][1] ?? null) === $correction['corrected']) {
                $this->csvRows[$rowIndex][1] = $correction['original'];
                $anyReverted = true;
            }
        }

        if ($anyReverted) {
            $this->autoSaveDraft();
        }

        $this->stressBatchStatus = 'idle';
        $this->refreshEstimates();
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
        $stressBatchId = $this->batchIdFor(OperationType::Stress);
        $ttsBatchId = $this->batchIdFor(OperationType::Tts);
        $stressProgress = $this->massOperationService->getOperationProgress($stressBatchId, OperationType::Stress);
        $ttsProgress = $this->massOperationService->getOperationProgress($ttsBatchId, OperationType::Tts);

        // Auto-cancel a batch that is marked running but has no remaining jobs in
        // the queue (e.g. worker was killed mid-run, permission error wiped jobs).
        // Only relevant with the 'database' driver; other drivers don't expose a
        // queryable jobs table, so the check would always return false.
        if (config('queue.default') === 'database') {
            if ($stressProgress['status'] === 'running' && ! $this->hasActiveJobsInQueue($stressBatchId, OperationType::Stress)) {
                $this->cancelBatch(OperationType::Stress);
                $stressProgress['status'] = 'done';
            }
            if ($ttsProgress['status'] === 'running' && ! $this->hasActiveJobsInQueue($ttsBatchId, OperationType::Tts)) {
                $this->cancelBatch(OperationType::Tts);
                $ttsProgress['status'] = 'done';
            }
        }

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
     * into the draft the batch was started on. Called once when the stress
     * batch status transitions to 'done'. Also reads the token-usage report
     * accumulated by the jobs. Clears the cache keys after merging.
     *
     * The target draft is the one recorded at dispatch time: when it is the
     * active draft the in-memory rows are updated, otherwise the stored draft
     * is updated directly so results never land in the wrong file.
     */
    private function mergeStressResultsFromCache(): void
    {
        $batchId = $this->batchIdFor(OperationType::Stress);
        $key = fn (string $suffix): string => MassOperationService::batchCacheKey($batchId, OperationType::Stress, $suffix);
        $targetDraftId = Cache::pull($key('draft_id'));
        $rowIndexes = Cache::pull($key('row_indexes')) ?? array_keys($this->csvRows);

        $corrections = [];
        foreach ($rowIndexes as $rowIndex) {
            $result = Cache::pull($key("row:{$rowIndex}"));
            if (is_array($result) && isset($result['original'], $result['corrected'])) {
                $corrections[$rowIndex] = $result;
            }
        }

        if ($corrections !== []) {
            if ($targetDraftId === null || (int) $targetDraftId === $this->activeDraftId) {
                $appliedCorrections = [];
                $this->csvRows = $this->applyStressCorrections($this->csvRows, $corrections, $appliedCorrections);
                $this->autoSaveDraft();
                $this->rememberStressBatchUndo($appliedCorrections);
            } else {
                $this->applyStressCorrectionsToStoredDraft((int) $targetDraftId, $corrections);
            }
        }

        // Read the exact token usage and corrected-row count accumulated by the jobs.
        $report = $this->massOperationService->getStressReport($batchId);
        $this->stressBatchCorrectedCount = $report['corrected'];
        $this->stressBatchPromptTokens = $report['promptTokens'];
        $this->stressBatchCompletionTokens = $report['completionTokens'];

        $this->notifyIfBatchStoppedForCredits(OperationType::Stress);
    }

    /**
     * Read the TTS-batch completion report from cache and populate the
     * report props. Called once when the TTS batch transitions to 'done'.
     */
    private function mergeTtsReportFromCache(): void
    {
        $report = $this->massOperationService->getTtsReport($this->batchIdFor(OperationType::Tts));
        $this->ttsBatchGeneratedCount = $report['generated'];
        $this->ttsBatchActualChars = $report['actualChars'];

        $this->notifyIfBatchStoppedForCredits(OperationType::Tts);
    }

    /**
     * Apply stress corrections only to rows that still hold the exact text the
     * correction was computed from. The original row index is tried first;
     * if that row changed (edited, or shifted by a deletion), the first other
     * row with the same original text receives it. Otherwise it is dropped.
     *
     * @param  array<int, array<int, string>>  $rows
     * @param  array<int, array{original: string, corrected: string}>  $corrections  Keyed by row index at dispatch time.
     * @param  array<int, array{original: string, corrected: string}>  $appliedCorrections  Filled with the corrections applied, keyed by the row index they landed on.
     * @return array<int, array<int, string>>
     */
    private function applyStressCorrections(array $rows, array $corrections, array &$appliedCorrections = []): array
    {
        $correctedRowIndexes = [];

        foreach ($corrections as $dispatchedRowIndex => $correction) {
            $targetRowIndex = ($rows[$dispatchedRowIndex][1] ?? null) === $correction['original']
                && ! isset($correctedRowIndexes[$dispatchedRowIndex])
                ? $dispatchedRowIndex
                : null;

            if ($targetRowIndex === null) {
                foreach ($rows as $rowIndex => $row) {
                    if (($row[1] ?? null) === $correction['original'] && ! isset($correctedRowIndexes[$rowIndex])) {
                        $targetRowIndex = $rowIndex;
                        break;
                    }
                }
            }

            if ($targetRowIndex !== null) {
                $rows[$targetRowIndex][1] = $correction['corrected'];
                $correctedRowIndexes[$targetRowIndex] = true;
                $appliedCorrections[$targetRowIndex] = $correction;
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array{original: string, corrected: string}>  $appliedCorrections
     */
    private function rememberStressBatchUndo(array $appliedCorrections): void
    {
        if ($appliedCorrections === []) {
            return;
        }

        Cache::put($this->stressUndoCacheKey(), ['draftId' => $this->activeDraftId, 'corrections' => $appliedCorrections], 3600);
        $this->canUndoStressBatch = true;
    }

    private function forgetStressBatchUndo(): void
    {
        if ($this->canUndoStressBatch) {
            Cache::forget($this->stressUndoCacheKey());
            $this->canUndoStressBatch = false;
        }
    }

    private function stressUndoCacheKey(): string
    {
        return MassOperationService::stressUndoCacheKey((int) Auth::id());
    }

    /**
     * Merge stress corrections into a draft that is not currently open.
     *
     * @param  array<int, array{original: string, corrected: string}>  $corrections
     */
    private function applyStressCorrectionsToStoredDraft(int $draftId, array $corrections): void
    {
        $draft = CsvDraft::query()
            ->where('id', $draftId)
            ->where('user_id', Auth::id())
            ->first();

        if ($draft === null) {
            return;
        }

        $draft->update(['csv_rows' => $this->applyStressCorrections($draft->csv_rows ?? [], $corrections)]);
    }

    /**
     * If the stress batch has already reached 'done' in the cache, apply the
     * same post-completion actions that the Echo event handler would normally
     * trigger.  This handles the sync queue driver, where all jobs finish before
     * the browser can connect and listen on the WebSocket channel.
     */
    private function applyStressBatchCompletionIfAlreadyDone(string $batchId): void
    {
        $progress = $this->massOperationService->getOperationProgress($batchId, OperationType::Stress);

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
    private function applyTtsBatchCompletionIfAlreadyDone(string $batchId): void
    {
        $progress = $this->massOperationService->getOperationProgress($batchId, OperationType::Tts);

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
     * Apply ё-accent normalization to every Russian column entry, updating
     * $csvRows in place and persisting when at least one row changed.
     *
     * Called synchronously at the start of dispatchStressBatch() so that rows
     * whose only problem is a bare ё are corrected immediately, without having
     * to wait for an async queue worker or a WebSocket broadcast.
     */
    private function applyYoNormalizationToAllRows(): void
    {
        $anyChanged = false;

        foreach ($this->csvRows as $rowIndex => $row) {
            $russianText = $row[1] ?? '';

            if (empty(trim($russianText))) {
                continue;
            }

            $normalizedText = $this->accentService->normalizeYoAccent($russianText);

            if ($normalizedText !== $russianText) {
                $this->csvRows[$rowIndex][1] = $normalizedText;
                $anyChanged = true;
            }
        }

        if ($anyChanged) {
            $this->autoSaveDraft();
        }
    }

    private function applyStressProgressUpdate(string $status, int $processedCount, int $totalCount, int $failedCount): void
    {
        $this->stressBatchStatus = $status;
        $this->stressBatchProgress = $processedCount;
        $this->stressBatchTotal = $totalCount;
        $this->stressBatchFailed = $failedCount;

        if ($status === 'done') {
            $this->mergeStressResultsFromCache();
            $this->refreshEstimates();
        }
    }

    private function applyTtsProgressUpdate(string $status, int $processedCount, int $totalCount, int $failedCount): void
    {
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

    /**
     * UUID of the batch this editor tracks for the given type. An editor that
     * has not started one itself (fresh page load, reload mid-run) adopts the
     * user's latest batch, so progress and results are never lost.
     */
    private function batchIdFor(OperationType $operationType): string
    {
        $property = $operationType === OperationType::Stress ? 'stressBatchId' : 'ttsBatchId';

        if ($this->{$property} === '') {
            $this->{$property} = $this->latestUserBatchId($operationType) ?? '';
        }

        return $this->{$property};
    }

    private function latestUserBatchId(OperationType $operationType): ?string
    {
        if (Auth::id() === null) {
            return null;
        }

        return Cache::get(MassOperationService::currentBatchCacheKey(Auth::id(), $operationType));
    }

    /**
     * One batch per type and user at a time: when another tab already runs
     * one, track it instead of dispatching a second batch that would bill
     * and edit the same rows concurrently. Returns true when adopted.
     */
    private function adoptRunningBatchFromAnotherTab(OperationType $operationType): bool
    {
        $latestBatchId = $this->latestUserBatchId($operationType);

        if ($latestBatchId === null
            || $this->massOperationService->getOperationProgress($latestBatchId, $operationType)['status'] !== 'running') {
            return false;
        }

        if ($operationType === OperationType::Stress) {
            $this->stressBatchId = $latestBatchId;
        } else {
            $this->ttsBatchId = $latestBatchId;
        }

        $this->syncProgressFromCache();

        return true;
    }

    /**
     * Return true if the queue contains any jobs (pending or reserved) for this
     * batch and operation type. Used to detect orphaned 'running' batches.
     */
    private function hasActiveJobsInQueue(string $batchId, OperationType $operationType): bool
    {
        return DB::table('jobs')
            ->where('payload', 'like', "%{$batchId}%")
            ->where('payload', 'like', '%'.$operationType->value.'%')
            ->exists();
    }

    private function cancelBatch(OperationType $operationType): void
    {
        $this->massOperationService->cancelBatch($this->batchIdFor($operationType), $operationType);
    }

    /**
     * Open the credits shop when a finished batch was stopped by a job because
     * the user ran out of credits, so they know why rows were left untouched.
     */
    private function notifyIfBatchStoppedForCredits(OperationType $operationType): void
    {
        $stopReason = Cache::pull(MassOperationService::batchCacheKey($this->batchIdFor($operationType), $operationType, 'stopped'));

        if ($stopReason === 'insufficient_credits') {
            $this->dispatch('open-credits-shop');
        }
    }
}
