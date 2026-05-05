<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Provides session-scoped temp-file persistence for the CsvEditor component.
 * Saving and restoring editor state allows drafts to survive page refreshes.
 *
 * @property array<int, array<int, string>> $csvRows
 * @property string $originalFileName
 * @property bool $hasCsvLoaded
 */
trait ManagesPersistence
{
    /**
     * Persist the current editor state to a session-scoped JSON temp file in storage.
     */
    private function autoSaveToTempFile(): void
    {
        $data = [
            'csvRows' => $this->csvRows,
            'originalFileName' => $this->originalFileName,
            'hasCsvLoaded' => $this->hasCsvLoaded,
            'savedAt' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put($this->tempFilePath(), json_encode($data));
    }

    /**
     * Restore editor state from the session-scoped temp file if it exists.
     */
    private function restoreFromTempFile(): void
    {
        $filePath = $this->tempFilePath();

        if (! Storage::disk('local')->exists($filePath)) {
            return;
        }

        $rawJson = Storage::disk('local')->get($filePath);

        if ($rawJson === null) {
            return;
        }

        /** @var array{csvRows: array<int, array<int, string>>, originalFileName: string, hasCsvLoaded: bool}|null $data */
        $data = json_decode($rawJson, true);

        if (! is_array($data)) {
            return;
        }

        $this->csvRows = $data['csvRows'] ?? [];
        $this->originalFileName = $data['originalFileName'] ?? '';
        $this->hasCsvLoaded = $data['hasCsvLoaded'] ?? false;
    }

    /**
     * Remove the session-scoped temp file from storage.
     */
    private function clearTempFile(): void
    {
        $filePath = $this->tempFilePath();

        if (Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->delete($filePath);
        }
    }

    /**
     * Build the storage-relative path for the session-scoped temp file.
     * Using the session ID ensures each browser session has its own isolated state.
     */
    private function tempFilePath(): string
    {
        return 'csv_editor_temp_'.session()->getId().'.json';
    }
}
