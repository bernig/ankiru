<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Generates high-quality Russian text-to-speech audio using the OpenAI TTS API.
 *
 * Provider choice: OpenAI `tts-1-hd` model — a neural TTS engine that produces
 * natural, fluent Russian speech. The `tts-1-hd` variant prioritises quality over
 * latency, making it the best option available through the OpenAI API for Russian.
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
    public function buildCacheKey(string $rawRussianPhrase): string
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
    public function hasCachedAudio(string $rawRussianPhrase): bool
    {
        $cacheKey = $this->buildCacheKey($rawRussianPhrase);

        return Storage::disk('local')->exists("tts/{$cacheKey}.mp3");
    }

    /**
     * Generate (or retrieve from cache) an MP3 audio file for the given Russian
     * phrase. Returns the storage-relative path to the MP3 file.
     *
     * @throws RuntimeException when the TTS API request fails.
     */
    public function generateAudio(string $rawRussianPhrase): string
    {
        $cacheKey = $this->buildCacheKey($rawRussianPhrase);
        $storagePath = "tts/{$cacheKey}.mp3";

        // Return the cached file immediately if it already exists.
        if (Storage::disk('local')->exists($storagePath)) {
            return $storagePath;
        }

        $normalizedText = $this->normalizeForSpeech($rawRussianPhrase);
        $audioContents = $this->requestAudioFromOpenAi($normalizedText);

        Storage::disk('local')->put($storagePath, $audioContents);

        return $storagePath;
    }

    /**
     * Call the OpenAI TTS API and return raw MP3 binary content.
     *
     * The `tts-1-hd` model is used for maximum quality. Voice and model are
     * configurable via services.openai.tts_voice / services.openai.tts_model.
     *
     * @throws RuntimeException|ConnectionException when the API responds with a non-2xx status.
     */
    private function requestAudioFromOpenAi(string $normalizedText): string
    {
        $apiKey = config('services.openai.api_key');
        $ttsModel = config('services.openai.tts_model', 'tts-1-hd');
        $ttsVoice = config('services.openai.tts_voice', 'echo');

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/audio/speech', [
                'model' => $ttsModel,
                'input' => $normalizedText,
                'voice' => $ttsVoice,
                'response_format' => 'mp3',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "OpenAI TTS API returned an error: {$response->status()}. Check your API key and quota."
            );
        }

        return $response->body();
    }
}
