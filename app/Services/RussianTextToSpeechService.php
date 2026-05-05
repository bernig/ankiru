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
 * filename derived from the SHA-256 hash of the raw Russian phrase (including any
 * <b> stress-mark tags). This means the same phrase always maps to the same file,
 * and the TTS API is only called once per unique phrase.
 */
class RussianTextToSpeechService
{
    /**
     * Derive a deterministic storage key from the raw Russian phrase.
     *
     * The hash is computed from the original text including <b> stress tags so
     * that two phrases that differ only in stress placement are stored separately
     * and can be regenerated independently if needed.
     */
    public function hashRawString(string $rawRussianPhrase): string
    {
        return hash('sha256', $rawRussianPhrase);
    }

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
        $hash = $this->hashRawString($rawRussianPhrase);

        return Storage::disk('local')->exists("tts/{$hash}.mp3");
    }

    /**
     * Delete the cached audio file for the given phrase, if it exists.
     *
     * Returns true when the file was found and deleted, false when no cached
     * file existed for this phrase.
     */
    public function deleteAudio(string $rawRussianPhrase): bool
    {
        $hash = $this->hashRawString($rawRussianPhrase);
        $storagePath = "tts/{$hash}.mp3";

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
        $hash = $this->hashRawString($rawRussianPhrase);
        $storagePath = "tts/{$hash}.mp3";

        // Return the cached file immediately if it already exists.
        if (Storage::disk('local')->exists($storagePath)) {
            return $storagePath;
        }

        $normalizedText = $this->normalizeForSpeech($rawRussianPhrase);

        // Generate audio via the Laravel AI SDK and store the raw MP3 bytes.
        $ttsVoice = config('services.openai.tts_voice', 'echo');

        $audio = Audio::of($normalizedText)
            ->voice($ttsVoice)
            ->generate();

        Storage::disk('local')->put($storagePath, (string) $audio);

        return $storagePath;
    }
}
