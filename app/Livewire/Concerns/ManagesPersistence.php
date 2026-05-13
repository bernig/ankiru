<?php

namespace App\Livewire\Concerns;

use App\Models\CsvDraft;

/**
 * Provides per-user database persistence for the CsvEditor component.
 * Multiple drafts are supported; the active draft is tracked via activeDraftId.
 *
 * @property array<int, array<int, string>> $csvRows
 * @property string $originalFileName
 * @property bool $hasCsvLoaded
 * @property int $activeDraftId
 * @property array<int, array{id: int, original_file_name: string}> $allDraftsMeta
 */
trait ManagesPersistence
{
    /**
     * Persist the current editor state to the database.
     * Updates the active draft when one exists, otherwise creates a new draft.
     */
    private function autoSaveDraft(): void
    {
        if (! auth()->check()) {
            return;
        }

        if ($this->activeDraftId > 0) {
            CsvDraft::query()
                ->where('id', $this->activeDraftId)
                ->where('user_id', auth()->id())
                ->update([
                    'original_file_name' => $this->originalFileName,
                    'csv_rows' => $this->csvRows,
                    'has_csv_loaded' => $this->hasCsvLoaded,
                    'last_accessed_at' => now(),
                ]);
        } else {
            $draft = CsvDraft::query()->create([
                'user_id' => auth()->id(),
                'original_file_name' => $this->originalFileName,
                'csv_rows' => $this->csvRows,
                'has_csv_loaded' => $this->hasCsvLoaded,
                'last_accessed_at' => now(),
            ]);

            $this->activeDraftId = $draft->id;
        }

        $this->refreshAllDraftsMeta();
    }

    /**
     * Restore editor state from the most recent draft on mount.
     */
    private function restoreFromDraft(): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->refreshAllDraftsMeta();

        /** @var CsvDraft|null $draft */
        $draft = CsvDraft::query()
            ->where('user_id', auth()->id())
            ->orderByRaw('COALESCE(last_accessed_at, created_at) DESC')
            ->first();

        if ($draft === null) {
            return;
        }

        $this->loadDraftIntoState($draft);
    }

    /**
     * Populate the editor's core state properties from a draft model.
     */
    private function loadDraftIntoState(CsvDraft $draft): void
    {
        $this->activeDraftId = $draft->id;
        $this->csvRows = $draft->csv_rows ?? [];
        $this->originalFileName = $draft->original_file_name;
        $this->hasCsvLoaded = $draft->has_csv_loaded;
    }

    /**
     * Delete the currently active draft.
     */
    private function clearDraft(): void
    {
        if (! auth()->check()) {
            return;
        }

        if ($this->activeDraftId > 0) {
            CsvDraft::query()
                ->where('id', $this->activeDraftId)
                ->where('user_id', auth()->id())
                ->delete();

            $this->activeDraftId = 0;
        }

        $this->refreshAllDraftsMeta();
    }

    /**
     * Reload the list of all draft file names for the selector.
     */
    private function refreshAllDraftsMeta(): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->allDraftsMeta = CsvDraft::query()
            ->where('user_id', auth()->id())
            ->orderBy('id')
            ->get(['id', 'original_file_name'])
            ->map(fn (CsvDraft $d): array => [
                'id' => $d->id,
                'original_file_name' => $d->original_file_name,
            ])
            ->all();
    }
}
