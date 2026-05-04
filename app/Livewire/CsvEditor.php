<?php

namespace App\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
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

    /*const TRANSLATION_PROMPT = <<<'PROMPT'
You are a professional French → Russian translator.

TASK:
Translate the French text into natural, fluent Russian. Preserve meaning and tone. Do not translate literally.

STRESS MARKING RULES:
After translating, mark lexical stress in Russian words using <b>...</b>:

• Vowels: а е ё и о у ы э ю я
• Only words with TWO OR MORE vowels are tagged
• Mark EXACTLY ONE stressed vowel per eligible word
• Words with ONE vowel → no tag
• Never mark more than one vowel per word
• The tagged character must be a vowel (never a consonant)

IMPORTANT:
• Use correct Russian stress (not random or mechanical)
• If you are unsure about the stress position, do NOT add any tag for that word
• The letter "ё" is ALWAYS stressed → always wrap it in <b>ё</b> if present
• Do not tag:

* abbreviations (e.g., ПК, США)
* numbers
* punctuation

OUTPUT:
Return ONLY the final Russian translation with stress tags. No explanations.

EXAMPLE:
Input: Je pense que je vais acheter un nouveau PC.
Output: Я д<b>у</b>маю, что купл<b>ю</b> н<b>о</b>вый ПК.

PROMPT;*/

    const TRANSLATION_PROMPT = <<<'PROMPT'
You are a professional French → Russian translator.

Translate into natural Russian (not literal).

Mark stress using <b>...</b>:
• Vowels: а е ё и о у ы э ю я
• Only words with ≥2 vowels
• Exactly ONE stressed vowel per word
• 1 vowel → no tag
• Never tag consonants

Rules:
• Use correct stress
• If unsure → no tag
• "ё" is always stressed → <b>ё</b>
• No tags for abbreviations, numbers, punctuation

Output only the final Russian text with tags.
PROMPT;

    const STRESS_CORRECTION_PROMPT = <<<'PROMPT'
You are a Russian stress-mark reviewer and native-pronunciation expert.

Input: Russian text with stress marks encoded as <b>vowel</b> (one bold vowel per word marks the stress).
You may also receive the original French source sentence as semantic context; use it only to disambiguate meaning, and only edit the Russian text.

Task: review and correct every stress mark so that it reflects standard contemporary Russian pronunciation.

Tagging rules:
• Only words with ≥2 vowels get a mark
• Eligible words should contain exactly ONE stressed vowel mark, unless the stress is genuinely uncertain from context
• "ё" is ALWAYS stressed → must be written as <b>ё</b>
• No tags on abbreviations, numbers, or punctuation
• Vowels: а е ё и о у ы э ю я

Pronunciation rules (apply these with the highest priority):
• Use the stress that matches standard contemporary spoken Russian (modern literary norm)
• Read the full sentence for context; if a word's stress depends on meaning or grammatical form, choose the stress that fits THIS sentence
• Prioritise natural, native-speaker pronunciation over dictionary headword placement when the two differ in colloquial use

Strict preservation rules (do not violate these):
• Do NOT rewrite, paraphrase, reorder, or alter any word — your only permitted action is moving, adding, or removing a <b>...</b> tag around a single vowel
• Do NOT change spelling, capitalisation, punctuation, spaces, or any character except by adding, moving, or removing the literal tags <b> and </b>
• If you are uncertain about the correct stress for a word, remove its tag entirely — do not guess; an untagged word is always safer than a wrong tag
• Never change the grammatical form of a word (case, number, tense, aspect, etc.) even if an alternative form would carry a “nicer” stress

Return ONLY the corrected text with <b>...</b> tags. No explanations.
If already correct, return the text unchanged.
PROMPT;

    /** Path to the temp file used for auto-saving between sessions */
    private const TEMP_FILE_PATH = 'csv_editor_temp.json';

    /** Number of rows displayed per page. */
    private const PER_PAGE = 50;

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

    public bool $isRussianAccentMode = false;

    /**
     * Row index for which per-row accent mode is active (-1 = none).
     * Allows enabling accent mode for a single row without toggling it globally.
     */
    public int $accentModeRowIndex = -1;

    /** Row index currently being translated via ChatGPT (-1 = none). */
    public int $translatingRowIndex = -1;

    /** Row index currently having stress corrected via ChatGPT (-1 = none). */
    public int $correctingStressRowIndex = -1;

    /** Error message from the last ChatGPT translation attempt. */
    public string $translationError = '';

    /**
     * Tracks stress correction outcomes per row (transient, not persisted).
     * Values: 'corrected' = text was changed, 'ok' = text was already correct.
     *
     * @var array<int, string>
     */
    public array $stressCorrectionStatus = [];

    public function mount(): void
    {
        $this->restoreFromTempFile();
    }

    /**
     * Livewire lifecycle hook: called automatically after uploadedCsvFile is set.
     * This enables autoupload behaviour without a submit button.
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
            $this->validationError = 'Could not read the uploaded file.';

            return;
        }

        /** @var array<int, array<int, string>>|false $parsed */
        $parsed = $this->parseCsvContent($fileContent);

        if ($parsed === false || count($parsed) === 0) {
            $this->validationError = 'The CSV file appears to be empty or malformed.';

            return;
        }

        $columnCount = count($parsed[0]);

        // Normalise each row to match the first row's column count
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
     * Toggle Russian accent mode on or off.
     * Also clears any active per-row accent mode.
     */
    public function toggleRussianAccentMode(): void
    {
        $this->isRussianAccentMode = ! $this->isRussianAccentMode;
        $this->accentModeRowIndex = -1;
    }

    /**
     * Toggle per-row accent mode for the given row's right column.
     * Disables itself when the same row is clicked again.
     */
    public function toggleRowAccentMode(int $rowIndex): void
    {
        $this->accentModeRowIndex = ($this->accentModeRowIndex === $rowIndex) ? -1 : $rowIndex;
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

        $this->csvRows[$rowIndex][$columnIndex] = $this->moveAccentToPosition(
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

        // Clear any correction status when the translated column is manually edited.
        if ($columnIndex === 1) {
            unset($this->stressCorrectionStatus[$rowIndex]);
        }

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

        $openAiApiKey = config('services.openai.api_key');

        if (empty($openAiApiKey)) {
            $this->translationError = 'OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.';

            return;
        }

        $frenchText = $this->csvRows[$rowIndex][0] ?? '';

        if (empty(trim($frenchText))) {
            return;
        }

        $this->translatingRowIndex = $rowIndex;

        try {
            $openAiModel = config('services.openai.model', 'gpt-4o-mini');

            $response = Http::withToken($openAiApiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $openAiModel,
                    'input' => $frenchText,
                    'instructions' => self::TRANSLATION_PROMPT,
                    'temperature' => 0,
                ]);

            if (! $response->successful()) {
                $this->translationError = 'ChatGPT API returned an error: '.$response->status().'. Check your API key and quota.';

                return;
            }

            $translatedText = $response->json('output.0.content.0.text');

            if (! is_string($translatedText) || empty(trim($translatedText))) {
                $this->translationError = 'ChatGPT returned an empty translation.';

                return;
            }

            $this->csvRows[$rowIndex][1] = trim($translatedText);
            $this->autoSaveToTempFile();
            // A fresh translation supersedes any previous correction status.
            unset($this->stressCorrectionStatus[$rowIndex]);

        } catch (\Exception $exception) {
            $this->translationError = 'Translation failed: '.$exception->getMessage();
        } finally {
            $this->translatingRowIndex = -1;
        }
    }

    /**
     * Ask ChatGPT to review and fix stress marks in column 1 of the given row.
     * Updates the cell if corrections were made, and records the outcome in
     * $stressCorrectionStatus so the view can reflect the result visually.
     */
    public function correctStressMarks(int $rowIndex): void
    {
        $this->translationError = '';

        $openAiApiKey = config('services.openai.api_key');

        if (empty($openAiApiKey)) {
            $this->translationError = 'OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.';

            return;
        }

        $frenchText = trim($this->csvRows[$rowIndex][0] ?? '');
        $russianText = trim($this->csvRows[$rowIndex][1] ?? '');

        if ($russianText === '') {
            return;
        }

        $this->correctingStressRowIndex = $rowIndex;

        try {
            $openAiModel = config('services.openai.model', 'gpt-4o-mini');

            $stressCorrectionInput = <<<TEXT
French source for meaning/context only:
---
{$frenchText}
---

Russian text to review and correct stress marks in:
---
{$russianText}
---
TEXT;

            $response = Http::withToken($openAiApiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $openAiModel,
                    'input' => $stressCorrectionInput,
                    'instructions' => self::STRESS_CORRECTION_PROMPT,
                    'temperature' => 0,
                ]);

            if (! $response->successful()) {
                $this->translationError = 'ChatGPT API returned an error: '.$response->status().'. Check your API key and quota.';

                return;
            }

            $correctedText = $response->json('output.0.content.0.text');

            if (! is_string($correctedText) || empty(trim($correctedText))) {
                $this->translationError = 'ChatGPT returned an empty response.';

                return;
            }

            $correctedText = trim($correctedText);

            if ($correctedText !== $russianText) {
                $this->csvRows[$rowIndex][1] = $correctedText;
                $this->stressCorrectionStatus[$rowIndex] = 'corrected';
                $this->autoSaveToTempFile();
            } else {
                $this->stressCorrectionStatus[$rowIndex] = 'ok';
            }

        } catch (\Exception $exception) {
            $this->translationError = 'Stress correction failed: '.$exception->getMessage();
        } finally {
            $this->correctingStressRowIndex = -1;
        }
    }

    /**
     * Delete a row by its index and re-index the rows array.
     */
    public function deleteRow(int $rowIndex): void
    {
        unset($this->csvRows[$rowIndex]);
        $this->csvRows = array_values($this->csvRows);
        // Row indices have shifted — clear all correction statuses and per-row accent mode to avoid stale state.
        $this->stressCorrectionStatus = [];
        $this->accentModeRowIndex = -1;
        $this->autoSaveToTempFile();
        // Clamp the current page in case the last page was emptied by this deletion.
        if ($this->getPage() > $this->totalPages) {
            $this->setPage($this->totalPages);
        }
    }

    /**
     * Trigger a browser download of the current CSV data as a new file.
     */
    public function downloadCsv(): StreamedResponse
    {
        $downloadFileName = $this->buildDownloadFileName();

        $csvContent = $this->buildCsvContent();

        return response()->streamDownload(function () use ($csvContent): void {
            echo $csvContent;
        }, $downloadFileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
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
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse raw CSV content into a 2D array.
     *
     * @return array<int, array<int, string>>|false
     */
    private function parseCsvContent(string $content): array|false
    {
        // Normalise line endings
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

            $row = str_getcsv($line);
            $rows[] = array_map('strval', $row);
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
     * Generate a download file name based on the original file name.
     */
    private function buildDownloadFileName(): string
    {
        $baseName = pathinfo($this->originalFileName, PATHINFO_FILENAME);

        if (empty($baseName)) {
            $baseName = 'export';
        }

        return $baseName.'_edited_'.now()->format('Ymd_His').'.csv';
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

    /**
     * Move the <b> accent marker to the Russian vowel at the given plain-text
     * position, touching ONLY the word that contains that position.
     * Accent markers on all other words in the same cell are preserved.
     *
     * @param  string  $rawText  Cell content possibly containing <b>…</b> tags.
     * @param  int  $charPosition  0-based Unicode character index in stripped text.
     */
    private function moveAccentToPosition(string $rawText, int $charPosition): string
    {
        $russianVowels = 'аеёиоуыэюяАЕЁИОУЫЭЮЯ';

        // Strip tags to build the plain text used for validation and word detection.
        $plainText = str_replace(['<b>', '</b>'], '', $rawText);
        $plainLength = mb_strlen($plainText);

        if ($charPosition < 0 || $charPosition >= $plainLength) {
            return $rawText;
        }

        $targetChar = mb_substr($plainText, $charPosition, 1);

        // Reject non-vowel positions silently.
        if (mb_strpos($russianVowels, $targetChar) === false) {
            return $rawText;
        }

        // Find the Cyrillic word [wordStart, wordEnd) that contains charPosition.
        $wordStart = $charPosition;
        while ($wordStart > 0 && $this->isCyrillicChar(mb_substr($plainText, $wordStart - 1, 1))) {
            $wordStart--;
        }

        $wordEnd = $charPosition + 1; // exclusive upper bound
        while ($wordEnd < $plainLength && $this->isCyrillicChar(mb_substr($plainText, $wordEnd, 1))) {
            $wordEnd++;
        }

        // Walk the rawText segment by segment, rebuilding character by character.
        // – Characters inside the target word: strip any existing bold, place
        //   bold only on the clicked vowel.
        // – Characters outside the target word: preserve their original bold state.
        $result = '';
        $plainPos = 0;

        foreach ($this->parseRawTextSegments($rawText) as ['text' => $segText, 'bold' => $segBold]) {
            $segLength = mb_strlen($segText);

            for ($j = 0; $j < $segLength; $j++) {
                $ch = mb_substr($segText, $j, 1);
                $pos = $plainPos + $j;

                if ($pos === $charPosition) {
                    $result .= '<b>'.$ch.'</b>';
                } elseif ($pos >= $wordStart && $pos < $wordEnd) {
                    // Inside the target word but not the accent: remove bold.
                    $result .= $ch;
                } else {
                    // Outside the target word: preserve original bold state.
                    $result .= $segBold ? '<b>'.$ch.'</b>' : $ch;
                }
            }

            $plainPos += $segLength;
        }

        return $result;
    }

    /**
     * Parse a raw text string with <b>…</b> tags into flat segments.
     *
     * @return array<int, array{text: string, bold: bool}>
     */
    private function parseRawTextSegments(string $rawText): array
    {
        $segments = [];
        $isBold = false;

        // Split on <b> / </b> delimiters, keeping the delimiters in the result.
        $parts = preg_split('/(<\/?b>)/', $rawText, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        foreach ($parts as $part) {
            if ($part === '<b>') {
                $isBold = true;
            } elseif ($part === '</b>') {
                $isBold = false;
            } elseif ($part !== '') {
                $segments[] = ['text' => $part, 'bold' => $isBold];
            }
        }

        return $segments;
    }

    /**
     * Return true when the given single character is a Cyrillic letter.
     */
    private function isCyrillicChar(string $char): bool
    {
        return preg_match('/[а-яёА-ЯЁ]/u', $char) === 1;
    }
}
