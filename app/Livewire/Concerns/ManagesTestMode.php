<?php

namespace App\Livewire\Concerns;

use App\Services\RussianTextToSpeechService;

/**
 * Manages the Test Mode for the CsvEditor component.
 *
 * @property int $activeDraftId
 * @property array<int, array<int, string>> $csvRows
 * @property RussianTextToSpeechService $ttsService
 */
trait ManagesTestMode
{
    /** Whether the test modal is open. */
    public bool $testModeOpen = false;

    /** Shuffled list of row indices for the current session. */
    public array $testQueue = [];

    /** Index within $testQueue of the current card. */
    public int $testQueuePosition = 0;

    /** Whether the current card has been flipped (back revealed). */
    public bool $testCardFlipped = false;

    /** Whether the session is finished (all cards seen). */
    public bool $testSessionDone = false;

    /** Audio URL for the current card's Russian text, or null if none cached. */
    public ?string $testCardAudioUrl = null;

    /** Whether audio autoplays when a card is flipped. */
    public bool $testAutoplay = true;

    /** Whether TTS audio is currently being generated for the current card. */
    public bool $testAudioGenerating = false;

    public function openTestMode(): void
    {
        if ($this->activeDraftId === 0) {
            return;
        }

        $this->buildTestQueue();
        $this->testQueuePosition = 0;
        $this->testCardFlipped = false;
        $this->testSessionDone = false;
        $this->testCardAudioUrl = null;

        $this->testModeOpen = true;
        $this->dispatch('open-test-mode');
    }

    public function closeTestMode(): void
    {
        $this->testModeOpen = false;
    }

    public function flipTestCard(): void
    {
        $this->testCardFlipped = true;
        $this->testCardAudioUrl = $this->resolveTestCardAudioUrl();
    }

    public function nextTestCard(): void
    {
        $this->testCardFlipped = false;
        $this->testCardAudioUrl = null;

        $nextPosition = $this->testQueuePosition + 1;

        if ($nextPosition >= count($this->testQueue)) {
            $this->testSessionDone = true;
        } else {
            $this->testQueuePosition = $nextPosition;
        }
    }

    public function generateTestCardAudio(): void
    {
        $rowIndex = $this->testQueue[$this->testQueuePosition] ?? null;

        if ($rowIndex === null) {
            return;
        }

        $this->testAudioGenerating = true;

        try {
            $this->generateTtsAudio($rowIndex);
            $this->testCardAudioUrl = $this->resolveTestCardAudioUrl();
        } finally {
            $this->testAudioGenerating = false;
        }
    }

    public function restartTest(): void
    {
        $this->buildTestQueue();
        $this->testQueuePosition = 0;
        $this->testCardFlipped = false;
        $this->testSessionDone = false;
        $this->testCardAudioUrl = null;
    }

    private function resolveTestCardAudioUrl(): ?string
    {
        $rowIndex = $this->testQueue[$this->testQueuePosition] ?? null;

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

    private function buildTestQueue(): void
    {
        $cards = [];

        foreach (array_keys($this->csvRows) as $rowIndex) {
            $sourceText = trim($this->csvRows[$rowIndex][0] ?? '');
            $russianText = trim($this->csvRows[$rowIndex][1] ?? '');

            if ($sourceText !== '' && $russianText !== '') {
                $cards[] = $rowIndex;
            }
        }

        shuffle($cards);
        $this->testQueue = array_values($cards);
    }
}
