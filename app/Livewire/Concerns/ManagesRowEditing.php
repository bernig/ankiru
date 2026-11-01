<?php

namespace App\Livewire\Concerns;

use App\Services\RussianAccentService;
use Livewire\Attributes\Locked;

/**
 * Cell and row editing of the CsvEditor: inline edits, accent placement,
 * adding rows, and deleting a row with a one-step undo.
 *
 * @property array<int, array<int, string>> $csvRows
 * @property int $activeDraftId
 * @property int $perPage
 * @property RussianAccentService $accentService
 */
trait ManagesRowEditing
{
    /**
     * The row removed by the last deleteRow(), so undoDeleteRow() can put it
     * back where it was. Cleared once restored or when another file opens.
     *
     * @var array{draftId: int, index: int, row: array<int, string>}|null
     */
    #[Locked]
    public ?array $lastDeletedRow = null;

    /**
     * Move the <b> accent marker to the Russian vowel at the given plain-text
     * character position within a data cell.
     *
     * @param  int  $charPosition  0-based character index in the stripped plain text.
     */
    public function placeAccentOnVowel(int $rowIndex, int $columnIndex, int $charPosition): void
    {
        if (! isset($this->csvRows[$rowIndex][$columnIndex])) {
            return;
        }

        $this->csvRows[$rowIndex][$columnIndex] = $this->accentService->moveAccentToPosition(
            $this->csvRows[$rowIndex][$columnIndex],
            $charPosition
        );

        $this->autoSaveDraft();
    }

    /**
     * Update a single cell value and persist the change.
     */
    public function updateCell(int $rowIndex, int $columnIndex, string $value): void
    {
        if (! isset($this->csvRows[$rowIndex])) {
            return;
        }

        $this->csvRows[$rowIndex][$columnIndex] = $value;

        $this->autoSaveDraft();
    }

    /**
     * Append an empty row filled with empty strings for each column,
     * then navigate to the last page where the new row appears.
     */
    public function addRow(): void
    {
        $columnCount = count(reset($this->csvRows) ?: []) ?: 2;
        $this->csvRows[] = array_fill(0, $columnCount, '');
        $newRowIndex = array_key_last($this->csvRows);
        $this->autoSaveDraft();
        // Clear all filters so the new (empty) row is visible and totalPages is accurate.
        $this->searchQuery = '';
        $this->filterAccentNeeded = false;
        $this->filterNoAudio = false;
        // Land on the last page so the new row is immediately visible.
        $this->setPage($this->totalPages);
        $this->dispatch('csv-row-added', rowIndex: $newRowIndex);

        if ($newRowIndex === 0) {
            $this->maybeStartFirstRowTip();
        }
    }

    /**
     * Delete a row by its index and re-index the rows array.
     */
    public function deleteRow(int $rowIndex): void
    {
        if (! isset($this->csvRows[$rowIndex])) {
            return;
        }

        $this->lastDeletedRow = ['draftId' => $this->activeDraftId, 'index' => $rowIndex, 'row' => $this->csvRows[$rowIndex]];
        $this->dispatch('row-deleted');

        unset($this->csvRows[$rowIndex]);
        $this->csvRows = array_values($this->csvRows);
        // Close the audio modal, since indices have shifted and references would be stale.
        $this->ttsModalRowIndex = -1;
        $this->autoSaveDraft();
        // Clamp the current page in case the last page was emptied by this deletion.
        if ($this->getPage() > $this->totalPages) {
            $this->setPage($this->totalPages);
        }
    }

    /**
     * Put back the row removed by the last deleteRow(), at its former index
     * (or at the end when rows were removed since), and show its page.
     */
    public function undoDeleteRow(): void
    {
        $deleted = $this->lastDeletedRow;
        $this->lastDeletedRow = null;

        if ($deleted === null || $deleted['draftId'] !== $this->activeDraftId) {
            return;
        }

        $index = min($deleted['index'], count($this->csvRows));
        array_splice($this->csvRows, $index, 0, [$deleted['row']]);
        $this->autoSaveDraft();
        $this->setPage(intdiv($index, $this->perPage) + 1);
    }
}
