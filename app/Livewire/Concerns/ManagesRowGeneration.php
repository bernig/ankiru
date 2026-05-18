<?php

namespace App\Livewire\Concerns;

use App\Models\ApiUsageLog;
use App\Services\CreditService;
use App\Services\RowGenerationService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

/**
 * Manages the "Generate rows" feature for the CsvEditor component.
 *
 * @property string $translationError
 * @property array<int, array<int, string>> $csvRows
 * @property RowGenerationService $rowGenerationService
 * @property CreditService $creditService
 */
trait ManagesRowGeneration
{
    public string $generateRowsContext = '';

    public int $generateRowsCount = 5;

    public string $generateRowsPrompt = '';

    public bool $generateRowsIncludeExisting = false;

    /** Pairs returned by the last successful generation, displayed as a report in the modal. */
    public array $generatedRows = [];

    /**
     * Estimated cost and token breakdown for the current modal inputs.
     *
     * Thresholds:
     *   - Existing rows are sent verbatim only when their combined source-text
     *     is ≤ 6 000 characters (~1 500 tokens). Beyond that, only the row
     *     count is mentioned to the AI.
     *
     * @return array{totalCost: float, inputTokens: int, outputTokens: int, existingCount: int, existingChars: int, existingTokens: int, willSendActualRows: bool, willSendCountOnly: bool}
     */
    #[Computed]
    public function generationCostEstimate(): array
    {
        $thresholdChars = 6_000;

        $systemTokens = 300;
        $contextTokens = (int) ceil(mb_strlen($this->generateRowsContext) / 4);
        $promptTokens = (int) ceil(mb_strlen($this->generateRowsPrompt) / 4);

        $existingSourceTexts = collect($this->csvRows)
            ->filter(fn ($row) => ! empty(trim($row[0] ?? '')))
            ->pluck(0);

        $existingCount = $existingSourceTexts->count();
        $existingChars = (int) $existingSourceTexts->sum(fn ($text) => mb_strlen($text));
        $existingTokens = (int) ceil($existingChars / 4);

        $willSendActualRows = $this->generateRowsIncludeExisting
            && $existingCount > 0
            && $existingChars <= $thresholdChars;

        $willSendCountOnly = $this->generateRowsIncludeExisting
            && $existingCount > 0
            && ! $willSendActualRows;

        $rowsInputTokens = match (true) {
            $willSendActualRows => $existingTokens,
            $willSendCountOnly => 15,
            default => 0,
        };

        $inputTokens = $systemTokens + $contextTokens + $promptTokens + $rowsInputTokens;
        $outputTokens = $this->generateRowsCount * 60;

        $inputPrice = (float) config('services.openai.gpt_5_4_input_price_per_million', 3.00);
        $outputPrice = (float) config('services.openai.gpt_5_4_output_price_per_million', 15.00);

        $totalCost = ($inputTokens / 1_000_000 * $inputPrice)
            + ($outputTokens / 1_000_000 * $outputPrice);

        return [
            'totalCost' => $totalCost,
            'inputTokens' => $inputTokens,
            'outputTokens' => $outputTokens,
            'existingCount' => $existingCount,
            'existingChars' => $existingChars,
            'existingTokens' => $existingTokens,
            'willSendActualRows' => $willSendActualRows,
            'willSendCountOnly' => $willSendCountOnly,
        ];
    }

    public function generateRows(): void
    {
        $this->translationError = '';

        if ($this->apiKeyMissing()) {
            return;
        }

        $this->validate([
            'generateRowsPrompt' => ['required', 'string', 'max:1000'],
            'generateRowsCount' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        if ($this->checkAiRateLimitExceeded()) {
            return;
        }

        $cost = $this->generationCostEstimate;

        $user = Auth::user();

        if ($user->usesPlatformCredits()) {
            $estimatedCredits = $this->creditService->tokensToCredits($cost['inputTokens'], $cost['outputTokens']);
            if (! $this->creditService->hasEnoughCredits($user, $estimatedCredits)) {
                $this->translationError = __('csv_editor.error_insufficient_credits');
                $this->dispatch('open-credits-shop');

                return;
            }
        }

        $existingSourceTexts = $cost['willSendActualRows']
            ? collect($this->csvRows)
                ->filter(fn ($row) => ! empty(trim($row[0] ?? '')))
                ->pluck(0)
                ->toArray()
            : [];

        try {
            $result = $this->rowGenerationService->generateRowPairs(
                prompt: $this->generateRowsPrompt,
                count: $this->generateRowsCount,
                context: $this->generateRowsContext,
                willSendActualRows: $cost['willSendActualRows'],
                willSendCountOnly: $cost['willSendCountOnly'],
                existingSourceTexts: $existingSourceTexts,
                existingCount: $cost['existingCount'],
            );

            foreach ($result['pairs'] as $pair) {
                $this->csvRows[] = [$pair['source'], $pair['russian']];
            }

            $this->autoSaveDraft();
            // Clear filters so new rows are visible and totalPages is accurate.
            $this->searchQuery = '';
            $this->filterAccentNeeded = false;
            $this->filterNoAudio = false;
            $this->setPage($this->totalPages);

            ApiUsageLog::create([
                'user_id' => Auth::id(),
                'operation' => 'row_generation',
                'prompt_tokens' => $result['promptTokens'],
                'completion_tokens' => $result['completionTokens'],
            ]);

            if ($user->usesPlatformCredits()) {
                $this->creditService->deduct(
                    $user,
                    $this->creditService->tokensToCredits($result['promptTokens'], $result['completionTokens']),
                );
                $user->refresh();
            }

            $this->generatedRows = $result['pairs'];
            $this->generateRowsPrompt = '';
        } catch (Exception $exception) {
            $this->translationError = $exception->getMessage();
        }
    }

    public function openGenerateRowsModal(): void
    {
        if (! $this->apiKeyMissing()) {
            $this->dispatch('open-generate-rows-modal');
        }
    }

    public function generateRowsReset(): void
    {
        $this->generatedRows = [];
        $this->generateRowsPrompt = '';
        $this->translationError = '';
    }
}
