<?php

namespace App\Livewire\Concerns;

use App\Models\CsvDraft;

/**
 * Provides per-user database persistence for the CsvEditor component.
 * Saving and restoring editor state allows drafts to survive page refreshes.
 *
 * @property array<int, array<int, string>> $csvRows
 * @property string $originalFileName
 * @property bool $hasCsvLoaded
 */
trait ManagesPersistence
{
    /**
     * Persist the current editor state to a per-user database draft row.
     */
    private function autoSaveDraft(): void
    {
        if (! auth()->check()) {
            return;
        }

        CsvDraft::query()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'original_file_name' => $this->originalFileName,
                'csv_rows' => $this->csvRows,
                'has_csv_loaded' => $this->hasCsvLoaded,
            ],
        );
    }

    /**
     * Restore editor state from the authenticated user's saved draft if it exists.
     */
    private function restoreFromDraft(): void
    {
        if (! auth()->check()) {
            return;
        }

        /** @var CsvDraft|null $draft */
        $draft = CsvDraft::query()->where('user_id', auth()->id())->first();

        if ($draft === null) {
            return;
        }

        /** @var array<int, array<int, string>> $rows */
        $rows = $draft->csv_rows ?? [];

        $this->csvRows = $rows;
        $this->originalFileName = $draft->original_file_name;
        $this->hasCsvLoaded = $draft->has_csv_loaded;
    }

    /**
     * Remove the authenticated user's saved draft.
     */
    private function clearDraft(): void
    {
        if (! auth()->check()) {
            return;
        }

        CsvDraft::query()->where('user_id', auth()->id())->delete();
    }
}
