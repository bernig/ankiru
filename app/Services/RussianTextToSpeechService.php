<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Audio;
use Laravel\Ai\Exceptions\FailoverableException;
use RuntimeException;

/**
 * Generates high-quality Russian text-to-speech audio using the Laravel AI SDK,
 * backed by the OpenAI TTS provider.
 *
 * Caching strategy: generated MP3 files are stored under tts/ on the local disk with a
 * filename derived from the SHA-256 hash of the normalized Russian phrase (stress
 * tags stripped). The same spoken phrase always maps to the same file, so the TTS
 * API is only called once per unique phrase regardless of stress-mark placement.
 */
class RussianTextToSpeechService
{
    /**
     * Normalize the Russian phrase for speech generation by stripping <b> stress
     * tags and trimming surrounding whitespace. Cyrillic text content is
     * preserved exactly as written.
     */
    public function normalizeForSpeech(string $rawRussianPhrase): string
    {
        return trim(str_replace(['<b>', '</b>'], '', $rawRussianPhrase));
    }

    /**
     * Return true when a cached audio file already exists for the given phrase.
     */
    public function audioFileExists(string $rawRussianPhrase): bool
    {
        return Storage::disk('local')->exists($this->buildStoragePath($rawRussianPhrase));
    }

    /**
     * Delete the cached audio file for the given phrase, if it exists.
     *
     * Returns true when the file was found and deleted, false when no cached
     * file existed for this phrase.
     */
    public function deleteAudio(string $rawRussianPhrase): bool
    {
        $storagePath = $this->buildStoragePath($rawRussianPhrase);

        if (! Storage::disk('local')->exists($storagePath)) {
            return false;
        }

        return Storage::disk('local')->delete($storagePath);
    }

    /**
     * Generate (or retrieve from cache) an MP3 audio file for the given Russian
     * phrase. Returns true when a new file was generated, false when the cached
     * file was reused. Callers that need the storage path should call
     * buildFilenameHash() directly.
     *
     * @throws RuntimeException|FailoverableException
     */
    public function generateAudio(string $rawRussianPhrase): bool
    {
        if (config('app.debug') && session('debug_force_ai_error')) {
            sleep(1);
            throw new RuntimeException('[Debug] Simulated AI error.');
        }

        $normalizedText = $this->normalizeForSpeech($rawRussianPhrase);
        $storagePath = $this->buildStoragePath($rawRussianPhrase);

        if (Storage::disk('local')->exists($storagePath)) {
            return false;
        }

        $ttsVoice = config('services.openai.tts_voice', 'echo');

        $audio = Audio::of($normalizedText)
            ->voice($ttsVoice)
            ->generate();

        Storage::disk('local')->put($storagePath, (string) $audio);

        return true;
    }

    /**
     * Check whether cached audio files exist for multiple phrases, returning a
     * map of rawPhrase => bool.
     *
     * Only the files asked for are checked, one existence test per distinct
     * file. Listing the tts/ directory instead would cost more as it grows,
     * since it holds the audio of every user.
     *
     * @param  string[]  $rawRussianPhrases
     * @return array<string, bool>
     */
    public function audioFilesExistBatch(array $rawRussianPhrases): array
    {
        $disk = Storage::disk('local');

        /** @var array<string, bool> $existenceByPath */
        $existenceByPath = [];
        $result = [];

        foreach ($rawRussianPhrases as $rawPhrase) {
            $storagePath = $this->buildStoragePath($rawPhrase);
            $existenceByPath[$storagePath] ??= $disk->exists($storagePath);
            $result[$rawPhrase] = $existenceByPath[$storagePath];
        }

        return $result;
    }

    /**
     * Return the SHA-256 cache key for the given phrase.
     *
     * The key is derived from the normalized text so that phrases differing only
     * in stress-mark placement map to the same audio file. Use this when you
     * need the hash externally, e.g. to build a route URL.
     */
    public function buildFilenameHash(string $rawRussianPhrase): string
    {
        return hash('sha256', $this->normalizeForSpeech($rawRussianPhrase));
    }

    /**
     * Derive the storage-relative path for the given phrase.
     */
    private function buildStoragePath(string $russianPhrase): string
    {
        return 'tts/'.$this->buildFilenameHash($russianPhrase).'.mp3';
    }
}
