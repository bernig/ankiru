<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesMassOperations;
use App\Livewire\Concerns\ManagesPersistence;
use App\Livewire\Concerns\ManagesRowGeneration;
use App\Livewire\Concerns\ManagesTestMode;
use App\Livewire\Concerns\ManagesTranslation;
use App\Livewire\Concerns\ManagesTtsAudio;
use App\Models\CsvDraft;
use App\Services\AnkiPackageExporterService;
use App\Services\CreditService;
use App\Services\CsvImportService;
use App\Services\DeckExportService;
use App\Services\MassOperationService;
use App\Services\OpenAiTranslationService;
use App\Services\RowGenerationService;
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
    use ManagesRowGeneration;
    use ManagesTestMode;
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

    /** @var list<int> Page sizes offered by the per-page selector. */
    private const array ALLOWED_PER_PAGE = [10, 25, 50, 100];

    /** Number of rows displayed per page (user-configurable). */
    public int $perPage = 50;

    /** @var TemporaryUploadedFile|null */
    public $uploadedCsvFile = null;

    /**
     * Rows of the current page, keyed by their absolute index in $csvRows,
     * for the Alpine cell bindings. Derived from $csvRows on every render and
     * never read back, so a client-side change to it has no effect.
     *
     * @var array<int, array<int, string>>
     */
    public array $pageRows = [];

    /**
     * Whether each row of the current page has cached audio, keyed like
     * $pageRows. Derived on every render, so the row indicator always matches
     * the row now shown at that index (Alpine keeps a row's x-data state when
     * Livewire morphs it, which made a value frozen there go stale after a
     * deletion). Never read back.
     *
     * @var array<int, bool>
     */
    public array $pageAudio = [];

    public string $originalFileName = '';

    public string $validationError = '';

    public string $searchQuery = '';

    public bool $filterAccentNeeded = false;

    public bool $filterNoAudio = false;

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
     * All rows of the active draft, indexed by column position.
     *
     * Kept out of the Livewire snapshot on purpose: a deck can hold thousands
     * of rows, and a public property would travel to the browser and back on
     * every request. The draft in the database is the source of truth, reloaded
     * once per request in hydrate(); only the visible page is sent to the
     * client, through $pageRows.
     *
     * @var array<int, array<int, string>>
     */
    protected array $csvRows = [];

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

    protected RowGenerationService $rowGenerationService;

    protected CreditService $creditService;

    protected CsvImportService $csvImportService;

    protected DeckExportService $deckExportService;

    /**
     * Called by Livewire before every action (mount and subsequent requests).
     */
    public function boot(
        OpenAiTranslationService $translationService,
        RussianAccentService $accentService,
        RussianTextToSpeechService $ttsService,
        AnkiPackageExporterService $ankiExporterService,
        MassOperationService $massOperationService,
        RowGenerationService $rowGenerationService,
        CreditService $creditService,
        CsvImportService $csvImportService,
        DeckExportService $deckExportService,
    ): void {
        $this->translationService = $translationService;
        $this->accentService = $accentService;
        $this->ttsService = $ttsService;
        $this->ankiExporterService = $ankiExporterService;
        $this->massOperationService = $massOperationService;
        $this->rowGenerationService = $rowGenerationService;
        $this->creditService = $creditService;
        $this->csvImportService = $csvImportService;
        $this->deckExportService = $deckExportService;
    }

    /**
     * Reload the rows from the database on every subsequent request, since
     * they are not part of the snapshot.
     */
    public function hydrate(): void
    {
        $this->csvRows = $this->loadActiveDraftRows();
    }

    public function mount(): void
    {
        $user = auth()->user();
        $this->accentColor = $user?->accent_color;
        $this->accentBold = (bool) ($user?->accent_bold ?? true);
        $this->accentUnicode = (bool) ($user?->accent_unicode ?? false);
        $this->generateRowsContext = $user?->learning_context ?? '';
        $this->restoreFromDraft();
        $this->maybeAutoStartMainTour();
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

        $parsedRows = $this->csvImportService->parse($fileContent);

        if ($parsedRows === false) {
            $this->validationError = __('csv_editor.error_csv_empty_or_malformed');

            return;
        }

        $this->csvRows = $parsedRows;

        $this->hasCsvLoaded = true;
        $this->uploadedCsvFile = null;
        // Force creation of a new draft for this upload instead of overwriting the active one.
        $this->activeDraftId = 0;
        $this->isRenamingFile = false;
        $this->resetPage();

        $this->autoSaveDraft();
        $this->maybeAutoStartMainTour();
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
        $rows = $this->filteredRows;
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * $this->perPage;
        $slicedItems = collect(array_slice($rows, $offset, $this->perPage, true));

        return new LengthAwarePaginator(
            $slicedItems,
            count($rows),
            $this->perPage,
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
        return max(1, (int) ceil(count($this->filteredRows) / $this->perPage));
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAccentNeeded(): void
    {
        $this->resetPage();
    }

    public function updatedFilterNoAudio(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, self::ALLOWED_PER_PAGE, true)) {
            $this->perPage = 50;
        }

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

    // -------------------------------------------------------------------------
    // Export
    // -------------------------------------------------------------------------

    /**
     * Trigger a browser download of the current CSV data as a plain CSV file.
     */
    public function downloadCsv(): StreamedResponse
    {
        $csvContent = $this->deckExportService->toCsv($this->csvRows, $this->accentColor, $this->accentBold, $this->accentUnicode);
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
        $cards = $this->deckExportService->toAnkiCards($this->csvRows, $this->activeDraftId);
        $deckName = pathinfo($this->originalFileName, PATHINFO_FILENAME) ?: 'French-Russian';
        $apkgPath = $this->ankiExporterService->export($cards, $deckName);

        $downloadFileName = $this->buildBaseFileName().'_'.now()->format('Ymd_His').'.apkg';

        return $this->streamApkgFileDownload($apkgPath, $downloadFileName);
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
                'cards' => $this->deckExportService->toAnkiCards($draft->csv_rows ?? [], $draft->id),
            ];
        }

        $parentName = trim($this->collectionExportName) ?: 'Collection';
        $apkgPath = $this->ankiExporterService->exportCollection($decks, $parentName);
        $downloadFileName = $this->deckExportService->toDownloadFileName($parentName, 'Collection').'_'.now()->format('Ymd_His').'.apkg';

        return $this->streamApkgFileDownload($apkgPath, $downloadFileName);
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

    /**
     * The client is not meant to change the active draft directly (that goes
     * through switchToDraft), but if it does, the rows must follow so they are
     * never saved into another draft.
     */
    public function updatedActiveDraftId(): void
    {
        $this->csvRows = $this->loadActiveDraftRows();
    }

    public function render(): View
    {
        $this->pageRows = $this->paginatedRows->all();
        $this->pageAudio = $this->audioExistenceByRowIndex;
        $this->syncTestCards();

        return view('livewire.csv-editor');
    }

    /**
     * Whether at least one row of the deck has Russian text, used by the
     * mobile edit modal to label its AI actions.
     */
    #[Computed]
    public function hasAnyRussianText(): bool
    {
        return collect($this->csvRows)->contains(fn (array $row): bool => trim($row[1] ?? '') !== '');
    }

    /**
     * Return the subset of csvRows matching the active search query and toggle
     * filters, preserving original array keys so row-index-based actions work.
     *
     * @return array<int, array<int, string>>
     */
    #[Computed]
    public function filteredRows(): array
    {
        $rows = $this->csvRows;

        $normalizedQuery = $this->normalizeForSearch(trim($this->searchQuery));

        if ($normalizedQuery !== '') {
            $rows = array_filter(
                $rows,
                fn (array $row): bool => str_contains($this->normalizeForSearch($row[0] ?? ''), $normalizedQuery)
                    || str_contains($this->normalizeForSearch($row[1] ?? ''), $normalizedQuery)
            );
        }

        if ($this->filterAccentNeeded) {
            $rows = array_filter(
                $rows,
                fn (array $row): bool => $this->accentService->textNeedsStressCorrection($row[1] ?? '')
            );
        }

        if ($this->filterNoAudio) {
            $russianTexts = array_values(array_filter(
                array_map(fn (array $row): string => $row[1] ?? '', $rows),
                fn (string $text): bool => trim($text) !== '',
            ));
            $audioExistence = $this->ttsService->audioFilesExistBatch($russianTexts);
            $rows = array_filter(
                $rows,
                fn (array $row): bool => trim($row[1] ?? '') !== '' && ! ($audioExistence[$row[1] ?? ''] ?? false)
            );
        }

        return $rows;
    }

    /**
     * Auto-starts the full guided tour the first time this user has a loaded
     * file with at least one row, so there is something on screen to point
     * at. Marked as seen immediately so it never fires again automatically,
     * regardless of whether the user finishes or dismisses it; the header's
     * "Guided tour" button remains available to replay it manually.
     */
    private function maybeAutoStartMainTour(): void
    {
        $user = auth()->user();

        if (! $user || $user->tour_completed_at || ! $this->hasCsvLoaded || count($this->csvRows) === 0) {
            return;
        }

        $user->tour_completed_at = now();
        $user->save();

        $this->dispatch('start-main-tour');
    }

    /**
     * Shows a one-off tip pointing at the row's action icons the first time
     * this user ever adds a row to a file, since that's the moment the
     * translate/stress/audio icons first become relevant.
     */
    private function maybeStartFirstRowTip(): void
    {
        $user = auth()->user();

        if (! $user || $user->tour_first_row_tip_seen_at) {
            return;
        }

        $user->tour_first_row_tip_seen_at = now();
        $user->save();

        $this->dispatch('start-first-row-tip');
    }

    /**
     * Shows a one-off tip pointing at the "add row" area the first time
     * this user ever creates a new empty file, since that's the moment
     * they most need to know how to get started.
     */
    private function maybeStartNewFileTip(): void
    {
        $user = auth()->user();

        if (! $user || $user->tour_new_file_tip_seen_at) {
            return;
        }

        $user->tour_new_file_tip_seen_at = now();
        $user->save();

        $this->dispatch('start-new-file-tip');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Returns true and opens the API key setup modal when the user has no
     * OpenAI key configured. Callers should return immediately when true.
     */
    private function apiKeyMissing(): bool
    {
        $user = auth()->user();

        if ($user?->credits > 0) {
            return false; // Les crédits ont la priorité
        }

        if ($user?->hasPersonalApiKey()) {
            return false; // Clé personnelle en fallback
        }

        $this->dispatch('open-openai-key-setup');

        return true;
    }

    /**
     * Reset UI-only state that must be cleared whenever a different file is
     * opened (filters, search, rename input, TTS modal, pagination).
     */
    private function resetFiltersAndPagination(): void
    {
        $this->isRenamingFile = false;
        $this->renameInput = '';
        $this->ttsModalRowIndex = -1;
        $this->searchQuery = '';
        $this->filterAccentNeeded = false;
        $this->filterNoAudio = false;
        $this->resetPage();
    }

    /**
     * Stream an .apkg file to the browser then delete the temporary file.
     */
    private function streamApkgFileDownload(string $apkgPath, string $downloadFileName): StreamedResponse
    {
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
     * Derive the base file name (without extension) used for all download file names.
     * Falls back to 'export' when no original file name is available.
     */
    private function buildBaseFileName(): string
    {
        return $this->deckExportService->toDownloadFileName(pathinfo($this->originalFileName, PATHINFO_FILENAME), 'export');
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
