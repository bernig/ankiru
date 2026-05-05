<?php

namespace App\Livewire;

use App\Services\AnkiPackageExporterService;
use App\Services\OpenAiTranslationService;
use App\Services\RussianAccentService;
use App\Services\RussianTextToSpeechService;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Laravel\Ai\Exceptions\FailoverableException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvEditor extends Component
{
    use WithFileUploads;

    /**
     * Alias the trait's setPage so we can override it with clamping logic
     * while still delegating to the original implementation.
     */
    use WithPagination {
        setPage as paginationSetPage;
    }

    /** Path to the temp file used for auto-saving between sessions */
    private const string TEMP_FILE_PATH = 'csv_editor_temp.json';

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

    /** Row index currently being translated via ChatGPT (-1 = none). */
    public int $translatingRowIndex = -1;

    /** Row index currently having stress corrected via ChatGPT (-1 = none). */
    public int $correctingStressRowIndex = -1;

    /** Error message from the last ChatGPT translation or stress-correction attempt. */
    public string $translationError = '';

    /** Row index currently generating TTS audio (-1 = none). */
    public int $ttsGeneratingRowIndex = -1;

    /** Error message from the last TTS generation attempt. */
    public string $ttsError = '';

    /**
     * Row index whose audio is displayed in the TTS player modal (-1 = none).
     * Reset to -1 whenever a row is deleted to prevent stale references.
     */
    public int $ttsModalRowIndex = -1;

    private OpenAiTranslationService $translationService;

    private RussianAccentService $accentService;

    private RussianTextToSpeechService $ttsService;

    /**
     * Called by Livewire before every action (mount and subsequent requests).
     * Services are re-injected on each hydration cycle because they are not
     * serialized as component state.
     */
    public function boot(
        OpenAiTranslationService $translationService,
        RussianAccentService $accentService,
        RussianTextToSpeechService $ttsService,
    ): void {
        $this->translationService = $translationService;
        $this->accentService = $accentService;
        $this->ttsService = $ttsService;
    }

    public function mount(): void
    {
        $this->restoreFromTempFile();
    }

    /**
     * Livewire lifecycle hook: called automatically after uploadedCsvFile is set.
     * Enables auto-upload behaviour without a submit button.
     */
    public function updatedUploadedCsvFile(): void
    {
        $this->uploadCsv();
    }

    /**
     * Handle the CSV file upload, parse rows, and persist to temp file.
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

        $this->autoSaveToTempFile();
    }

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

        $this->autoSaveToTempFile();
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

        $this->autoSaveToTempFile();
    }

    /**
     * Append an empty row filled with empty strings for each column,
     * then navigate to the last page where the new row appears.
     */
    public function addRow(): void
    {
        $columnCount = count(reset($this->csvRows) ?: []) ?: 2;
        $this->csvRows[] = array_fill(0, $columnCount, '');
        $this->autoSaveToTempFile();
        // Land on the last page so the new row is immediately visible.
        $this->setPage($this->totalPages);
    }

    /**
     * Translate the French text in column 0 of the given row to Russian using
     * the ChatGPT API, and insert the result (with <b>…</b> accent markers on
     * stressed vowels) into column 1.
     */
    public function translateWithChatGpt(int $rowIndex): void
    {
        $this->translationError = '';

        $frenchText = $this->csvRows[$rowIndex][0] ?? '';

        if (empty(trim($frenchText))) {
            return;
        }

        $this->translatingRowIndex = $rowIndex;

        try {
            $translatedText = $this->translationService->translateFrenchToRussian($frenchText);
            $this->csvRows[$rowIndex][1] = $translatedText;
            $this->autoSaveToTempFile();
        } catch (Exception $exception) {
            $this->translationError = $exception->getMessage();
        } finally {
            $this->translatingRowIndex = -1;
        }
    }

    /**
     * Ask ChatGPT to review and fix stress marks in column 1 of the given row.
     * The French source in column 0 is sent as semantic context.
     */
    public function correctStressMarks(int $rowIndex): void
    {
        $this->translationError = '';

        $frenchText = trim($this->csvRows[$rowIndex][0] ?? '');
        $russianText = trim($this->csvRows[$rowIndex][1] ?? '');

        if ($russianText === '') {
            return;
        }

        $this->correctingStressRowIndex = $rowIndex;

        try {
            $correctedText = $this->translationService->correctRussianStressMarks($russianText, $frenchText);

            if ($correctedText !== $russianText) {
                $this->csvRows[$rowIndex][1] = $correctedText;
                $this->autoSaveToTempFile();
            }
        } catch (Exception $exception) {
            $this->translationError = $exception->getMessage();
        } finally {
            $this->correctingStressRowIndex = -1;
        }
    }

    /**
     * Generate (or retrieve from cache) high-quality TTS audio for the Russian
     * phrase in column 1 of the given row, then dispatch a browser event so
     * Alpine.js can play the returned audio URL immediately.
     */
    public function generateTtsAudio(int $rowIndex): void
    {
        $this->ttsError = '';

        $rawRussianText = $this->csvRows[$rowIndex][1] ?? '';
        $normalizedText = trim(str_replace(['<b>', '</b>'], '', $rawRussianText));

        if (empty($normalizedText)) {
            return;
        }

        $this->ttsGeneratingRowIndex = $rowIndex;

        try {
            $this->ttsService->generateAudio($rawRussianText);

            $cacheKey = $this->ttsService->hashRawString($rawRussianText);
            // Append a cache-busting timestamp so browsers always fetch the latest audio.
            $audioUrl = route('tts.serve', $cacheKey).'?v='.time();

            $this->dispatch('tts-audio-ready', audioUrl: $audioUrl);
        } catch (Exception|FailoverableException $exception) {
            $this->ttsError = __('csv_editor.error_audio_generation_failed', ['message' => $exception->getMessage()]);
        } finally {
            $this->ttsGeneratingRowIndex = -1;
        }
    }

    /**
     * Delete the cached TTS audio file for the Russian phrase in column 1 of the
     * given row. The UI icon then reverts to "generate".
     */
    public function deleteTtsAudio(int $rowIndex): void
    {
        $rawRussianText = $this->csvRows[$rowIndex][1] ?? '';

        if (empty(trim($rawRussianText))) {
            return;
        }

        $this->ttsService->deleteAudio($rawRussianText);
    }

    /**
     * Return true when a cached audio file exists for the Russian phrase in
     * column 1 of the given row.
     */
    public function ttsAudioExistsForRow(int $rowIndex): bool
    {
        $rawRussianText = $this->csvRows[$rowIndex][1] ?? '';

        if (empty(trim($rawRussianText))) {
            return false;
        }

        return $this->ttsService->audioFileExists($rawRussianText);
    }

    /**
     * Open the TTS audio player modal for the given row.
     * Dispatches open-tts-modal with the current audio URL (or null when no
     * cached file exists yet).
     */
    public function openTtsModal(int $rowIndex): void
    {
        $this->ttsModalRowIndex = $rowIndex;

        $rawRussianText = $this->csvRows[$rowIndex][1] ?? '';
        $audioUrl = null;

        if (! empty(trim($rawRussianText)) && $this->ttsService->audioFileExists($rawRussianText)) {
            $cacheKey = $this->ttsService->hashRawString($rawRussianText);
            $lastModified = Storage::disk('local')->lastModified("tts/{$cacheKey}.mp3");
            $audioUrl = route('tts.serve', $cacheKey).'?v='.$lastModified;
        }

        $this->dispatch('open-tts-modal', audioUrl: $audioUrl);
    }

    /**
     * Delete the existing cached audio and immediately regenerate it,
     * producing a fresh recording for the current Russian phrase.
     */
    public function refreshTtsAudio(int $rowIndex): void
    {
        $this->ttsService->deleteAudio($this->csvRows[$rowIndex][1] ?? '');
        $this->generateTtsAudio($rowIndex);
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
        $this->autoSaveToTempFile();
        // Clamp the current page in case the last page was emptied by this deletion.
        if ($this->getPage() > $this->totalPages) {
            $this->setPage($this->totalPages);
        }
    }

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
        /** @var AnkiPackageExporterService $exporter */
        $exporter = app(AnkiPackageExporterService::class);

        $cards = $this->buildAnkiCardsFromRows();
        $deckName = pathinfo($this->originalFileName, PATHINFO_FILENAME) ?: 'French-Russian';
        $apkgPath = $exporter->export($cards, $deckName);

        $downloadFileName = $this->buildBaseFileName().'_'.now()->format('Ymd_His').'.apkg';

        return response()->streamDownload(function () use ($apkgPath): void {
            readfile($apkgPath);
            @unlink($apkgPath);
        }, $downloadFileName, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Clear the loaded CSV and the temp file, returning to the upload screen.
     */
    public function resetEditor(): void
    {
        $this->csvRows = [];
        $this->originalFileName = '';
        $this->validationError = '';
        $this->hasCsvLoaded = false;
        $this->uploadedCsvFile = null;
        $this->resetPage();

        $this->clearTempFile();
    }

    public function render(): View
    {
        return view('livewire.csv-editor')
            ->layout('layouts.app');
    }

    // -------------------------------------------------------------------------
    // Stress-correction helpers
    // -------------------------------------------------------------------------

    /**
     * Return true when the Russian text in column 1 of the given row needs a
     * stress mark added (delegates to RussianAccentService).
     */
    public function rowNeedsStressCorrection(int $rowIndex): bool
    {
        return $this->accentService->textNeedsStressCorrection(
            $this->csvRows[$rowIndex][1] ?? ''
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse raw CSV content into a 2D array.
     *
     * @return array<int, array<int, string>>|false
     */
    private function parseCsvContent(string $content): array|false
    {
        $normalisedContent = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", trim($normalisedContent));

        if (count($lines) === 0) {
            return false;
        }

        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $rows[] = array_map('strval', str_getcsv($line));
        }

        return count($rows) > 0 ? $rows : false;
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

        foreach ($this->csvRows as $row) {
            fputcsv($buffer, $row);
        }

        rewind($buffer);
        $csvContent = stream_get_contents($buffer);
        fclose($buffer);

        return $csvContent ?: '';
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
            $frenchText = $row[0] ?? '';
            $rawRussianText = $row[1] ?? '';

            // Strip <b> stress tags — Anki does not render them as bold in basic fields.
            $plainRussianText = trim(str_replace(['<b>', '</b>'], '', $rawRussianText));

            $backFieldValue = $plainRussianText;
            $mp3StoragePath = null;
            $mp3FileName = null;

            // Append the Anki sound reference when a cached MP3 exists for this phrase.
            if (! empty(trim($rawRussianText)) && $this->ttsService->audioFileExists($rawRussianText)) {
                $cacheKey = $this->ttsService->hashRawString($rawRussianText);
                $mp3FileName = "{$cacheKey}.mp3";
                $mp3StoragePath = "tts/{$mp3FileName}";
                $backFieldValue .= " [sound:{$mp3FileName}]";
            }

            $cards[] = [
                'front' => $frenchText,
                'back' => $backFieldValue,
                'mp3StoragePath' => $mp3StoragePath,
                'mp3FileName' => $mp3FileName,
            ];
        }

        return $cards;
    }

    /**
     * Persist the current editor state to a JSON temp file in storage.
     */
    private function autoSaveToTempFile(): void
    {
        $data = [
            'csvRows' => $this->csvRows,
            'originalFileName' => $this->originalFileName,
            'hasCsvLoaded' => $this->hasCsvLoaded,
            'savedAt' => now()->toIso8601String(),
        ];

        file_put_contents(storage_path('app/'.self::TEMP_FILE_PATH), json_encode($data));
    }

    /**
     * Restore editor state from the temp file if it exists.
     */
    private function restoreFromTempFile(): void
    {
        $tempFilePath = storage_path('app/'.self::TEMP_FILE_PATH);

        if (! file_exists($tempFilePath)) {
            return;
        }

        $rawJson = file_get_contents($tempFilePath);

        if ($rawJson === false) {
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
     * Remove the temp file from storage.
     */
    private function clearTempFile(): void
    {
        $tempFilePath = storage_path('app/'.self::TEMP_FILE_PATH);

        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
    }
}
