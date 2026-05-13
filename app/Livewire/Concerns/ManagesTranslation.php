<?php

namespace App\Livewire\Concerns;

use App\Models\ApiUsageLog;
use App\Services\OpenAiTranslationService;
use App\Services\RussianAccentService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Provides ChatGPT translation and Russian stress-mark correction actions
 * for the CsvEditor component.
 *
 * @property OpenAiTranslationService $translationService
 * @property RussianAccentService $accentService
 * @property array<int, array<int, string>> $csvRows
 */
trait ManagesTranslation
{
    /** Row index currently being translated via ChatGPT (-1 = none). */
    public int $translatingRowIndex = -1;

    /** Row index currently having stress corrected via ChatGPT (-1 = none). */
    public int $correctingStressRowIndex = -1;

    /** Error message from the last ChatGPT translation or stress-correction attempt. */
    public string $translationError = '';

    /**
     * Translate the source text in column 0 of the given row to Russian using
     * the ChatGPT API, and insert the result (with <b>…</b> accent markers on
     * stressed vowels) into column 1.
     */
    public function translateWithChatGpt(int $rowIndex): void
    {
        $this->translationError = '';

        if (! auth()->user()?->openai_api_key) {
            $this->dispatch('open-openai-key-setup');

            return;
        }

        $sourceText = $this->csvRows[$rowIndex][0] ?? '';

        if (empty(trim($sourceText))) {
            return;
        }

        // Refuse per-row actions while a stress batch is in progress.
        if (isset($this->stressBatchStatus) && $this->stressBatchStatus === 'running') {
            return;
        }

        if ($this->checkAiRateLimitExceeded()) {
            return;
        }

        $this->translatingRowIndex = $rowIndex;

        try {
            $result = $this->translationService->translateSourceToRussianWithUsage($sourceText);
            $this->csvRows[$rowIndex][1] = $result['text'];
            $this->autoSaveDraft();

            ApiUsageLog::create([
                'user_id' => Auth::id(),
                'operation' => 'translation',
                'prompt_tokens' => $result['promptTokens'],
                'completion_tokens' => $result['completionTokens'],
            ]);
        } catch (Exception $exception) {
            $this->translationError = $exception->getMessage();
        } finally {
            $this->translatingRowIndex = -1;
        }
    }

    /**
     * Ask ChatGPT to review and fix stress marks in column 1 of the given row.
     * The source text in column 0 is sent as semantic context.
     */
    public function correctStressMarks(int $rowIndex): void
    {
        $this->translationError = '';

        if (! auth()->user()?->openai_api_key) {
            $this->dispatch('open-openai-key-setup');

            return;
        }

        $sourceText = trim($this->csvRows[$rowIndex][0] ?? '');
        $russianText = trim($this->csvRows[$rowIndex][1] ?? '');

        if ($russianText === '') {
            return;
        }

        // Refuse per-row actions while a stress batch is in progress.
        if (isset($this->stressBatchStatus) && $this->stressBatchStatus === 'running') {
            return;
        }

        if ($this->checkAiRateLimitExceeded()) {
            return;
        }

        $this->correctingStressRowIndex = $rowIndex;

        try {
            $result = $this->translationService->correctRussianStressMarksWithUsage($russianText, $sourceText);

            if ($result['text'] !== $russianText) {
                $this->csvRows[$rowIndex][1] = $result['text'];
                $this->autoSaveDraft();
            }

            if ($result['promptTokens'] > 0) {
                ApiUsageLog::create([
                    'user_id' => Auth::id(),
                    'operation' => 'stress_correction',
                    'prompt_tokens' => $result['promptTokens'],
                    'completion_tokens' => $result['completionTokens'],
                ]);
            }
        } catch (Exception $exception) {
            $this->translationError = $exception->getMessage();
        } finally {
            $this->correctingStressRowIndex = -1;
        }
    }

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

    /**
     * Returns true and sets $translationError when the per-user AI rate limit
     * is exceeded, false (and records a hit) when the request may proceed.
     */
    private function checkAiRateLimitExceeded(): bool
    {
        $rateLimitKey = 'ai-translation:'.(Auth::id() ?? session()->getId());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            $this->translationError = __('csv_editor.error_rate_limit');

            return true;
        }

        RateLimiter::hit($rateLimitKey, 60);

        return false;
    }
}
