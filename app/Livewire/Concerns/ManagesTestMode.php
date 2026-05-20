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

    /**
     * Pre-computed audio URLs for all cards in the queue, indexed by row index.
     *
     * @var array<int, string|null>
     */
    public array $testCardAudioUrls = [];

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

    public function generateTestCardAudio(): void
    {
        $rowIndex = $this->testQueue[$this->testQueuePosition] ?? null;

        if ($rowIndex === null) {
            return;
        }

        $this->testAudioGenerating = true;

        try {
            $this->generateTtsAudio($rowIndex);
            $url = $this->buildAudioUrl($this->csvRows[$rowIndex][1] ?? '');
            $this->testCardAudioUrl = $url;
            $this->testCardAudioUrls[$rowIndex] = $url;
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

    private function buildAudioUrl(string $russianText): ?string
    {
        if (empty(trim($russianText)) || ! $this->ttsService->audioFileExists($russianText)) {
            return null;
        }

        return route('tts.serve', $this->ttsService->buildFilenameHash($russianText));
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

        $audioUrls = [];
        foreach ($this->testQueue as $rowIndex) {
            $audioUrls[$rowIndex] = $this->buildAudioUrl($this->csvRows[$rowIndex][1] ?? '');
        }
        $this->testCardAudioUrls = $audioUrls;
    }
}
