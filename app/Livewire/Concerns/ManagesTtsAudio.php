<?php

namespace App\Livewire\Concerns;

use App\Models\ApiUsageLog;
use App\Services\RussianTextToSpeechService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Exceptions\FailoverableException;
use Livewire\Attributes\Computed;

/**
 * Provides TTS audio generation, deletion, and player-modal management
 * actions for the CsvEditor component.
 *
 * @property RussianTextToSpeechService $ttsService
 * @property array<int, array<int, string>> $csvRows
 */
trait ManagesTtsAudio
{
    /** Row index currently generating TTS audio (-1 = none). */
    public int $ttsGeneratingRowIndex = -1;

    /** Error message from the last TTS generation attempt. */
    public string $ttsError = '';

    /**
     * Row index whose audio is displayed in the TTS player modal (-1 = none).
     * Reset to -1 whenever a row is deleted to prevent stale references.
     */
    public int $ttsModalRowIndex = -1;

    /**
     * Pre-built map of rowIndex → bool for all visible rows on the current page,
     * computed in a single directory scan rather than one Storage::exists() per row.
     *
     * @return array<int, bool>
     */
    #[Computed]
    public function audioExistenceByRowIndex(): array
    {
        /** @var array<int, string> $rawTextByRowIndex */
        $rawTextByRowIndex = [];

        foreach ($this->paginatedRows as $rowIndex => $row) {
            $rawTextByRowIndex[$rowIndex] = $row[1] ?? '';
        }

        // Collect only non-empty phrases for the batch lookup.
        $nonEmptyPhrases = array_values(
            array_filter($rawTextByRowIndex, fn (string $text) => ! empty(trim($text)))
        );

        $existenceByPhrase = $this->ttsService->audioFilesExistBatch($nonEmptyPhrases);

        $map = [];

        foreach ($rawTextByRowIndex as $rowIndex => $rawRussianText) {
            $map[$rowIndex] = ! empty(trim($rawRussianText)) && ($existenceByPhrase[$rawRussianText] ?? false);
        }

        return $map;
    }

    /**
     * Generate (or retrieve from cache) high-quality TTS audio for the Russian
     * phrase in column 1 of the given row, then dispatch a browser event so
     * Alpine.js can play the returned audio URL immediately.
     */
    public function generateTtsAudio(int $rowIndex): void
    {
        $this->ttsError = '';

        if ($this->apiKeyMissing()) {
            return;
        }

        $rawRussianText = $this->csvRows[$rowIndex][1] ?? '';
        $normalizedText = $this->ttsService->normalizeForSpeech($rawRussianText);

        if (empty($normalizedText)) {
            return;
        }

        // Refuse per-row action while a TTS batch is in progress.
        if (isset($this->ttsBatchStatus) && $this->ttsBatchStatus === 'running') {
            return;
        }

        $rateLimitKey = 'tts-generation:'.(Auth::id() ?? session()->getId());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $this->ttsError = __('csv_editor.error_rate_limit');

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        $this->ttsGeneratingRowIndex = $rowIndex;

        try {
            $isNewGeneration = $this->ttsService->generateAudio($rawRussianText);

            if ($isNewGeneration) {
                ApiUsageLog::create([
                    'user_id' => Auth::id(),
                    'operation' => 'tts',
                    'characters' => mb_strlen($normalizedText),
                ]);
            }

            $filenameHash = $this->ttsService->buildFilenameHash($rawRussianText);
            // Append a cache-busting timestamp so browsers always fetch the latest audio.
            $audioUrl = route('tts.serve', $filenameHash).'?v='.time();

            $this->dispatch('tts-audio-ready', audioUrl: $audioUrl);
            // Notify the row's Alpine component so it can update its rowHasAudio state.
            $this->dispatch('tts-audio-generated', rowIndex: $rowIndex);
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
        // Notify the row's Alpine component so it can revert its rowHasAudio state.
        $this->dispatch('tts-audio-deleted', rowIndex: $rowIndex);
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
            $filenameHash = $this->ttsService->buildFilenameHash($rawRussianText);
            $lastModified = Storage::disk('local')->lastModified("tts/{$filenameHash}.mp3");
            $audioUrl = route('tts.serve', $filenameHash).'?v='.$lastModified;
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
}
