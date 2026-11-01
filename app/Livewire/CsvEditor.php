<?php

namespace App\Livewire;

use App\Livewire\Concerns\ManagesGuidedTour;
use App\Livewire\Concerns\ManagesMassOperations;
use App\Livewire\Concerns\ManagesPersistence;
use App\Livewire\Concerns\ManagesRowEditing;
use App\Livewire\Concerns\ManagesRowFilters;
use App\Livewire\Concerns\ManagesRowGeneration;
use App\Livewire\Concerns\ManagesStressBatchResults;
use App\Livewire\Concerns\ManagesTestMode;
use App\Livewire\Concerns\ManagesTranslation;
use App\Livewire\Concerns\ManagesTtsAudio;
use App\Services\CreditService;
use App\Services\CsvImportService;
use App\Services\MassOperationService;
use App\Services\OpenAiTranslationService;
use App\Services\RowGenerationService;
use App\Services\RussianAccentService;
use App\Services\RussianTextToSpeechService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CsvEditor extends Component
{
    use ManagesGuidedTour;
    use ManagesMassOperations;
    use ManagesPersistence;
    use ManagesRowEditing;
    use ManagesRowFilters;
    use ManagesRowGeneration;
    use ManagesStressBatchResults;
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

    public bool $hasCsvLoaded = false;

    /** Database ID of the currently displayed draft. 0 means no draft persisted yet. */
    #[Locked]
    public int $activeDraftId = 0;

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

    protected MassOperationService $massOperationService;

    protected RowGenerationService $rowGenerationService;

    protected CreditService $creditService;

    protected CsvImportService $csvImportService;

    /**
     * Called by Livewire before every action (mount and subsequent requests).
     */
    public function boot(
        OpenAiTranslationService $translationService,
        RussianAccentService $accentService,
        RussianTextToSpeechService $ttsService,
        MassOperationService $massOperationService,
        RowGenerationService $rowGenerationService,
        CreditService $creditService,
        CsvImportService $csvImportService,
    ): void {
        $this->translationService = $translationService;
        $this->accentService = $accentService;
        $this->ttsService = $ttsService;
        $this->massOperationService = $massOperationService;
        $this->rowGenerationService = $rowGenerationService;
        $this->creditService = $creditService;
        $this->csvImportService = $csvImportService;
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
    // Pagination (the rest lives in ManagesRowFilters)
    // -------------------------------------------------------------------------

    /**
     * Navigate to the given page number (clamped to valid range).
     * Overrides WithPagination::setPage() to prevent out-of-range navigation;
     * it stays in the class, since a trait method of the same name would
     * collide with the one of WithPagination.
     *
     * @param  int|string  $page
     */
    public function setPage($page, $pageName = 'page'): void
    {
        $this->paginationSetPage(max(1, min((int) $page, $this->totalPages)), $pageName);
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Returns true and opens the API key setup modal when the user can use
     * neither purchased credits nor a personal OpenAI key. Callers should
     * return immediately when true.
     */
    private function apiKeyMissing(): bool
    {
        if (auth()->user()?->canUseAi()) {
            return false;
        }

        $this->dispatch('open-openai-key-setup');

        return true;
    }

    /**
     * Whether the current user can start an AI action estimated at
     * $estimatedCredits. When the platform balance falls short, opens the
     * credits shop and returns false; the caller then shows its own error.
     */
    private function ensureCanAfford(int $estimatedCredits): bool
    {
        if ($this->creditService->canAfford(Auth::user(), $estimatedCredits)) {
            return true;
        }

        $this->dispatch('open-credits-shop');

        return false;
    }

    /**
     * Reset UI-only state that must be cleared whenever a different file is
     * opened (filters, search, rename input, TTS modal, undo, pagination).
     */
    private function resetFiltersAndPagination(): void
    {
        $this->isRenamingFile = false;
        $this->renameInput = '';
        $this->ttsModalRowIndex = -1;
        $this->searchQuery = '';
        $this->filterAccentNeeded = false;
        $this->filterNoAudio = false;
        $this->lastDeletedRow = null;
        $this->forgetStressBatchUndo();
        $this->resetPage();
    }
}
