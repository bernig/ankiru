<?php

namespace App\Livewire\Concerns;

use App\Models\CardReview;
use App\Services\RussianTextToSpeechService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Manages the Practice Mode (SM-2 spaced repetition) for the CsvEditor component.
 *
 * @property int $activeDraftId
 * @property array<int, array<int, string>> $csvRows
 * @property RussianTextToSpeechService $ttsService
 */
trait ManagesPracticeMode
{
    /** Whether the practice modal is open. */
    public bool $practiceModeOpen = false;

    /** Ordered list of row indices for the current session. */
    public array $practiceQueue = [];

    /** Index within $practiceQueue of the current card. */
    public int $practiceQueuePosition = 0;

    /** Whether the current card has been flipped (back revealed). */
    public bool $practiceCardFlipped = false;

    /** Indices of cards re-queued as "Again" during this session. */
    public array $practiceAgainQueue = [];

    /** Whether the session is finished (all cards reviewed). */
    public bool $practiceSessionDone = false;

    /** Number of cards reviewed in this session. */
    public int $practiceReviewedCount = 0;

    /** Audio URL for the current card's Russian text, or null if none cached. */
    public ?string $practiceCardAudioUrl = null;

    /** Whether audio autoplays when a card is flipped. */
    public bool $practiceAutoplay = true;

    public function openPracticeMode(): void
    {
        if ($this->activeDraftId === 0) {
            return;
        }

        $sessionInProgress = ! empty($this->practiceQueue)
            && ! $this->practiceSessionDone
            && $this->practiceQueuePosition < count($this->practiceQueue);

        if (! $sessionInProgress) {
            $this->buildPracticeQueue();
            $this->practiceQueuePosition = 0;
            $this->practiceCardFlipped = false;
            $this->practiceAgainQueue = [];
            $this->practiceSessionDone = false;
            $this->practiceReviewedCount = 0;
            $this->practiceCardAudioUrl = null;
        }

        $this->practiceModeOpen = true;
        $this->dispatch('open-practice-mode');
    }

    public function closePracticeMode(): void
    {
        $this->practiceModeOpen = false;
    }

    public function flipPracticeCard(): void
    {
        $this->practiceCardFlipped = true;
        $this->practiceCardAudioUrl = $this->resolvePracticeCardAudioUrl();
    }

    /**
     * Grade the current card using SM-2 and advance to the next.
     *
     * Grades: 0 = Again, 3 = Hard, 4 = Good, 5 = Easy
     */
    public function gradePracticeCard(int $grade): void
    {
        $rowIndex = $this->practiceQueue[$this->practiceQueuePosition] ?? null;

        if ($rowIndex === null) {
            return;
        }

        /** @var CardReview $review */
        $review = CardReview::firstOrNew([
            'user_id' => Auth::id(),
            'csv_draft_id' => $this->activeDraftId,
            'row_index' => $rowIndex,
        ]);

        if (! $review->exists) {
            $review->ease_factor = '2.50';
            $review->repetitions = 0;
            $review->interval_days = 1;
        }

        [$repetitions, $easeFactor, $intervalDays] = $this->applySm2(
            grade: $grade,
            repetitions: (int) $review->repetitions,
            easeFactor: (float) $review->ease_factor,
            intervalDays: (int) $review->interval_days,
        );

        $review->repetitions = $repetitions;
        $review->ease_factor = number_format($easeFactor, 2, '.', '');
        $review->interval_days = $intervalDays;
        $review->due_date = Carbon::today()->addDays($intervalDays);
        $review->last_reviewed_at = now();
        $review->save();

        $this->practiceReviewedCount++;

        // "Again" cards are appended at the end of the session queue.
        if ($grade === 0) {
            $this->practiceAgainQueue[] = $rowIndex;
        }

        $this->advancePracticeQueue();
    }

    /**
     * @return array{0: int, 1: float, 2: int}
     */
    private function applySm2(int $grade, int $repetitions, float $easeFactor, int $intervalDays): array
    {
        if ($grade < 3) {
            return [0, $easeFactor, 1];
        }

        $newInterval = match ($repetitions) {
            0 => 1,
            1 => 6,
            default => (int) round($intervalDays * $easeFactor),
        };

        $newEf = $easeFactor + 0.1 - (5 - $grade) * (0.08 + (5 - $grade) * 0.02);
        $newEf = max(1.3, $newEf);

        return [$repetitions + 1, $newEf, $newInterval];
    }

    private function advancePracticeQueue(): void
    {
        $this->practiceCardFlipped = false;
        $this->practiceCardAudioUrl = null;
        $nextPosition = $this->practiceQueuePosition + 1;

        // Reached the end of the main queue: append Again cards then continue.
        if ($nextPosition >= count($this->practiceQueue)) {
            if (! empty($this->practiceAgainQueue)) {
                foreach ($this->practiceAgainQueue as $rowIndex) {
                    $this->practiceQueue[] = $rowIndex;
                }
                $this->practiceAgainQueue = [];
                $this->practiceQueuePosition = $nextPosition;
            } else {
                $this->practiceSessionDone = true;
            }
        } else {
            $this->practiceQueuePosition = $nextPosition;
        }
    }

    private function resolvePracticeCardAudioUrl(): ?string
    {
        $rowIndex = $this->practiceQueue[$this->practiceQueuePosition] ?? null;

        if ($rowIndex === null) {
            return null;
        }

        $russianText = $this->csvRows[$rowIndex][1] ?? '';

        if (empty(trim($russianText)) || ! $this->ttsService->audioFileExists($russianText)) {
            return null;
        }

        $hash = $this->ttsService->buildFilenameHash($russianText);

        return route('tts.serve', $hash).'?v='.time();
    }

    private function buildPracticeQueue(): void
    {
        $today = Carbon::today();

        $existingReviews = CardReview::query()
            ->where('user_id', Auth::id())
            ->where('csv_draft_id', $this->activeDraftId)
            ->get()
            ->keyBy('row_index');

        $due = [];
        $newCards = [];

        foreach (array_keys($this->csvRows) as $rowIndex) {
            $sourceText = trim($this->csvRows[$rowIndex][0] ?? '');
            $russianText = trim($this->csvRows[$rowIndex][1] ?? '');

            // Skip cards with no content on either side.
            if ($sourceText === '' || $russianText === '') {
                continue;
            }

            /** @var CardReview|null $review */
            $review = $existingReviews->get($rowIndex);

            if ($review === null) {
                $newCards[] = $rowIndex;
            } elseif ($review->due_date->lte($today)) {
                $due[] = $rowIndex;
            }
        }

        $this->practiceQueue = array_values(array_merge($due, $newCards));
    }
}
