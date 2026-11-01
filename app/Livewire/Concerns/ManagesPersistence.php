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
     * Lightweight list of all drafts for the file selector.
     *
     * @var array<int, array{id: int, original_file_name: string}>
     */
    public array $allDraftsMeta = [];

    /** Whether the rename input for the active file is visible. */
    public bool $isRenamingFile = false;

    /** The value bound to the rename input field. */
    public string $renameInput = '';

    /**
     * Delete the active file. If other drafts remain, switch to the most recent one;
     * otherwise return to the upload screen.
     */
    public function resetEditor(): void
    {
        $this->clearDraft();

        /** @var CsvDraft|null $next */
        $next = CsvDraft::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        if ($next !== null) {
            $this->loadDraftIntoState($next);
        } else {
            $this->csvRows = [];
            $this->originalFileName = '';
            $this->validationError = '';
            $this->hasCsvLoaded = false;
            $this->uploadedCsvFile = null;
            $this->activeDraftId = 0;
        }

        $this->resetFiltersAndPagination();
        $this->dispatch('csv-file-switched');
    }

    /**
     * Switch the editor to a different draft owned by the authenticated user.
     */
    public function switchToDraft(int $draftId): void
    {
        /** @var CsvDraft|null $draft */
        $draft = CsvDraft::query()
            ->where('id', $draftId)
            ->where('user_id', auth()->id())
            ->first();

        if ($draft === null) {
            return;
        }

        $this->loadDraftIntoState($draft);
        $this->resetFiltersAndPagination();

        $draft->update(['last_accessed_at' => now()]);

        $this->dispatch('csv-file-switched');
    }

    /**
     * Create a new empty file, persist it, and open the rename prompt immediately.
     */
    public function createNewFile(): void
    {
        $this->csvRows = [];
        $this->originalFileName = __('csv_editor.new_file_default_name').'.csv';
        $this->hasCsvLoaded = true;
        $this->activeDraftId = 0;
        $this->resetFiltersAndPagination();
        $this->dispatch('csv-file-switched');

        $this->autoSaveDraft();
        $this->startRenameDraft();
        $this->maybeStartNewFileTip();
    }

    /**
     * Show the rename input pre-filled with the current file base name.
     */
    public function startRenameDraft(): void
    {
        $this->renameInput = CsvDraft::baseName($this->originalFileName);
        $this->isRenamingFile = true;
    }

    /**
     * Apply the new file name and persist it.
     */
    public function confirmRenameDraft(): void
    {
        $newBaseName = trim($this->renameInput);

        if ($newBaseName === '') {
            $this->isRenamingFile = false;

            return;
        }

        $extension = CsvDraft::extension($this->originalFileName);
        $this->originalFileName = $newBaseName.($extension !== '' ? ".{$extension}" : '');
        $this->isRenamingFile = false;
        $this->renameInput = '';

        $this->autoSaveDraft();
    }

    /**
     * Dismiss the rename input without saving.
     */
    public function cancelRenameDraft(): void
    {
        $this->isRenamingFile = false;
        $this->renameInput = '';
    }

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
     * Read the rows of the active draft, scoped to the authenticated user.
     *
     * @return array<int, array<int, string>>
     */
    private function loadActiveDraftRows(): array
    {
        if (! auth()->check() || $this->activeDraftId <= 0) {
            return [];
        }

        /** @var CsvDraft|null $draft */
        $draft = CsvDraft::query()
            ->where('id', $this->activeDraftId)
            ->where('user_id', auth()->id())
            ->first(['id', 'csv_rows']);

        return $draft?->csv_rows ?? [];
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
