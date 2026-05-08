<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesMassOperations;
use App\Livewire\Concerns\ManagesPersistence;
use App\Livewire\Concerns\ManagesTranslation;
use App\Livewire\Concerns\ManagesTtsAudio;
use App\Services\AnkiPackageExporterService;
use App\Services\MassOperationService;
use App\Services\OpenAiTranslationService;
use App\Services\RussianAccentService;
use App\Services\RussianTextToSpeechService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvEditor extends Component
{
    use ManagesMassOperations;
    use ManagesPersistence;
    use ManagesTranslation;
    use ManagesTtsAudio;
    use WithFileUploads;

    /**
     * Alias the trait's setPage so we can override it with clamping logic
     * while still delegating to the original implementation.
     */
    use WithPagination {
        setPage as paginationSetPage;
    }

    /** Number of rows displayed per page. */
    private const int PER_PAGE = 50;

    /** @var TemporaryUploadedFile|null */
    public $uploadedCsvFile = null;

    /**
     * Rows stored as array of arrays indexed by column position.
     *
     * @var array<int, array<int, string>>
     */
    public array $csvRows = [];

    public string $originalFileName = '';

    public string $validationError = '';

    public bool $hasCsvLoaded = false;

    /**
     * Services are injected as protected so they are accessible from concern traits.
     * They are re-injected on each hydration cycle because Livewire does not
     * serialize non-public properties.
     */
    protected OpenAiTranslationService $translationService;

    protected RussianAccentService $accentService;

    protected RussianTextToSpeechService $ttsService;

    protected AnkiPackageExporterService $ankiExporterService;

    protected MassOperationService $massOperationService;

    /**
     * Called by Livewire before every action (mount and subsequent requests).
     */
    public function boot(
        OpenAiTranslationService $translationService,
        RussianAccentService $accentService,
        RussianTextToSpeechService $ttsService,
        AnkiPackageExporterService $ankiExporterService,
        MassOperationService $massOperationService,
    ): void {
        $this->translationService = $translationService;
        $this->accentService = $accentService;
        $this->ttsService = $ttsService;
        $this->ankiExporterService = $ankiExporterService;
        $this->massOperationService = $massOperationService;
    }

    public function mount(): void
    {
        $this->restoreFromDraft();
    }

    /**
     * Livewire lifecycle hook: called automatically after uploadedCsvFile is set.
     * Enables auto-upload behaviour without a submit button.
     */
    public function updatedUploadedCsvFile(): void
    {
        $this->uploadCsv();
    }

    // -------------------------------------------------------------------------
    // CSV Upload & Parsing
    // -------------------------------------------------------------------------

    /**
     * Handle the CSV file upload, parse rows, and persist to the user's draft.
     */
    public function uploadCsv(): void
    {
        if (! $this->uploadedCsvFile) {
            return;
        }

        $this->validate([
            'uploadedCsvFile' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $this->validationError = '';
        $this->originalFileName = $this->uploadedCsvFile->getClientOriginalName();

        $fileContent = file_get_contents($this->uploadedCsvFile->getRealPath());

        if ($fileContent === false) {
            $this->validationError = __('csv_editor.error_cannot_read_file');

            return;
        }

        /** @var array<int, array<int, string>>|false $parsed */
        $parsed = $this->parseCsvContent($fileContent);

        if ($parsed === false || count($parsed) === 0) {
            $this->validationError = __('csv_editor.error_csv_empty_or_malformed');

            return;
        }

        $columnCount = count($parsed[0]);

        // Normalize each row to match the first row's column count.
        $this->csvRows = array_map(function (array $row) use ($columnCount): array {
            $normalised = array_pad($row, $columnCount, '');

            return array_slice($normalised, 0, $columnCount);
        }, $parsed);

        $this->hasCsvLoaded = true;
        $this->uploadedCsvFile = null;
        $this->resetPage();

        $this->autoSaveDraft();
    }

    // -------------------------------------------------------------------------
    // Pagination
    // -------------------------------------------------------------------------

    /**
     * Rows visible on the current page wrapped in a LengthAwarePaginator so
     * flux:pagination can consume it directly. Original array keys are
     * preserved so all row-index-based actions still work.
     */
    #[Computed]
    public function paginatedRows(): LengthAwarePaginator
    {
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * self::PER_PAGE;
        $slicedItems = collect(array_slice($this->csvRows, $offset, self::PER_PAGE, true));

        return new LengthAwarePaginator(
            $slicedItems,
            count($this->csvRows),
            self::PER_PAGE,
            $currentPage,
            ['path' => request()->url()]
        );
    }

    /**
     * Total number of pages given the current row count and page size.
     */
    #[Computed]
    public function totalPages(): int
    {
        return max(1, (int) ceil(count($this->csvRows) / self::PER_PAGE));
    }

    /**
     * Navigate to the given page number (clamped to valid range).
     * Overrides WithPagination::setPage() to prevent out-of-range navigation.
     *
     * @param  int|string  $page
     */
    public function setPage($page, $pageName = 'page'): void
    {
        $this->paginationSetPage(max(1, min((int) $page, $this->totalPages)), $pageName);
    }

    // -------------------------------------------------------------------------
    // Cell & Row Editing
    // -------------------------------------------------------------------------

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
        $this->autoSaveDraft();
        // Land on the last page so the new row is immediately visible.
        $this->setPage($this->totalPages);
    }

    /**
     * Delete a row by its index and re-index the rows array.
     */
    public function deleteRow(int $rowIndex): void
    {
        unset($this->csvRows[$rowIndex]);
        $this->csvRows = array_values($this->csvRows);
        // Close the audio modal — indices have shifted, references would be stale.
        $this->ttsModalRowIndex = -1;
        $this->autoSaveDraft();
        // Clamp the current page in case the last page was emptied by this deletion.
        if ($this->getPage() > $this->totalPages) {
            $this->setPage($this->totalPages);
        }
    }

    // -------------------------------------------------------------------------
    // Export
    // -------------------------------------------------------------------------

    /**
     * Trigger a browser download of the current CSV data as a plain CSV file.
     */
    public function downloadCsv(): StreamedResponse
    {
        $csvContent = $this->buildCsvContent();
        $downloadFileName = $this->buildBaseFileName().'_edited_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($csvContent): void {
            echo $csvContent;
        }, $downloadFileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build and download a full Anki-compatible .apkg package.
     *
     * The package contains a SQLite collection database with one note per CSV
     * row, plus all TTS audio files that have been cached for those rows.
     */
    public function downloadAnkiPackage(): StreamedResponse
    {
        $cards = $this->buildAnkiCardsFromRows();
        $deckName = pathinfo($this->originalFileName, PATHINFO_FILENAME) ?: 'French-Russian';
        $apkgPath = $this->ankiExporterService->export($cards, $deckName);

        $downloadFileName = $this->buildBaseFileName().'_'.now()->format('Ymd_His').'.apkg';

        return response()->streamDownload(function () use ($apkgPath): void {
            readfile($apkgPath);

            if (! @unlink($apkgPath)) {
                Log::warning('Failed to delete temporary .apkg file after streaming.', [
                    'path' => $apkgPath,
                ]);
            }
        }, $downloadFileName, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Clear the loaded CSV and the saved draft, returning to the upload screen.
     */
    public function resetEditor(): void
    {
        $this->csvRows = [];
        $this->originalFileName = '';
        $this->validationError = '';
        $this->hasCsvLoaded = false;
        $this->uploadedCsvFile = null;
        $this->resetPage();

        $this->clearDraft();
    }

    public function render(): View
    {
        return view('livewire.csv-editor')
            ->layout('layouts.app');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse raw CSV content into a 2D array using fgetcsv so that RFC 4180
     * quoted fields containing newlines are handled correctly.
     *
     * @return array<int, array<int, string>>|false
     */
    private function parseCsvContent(string $content): array|false
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return false;
        }

        try {
            fwrite($handle, $content);
            rewind($handle);

            $rows = [];

            while (($row = fgetcsv($handle)) !== false) {
                // fgetcsv returns [null] for completely blank lines — skip them.
                if ($row === [null]) {
                    continue;
                }

                $rows[] = array_map('strval', $row);
            }

            return count($rows) > 0 ? $rows : false;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Build a CSV string from the current rows (no header row).
     */
    private function buildCsvContent(): string
    {
        $buffer = fopen('php://temp', 'r+');

        if ($buffer === false) {
            return '';
        }

        try {
            foreach ($this->csvRows as $row) {
                fputcsv($buffer, $row);
            }

            rewind($buffer);

            return stream_get_contents($buffer) ?: '';
        } finally {
            fclose($buffer);
        }
    }

    /**
     * Derive the base file name (without extension) used for all download file names.
     * Falls back to 'export' when no original file name is available.
     */
    private function buildBaseFileName(): string
    {
        $baseName = pathinfo($this->originalFileName, PATHINFO_FILENAME);

        return empty($baseName) ? 'export' : $baseName;
    }

    /**
     * Build the card data array expected by AnkiPackageExporterService
     * from the current CSV rows, attaching cached MP3 references where available.
     *
     * @return array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}>
     */
    private function buildAnkiCardsFromRows(): array
    {
        $cards = [];

        foreach ($this->csvRows as $row) {
            $sourceText = $row[0] ?? '';
            $rawRussianText = $row[1] ?? '';

            // Keep stress tags in the field value so Anki can render them as bold.
            $backFieldValue = trim($rawRussianText);
            $mp3StoragePath = null;
            $mp3FileName = null;

            // Append the Anki sound reference when a cached MP3 exists for this phrase.
            if (! empty(trim($rawRussianText)) && $this->ttsService->audioFileExists($rawRussianText)) {
                $filenameHash = $this->ttsService->buildFilenameHash($rawRussianText);
                $mp3FileName = "{$filenameHash}.mp3";
                $mp3StoragePath = "tts/{$mp3FileName}";
                $backFieldValue .= " [sound:{$mp3FileName}]";
            }

            $cards[] = [
                'front' => $sourceText,
                'back' => $backFieldValue,
                'mp3StoragePath' => $mp3StoragePath,
                'mp3FileName' => $mp3FileName,
            ];
        }

        return $cards;
    }
}
