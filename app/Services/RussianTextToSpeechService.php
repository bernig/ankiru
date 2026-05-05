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
 * Caching strategy: generated MP3 files are stored under storage/app/tts/ with a
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
     * phrase. Returns the storage-relative path to the MP3 file.
     *
     * @throws RuntimeException|FailoverableException
     */
    public function generateAudio(string $rawRussianPhrase): string
    {
        $normalizedText = $this->normalizeForSpeech($rawRussianPhrase);
        $storagePath = $this->buildStoragePath($normalizedText);

        // Return the cached file immediately if it already exists.
        if (Storage::disk('local')->exists($storagePath)) {
            return $storagePath;
        }

        // Generate audio via the Laravel AI SDK and store the raw MP3 bytes.
        $ttsVoice = config('services.openai.tts_voice', 'echo');

        $audio = Audio::of($normalizedText)
            ->voice($ttsVoice)
            ->generate();

        Storage::disk('local')->put($storagePath, (string) $audio);

        return $storagePath;
    }

    /**
     * Check whether cached audio files exist for multiple phrases in a single
     * directory scan, returning a map of rawPhrase → bool.
     *
     * More efficient than calling audioFileExists() per phrase because it lists
     * the tts/ directory once instead of making one Storage::exists() call per phrase.
     *
     * @param  string[]  $rawRussianPhrases
     * @return array<string, bool>
     */
    public function audioFilesExistBatch(array $rawRussianPhrases): array
    {
        if (empty($rawRussianPhrases)) {
            return [];
        }

        // One filesystem scan to build a lookup set of all existing TTS files.
        $existingFiles = array_flip(Storage::disk('local')->files('tts'));

        $result = [];

        foreach ($rawRussianPhrases as $rawPhrase) {
            $result[$rawPhrase] = isset($existingFiles[$this->buildStoragePath($rawPhrase)]);
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
