<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesMassOperations;
use App\Livewire\Concerns\ManagesPersistence;
use App\Livewire\Concerns\ManagesTranslation;
use App\Livewire\Concerns\ManagesTtsAudio;
use App\Models\CsvDraft;
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

    public string $searchQuery = '';

    public bool $hasCsvLoaded = false;

    /** Database ID of the currently displayed draft. 0 means no draft persisted yet. */
    public int $activeDraftId = 0;

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

    /** Name entered by the user for the combined multi-deck .apkg export. */
    public string $collectionExportName = 'Collection';

    /** Hex color applied to stressed vowels in the exported CSV (#RRGGBB), or null for plain bold only. */
    public ?string $accentColor = null;

    /** Whether stressed vowels are bold in the exported CSV. */
    public bool $accentBold = true;

    /** When true, stressed vowels are exported as vowel + U+0301 (combining acute accent) instead of HTML tags. */
    public bool $accentUnicode = false;

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
        $user = auth()->user();
        $this->accentColor = $user?->accent_color;
        $this->accentBold = (bool) ($user?->accent_bold ?? true);
        $this->accentUnicode = (bool) ($user?->accent_unicode ?? false);
        $this->restoreFromDraft();
    }

    /**
     * Persist the user's accent style preference (color + bold) and update
     * the local Livewire state so the next CSV export uses the new values.
     */
    public function saveAccentStyle(?string $color, bool $bold, bool $unicode = false): void
    {
        if ($color !== null && ! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return;
        }

        $this->accentColor = $color;
        $this->accentBold = $bold;
        $this->accentUnicode = $unicode;

        auth()->user()->update([
            'accent_color' => $color,
            'accent_bold' => $bold,
            'accent_unicode' => $unicode,
        ]);
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

        // Normalize each row to match the first row's column count, and convert
        // any accent-marker format (font tags, combining accents) to <b>…</b>.
        $this->csvRows = array_map(function (array $row) use ($columnCount): array {
            $normalised = array_pad($row, $columnCount, '');
            $normalised = array_slice($normalised, 0, $columnCount);

            return array_map($this->accentService->normalizeImportedCellValue(...), $normalised);
        }, $parsed);

        $this->hasCsvLoaded = true;
        $this->uploadedCsvFile = null;
        // Force creation of a new draft for this upload instead of overwriting the active one.
        $this->activeDraftId = 0;
        $this->isRenamingFile = false;
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
        $rows = $this->filteredRows();
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * self::PER_PAGE;
        $slicedItems = collect(array_slice($rows, $offset, self::PER_PAGE, true));

        return new LengthAwarePaginator(
            $slicedItems,
            count($rows),
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
        return max(1, (int) ceil(count($this->filteredRows()) / self::PER_PAGE));
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
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
        $newRowIndex = array_key_last($this->csvRows);
        $this->autoSaveDraft();
        // Land on the last page so the new row is immediately visible.
        $this->setPage($this->totalPages);
        $this->dispatch('csv-row-added', rowIndex: $newRowIndex);
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
        $cards = $this->buildAnkiCardsFromCsvRows($this->csvRows);
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
     * Build and download a multi-deck .apkg package containing all of the user's files.
     * Only available when the user has at least two files.
     */
    public function downloadColpkg(): StreamedResponse
    {
        $allDrafts = CsvDraft::query()
            ->where('user_id', auth()->id())
            ->orderBy('id')
            ->get();

        $decks = [];

        foreach ($allDrafts as $draft) {
            $decks[] = [
                'deckName' => pathinfo($draft->original_file_name, PATHINFO_FILENAME) ?: 'Deck',
                'cards' => $this->buildAnkiCardsFromCsvRows($draft->csv_rows ?? []),
            ];
        }

        $parentName = trim($this->collectionExportName) ?: 'Collection';
        $apkgPath = $this->ankiExporterService->exportCollection($decks, $parentName);
        $downloadFileName = $parentName.'_'.now()->format('Ymd_His').'.apkg';

        return response()->streamDownload(function () use ($apkgPath): void {
            readfile($apkgPath);

            if (! @unlink($apkgPath)) {
                Log::warning('Failed to delete temporary combined .apkg file after streaming.', [
                    'path' => $apkgPath,
                ]);
            }
        }, $downloadFileName, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

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
            $this->activeDraftId = $next->id;
            $this->csvRows = $next->csv_rows ?? [];
            $this->originalFileName = $next->original_file_name;
            $this->hasCsvLoaded = $next->has_csv_loaded;
        } else {
            $this->csvRows = [];
            $this->originalFileName = '';
            $this->validationError = '';
            $this->hasCsvLoaded = false;
            $this->uploadedCsvFile = null;
            $this->activeDraftId = 0;
        }

        $this->isRenamingFile = false;
        $this->renameInput = '';
        $this->ttsModalRowIndex = -1;
        $this->searchQuery = '';
        $this->resetPage();
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

        $this->activeDraftId = $draft->id;
        $this->csvRows = $draft->csv_rows ?? [];
        $this->originalFileName = $draft->original_file_name;
        $this->hasCsvLoaded = $draft->has_csv_loaded;
        $this->isRenamingFile = false;
        $this->renameInput = '';
        $this->ttsModalRowIndex = -1;
        $this->searchQuery = '';
        $this->resetPage();
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
        $this->ttsModalRowIndex = -1;
        $this->searchQuery = '';
        $this->resetPage();
        $this->dispatch('csv-file-switched');

        $this->autoSaveDraft();
        $this->startRenameDraft();
    }

    /**
     * Show the rename input pre-filled with the current file base name.
     */
    public function startRenameDraft(): void
    {
        $this->renameInput = pathinfo($this->originalFileName, PATHINFO_FILENAME);
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

        $extension = pathinfo($this->originalFileName, PATHINFO_EXTENSION);
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

    public function render(): View
    {
        return view('livewire.csv-editor');
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
     *
     * Stressed vowels (stored as <b>X</b>) are re-encoded using the user's
     * current accent style: a <font color> wrapper is added when a color is
     * selected, and the <b> wrapper is kept or removed based on the bold flag.
     */
    private function buildCsvContent(): string
    {
        $buffer = fopen('php://temp', 'r+');

        if ($buffer === false) {
            return '';
        }

        try {
            foreach ($this->csvRows as $row) {
                fputcsv($buffer, array_map($this->applyAccentStyle(...), $row));
            }

            rewind($buffer);

            return stream_get_contents($buffer) ?: '';
        } finally {
            fclose($buffer);
        }
    }

    /**
     * Re-encode <b>X</b> accent markers in a cell value using the user's
     * chosen export style (color and/or bold).
     *
     * Possible outputs for a stressed vowel X:
     *   color + bold  → <font color="#HEX"><b>X</b></font>
     *   color only    → <font color="#HEX">X</font>
     *   bold only     → <b>X</b>  (unchanged — no color tag added)
     *
     * When multiple options are active they are combined:
     *   unicode + color + bold → <font color="#HEX"><b>X́</b></font>
     */
    private function applyAccentStyle(string $cellValue): string
    {
        if (! str_contains($cellValue, '<b>')) {
            return $cellValue;
        }

        $unicode = $this->accentUnicode;
        $bold = $this->accentBold;
        $color = $this->accentColor
            ? htmlspecialchars($this->accentColor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : null;

        // Nothing to do — keep the raw <b> tags as the accent position marker.
        if (! $unicode && ! $bold && ! $color) {
            return $cellValue;
        }

        return preg_replace_callback('/<b>(.*?)<\/b>/s', static function (array $m) use ($unicode, $bold, $color): string {
            $vowel = $m[1];

            // 1. Optionally append the combining acute accent (U+0301).
            //    ё/Ё are inherently stressed — no additional mark needed.
            $content = ($unicode && $vowel !== 'ё' && $vowel !== 'Ё')
                ? $vowel."\u{0301}"
                : $vowel;

            // 2. Optionally wrap in <b>.
            if ($bold) {
                $content = "<b>{$content}</b>";
            }

            // 3. Optionally wrap in <font color>.
            if ($color) {
                $content = "<font color=\"{$color}\">{$content}</font>";
            }

            return $content;
        }, $cellValue) ?? $cellValue;
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
     * from the given CSV rows, attaching cached MP3 references where available.
     *
     * @param  array<int, array<int, string>>  $csvRows
     * @return array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}>
     */
    private function buildAnkiCardsFromCsvRows(array $csvRows): array
    {
        $cards = [];

        foreach ($csvRows as $row) {
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

    /**
     * Return the subset of csvRows matching the current search query,
     * preserving original array keys so row-index-based actions keep working.
     *
     * @return array<int, array<int, string>>
     */
    private function filteredRows(): array
    {
        $normalizedQuery = $this->normalizeForSearch(trim($this->searchQuery));

        if ($normalizedQuery === '') {
            return $this->csvRows;
        }

        return array_filter(
            $this->csvRows,
            fn (array $row): bool => str_contains($this->normalizeForSearch($row[0] ?? ''), $normalizedQuery)
                || str_contains($this->normalizeForSearch($row[1] ?? ''), $normalizedQuery)
        );
    }

    /**
     * Strip HTML tags, remove combining acute accents (U+0301), and lowercase.
     * Mirrors the normalisation done client-side in window.csvSearch.normalizeText().
     */
    private function normalizeForSearch(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\x{0301}/u', '', $text) ?? $text;

        return mb_strtolower($text);
    }
}
