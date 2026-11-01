<?php

namespace App\Livewire\Concerns;

use App\Enums\OperationType;
use App\Models\CsvDraft;
use App\Services\MassOperationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;

/**
 * Results of a finished stress batch: the corrections are merged into the
 * draft the batch was started on (only into rows that still hold the text
 * they were computed from), and the last batch can be reverted.
 *
 * @property array<int, array<int, string>> $csvRows
 * @property int $activeDraftId
 * @property MassOperationService $massOperationService
 */
trait ManagesStressBatchResults
{
    /**
     * True while the corrections of the last finished stress batch can be
     * reverted with undoStressBatch(). The corrections themselves are kept
     * in the cache (stressUndoCacheKey()), not in the snapshot.
     */
    #[Locked]
    public bool $canUndoStressBatch = false;

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
}
