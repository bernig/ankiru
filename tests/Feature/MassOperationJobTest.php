<?php

use App\Enums\OperationType;
use App\Events\MassOperationProgressEvent;
use App\Jobs\MassOperationJob;
use App\Services\OpenAiTranslationService;
use App\Services\RussianAccentService;
use App\Services\RussianTextToSpeechService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * Shared test session ID — scopes all cache keys within a single test run.
 */
function massOpSessionId(): string
{
    return 'test-session-abc123';
}

/**
 * Build the namespaced cache key used by MassOperationJob.
 */
function massOpCacheKey(string $type, string $suffix): string
{
    return 'mass_op:'.massOpSessionId().":{$type}:{$suffix}";
}

/**
 * Initialise the cache counters the same way MassOperationService would
 * before dispatching a batch.
 */
function seedBatchCache(string $type, int $total): void
{
    Cache::put(massOpCacheKey($type, 'status'), 'running', 3600);
    Cache::put(massOpCacheKey($type, 'total'), $total, 3600);
    Cache::put(massOpCacheKey($type, 'processed'), 0, 3600);
    Cache::put(massOpCacheKey($type, 'failed'), 0, 3600);

    if ($type === 'stress') {
        Cache::put(massOpCacheKey($type, 'corrected'), 0, 3600);
        Cache::put(massOpCacheKey($type, 'prompt_tokens'), 0, 3600);
        Cache::put(massOpCacheKey($type, 'completion_tokens'), 0, 3600);
    }

    if ($type === 'tts') {
        Cache::put(massOpCacheKey($type, 'generated'), 0, 3600);
        Cache::put(massOpCacheKey($type, 'actual_chars'), 0, 3600);
    }
}

// ---------------------------------------------------------------------------
// Stress correction jobs
// ---------------------------------------------------------------------------

it('writes corrected text to the per-row cache key', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('stress', 1);

    $russianText = 'Я работаю из дома.'; // needs stress marks
    $corrected = 'Я раб<b>о</b>таю из д<b>о</b>ма.';

    $this->mock(RussianAccentService::class)
        ->shouldReceive('textNeedsStressCorrection')
        ->with($russianText)
        ->andReturn(true);

    $this->mock(OpenAiTranslationService::class)
        ->shouldReceive('correctRussianStressMarksWithUsage')
        ->with($russianText, 'Je travaille depuis chez moi.')
        ->andReturn(['text' => $corrected, 'promptTokens' => 120, 'completionTokens' => 40]);

    (new MassOperationJob(
        operationType: OperationType::Stress,
        sessionId: massOpSessionId(),
        rowIndex: 3,
        totalRows: 1,
        sourceText: 'Je travaille depuis chez moi.',
        russianText: $russianText,
    ))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect(Cache::get(massOpCacheKey('stress', 'row:3')))->toBe($corrected);
});

it('increments the processed counter after a successful stress job', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('stress', 3);

    $this->mock(RussianAccentService::class)
        ->shouldReceive('textNeedsStressCorrection')->andReturn(true);
    $this->mock(OpenAiTranslationService::class)
        ->shouldReceive('correctRussianStressMarksWithUsage')
        ->andReturn(['text' => 'result', 'promptTokens' => 100, 'completionTokens' => 30]);

    (new MassOperationJob(OperationType::Stress, massOpSessionId(), 0, 3, 'source', 'russian'))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect((int) Cache::get(massOpCacheKey('stress', 'processed')))->toBe(1);
});

it('sets status to done when the last stress row is processed', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('stress', 1);

    $this->mock(RussianAccentService::class)
        ->shouldReceive('textNeedsStressCorrection')->andReturn(true);
    $this->mock(OpenAiTranslationService::class)
        ->shouldReceive('correctRussianStressMarksWithUsage')
        ->andReturn(['text' => 'result', 'promptTokens' => 100, 'completionTokens' => 30]);

    (new MassOperationJob(OperationType::Stress, massOpSessionId(), 0, 1, 'source', 'russian'))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect(Cache::get(massOpCacheKey('stress', 'status')))->toBe('done');
});

it('broadcasts a MassOperationProgressEvent after a successful stress job', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('stress', 1);

    $this->mock(RussianAccentService::class)
        ->shouldReceive('textNeedsStressCorrection')->andReturn(true);
    $this->mock(OpenAiTranslationService::class)
        ->shouldReceive('correctRussianStressMarksWithUsage')
        ->andReturn(['text' => 'result', 'promptTokens' => 100, 'completionTokens' => 30]);

    (new MassOperationJob(OperationType::Stress, massOpSessionId(), 0, 1, 'source', 'russian'))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    Event::assertDispatched(MassOperationProgressEvent::class, function ($event): bool {
        return $event->operationType === 'stress'
            && $event->sessionId === massOpSessionId()
            && $event->status === 'done'
            && $event->processedCount === 1
            && $event->totalCount === 1
            && $event->failedCount === 0;
    });
});

it('skips correction when the row no longer needs stress marks', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('stress', 1);

    $this->mock(RussianAccentService::class)
        ->shouldReceive('textNeedsStressCorrection')->andReturn(false);

    // correctRussianStressMarksWithUsage should never be called for already-correct rows
    $this->mock(OpenAiTranslationService::class)
        ->shouldNotReceive('correctRussianStressMarksWithUsage');

    (new MassOperationJob(OperationType::Stress, massOpSessionId(), 0, 1, 'source', 'Я раб<b>о</b>таю.'))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    // Processed counter still increments even when skipped.
    expect((int) Cache::get(massOpCacheKey('stress', 'processed')))->toBe(1);
    // Corrected counter stays at zero — no API call was made.
    expect((int) Cache::get(massOpCacheKey('stress', 'corrected')))->toBe(0);
});

it('accumulates exact token counts and increments corrected counter on success', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('stress', 2);

    $this->mock(RussianAccentService::class)
        ->shouldReceive('textNeedsStressCorrection')->andReturn(true);
    $this->mock(OpenAiTranslationService::class)
        ->shouldReceive('correctRussianStressMarksWithUsage')
        ->twice()
        ->andReturn(
            ['text' => 'first', 'promptTokens' => 200, 'completionTokens' => 50],
            ['text' => 'second', 'promptTokens' => 150, 'completionTokens' => 40],
        );

    (new MassOperationJob(OperationType::Stress, massOpSessionId(), 0, 2, 'src', 'russian'))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );
    (new MassOperationJob(OperationType::Stress, massOpSessionId(), 1, 2, 'src', 'russian'))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect((int) Cache::get(massOpCacheKey('stress', 'corrected')))->toBe(2);
    expect((int) Cache::get(massOpCacheKey('stress', 'prompt_tokens')))->toBe(350);
    expect((int) Cache::get(massOpCacheKey('stress', 'completion_tokens')))->toBe(90);
});

// ---------------------------------------------------------------------------
// Failed stress job
// ---------------------------------------------------------------------------

it('records a failure and keeps the batch running when a stress API call throws', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('stress', 2);

    $this->mock(RussianAccentService::class)
        ->shouldReceive('textNeedsStressCorrection')->andReturn(true);
    $this->mock(OpenAiTranslationService::class)
        ->shouldReceive('correctRussianStressMarksWithUsage')
        ->andThrow(new RuntimeException('API timeout'));

    // Should NOT throw — exception must be caught inside handle()
    (new MassOperationJob(OperationType::Stress, massOpSessionId(), 0, 2, 'source', 'russian'))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect((int) Cache::get(massOpCacheKey('stress', 'failed')))->toBe(1);
    expect((int) Cache::get(massOpCacheKey('stress', 'processed')))->toBe(1);
    // Batch is NOT done yet — second row hasn't run.
    expect(Cache::get(massOpCacheKey('stress', 'status')))->toBe('running');
});

it('increments failed and processed counters when a stress job fails externally', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('stress', 1);

    $job = new MassOperationJob(OperationType::Stress, massOpSessionId(), 0, 1, 'source', 'russian');
    $job->failed(new RuntimeException('AI timeout'));

    expect((int) Cache::get(massOpCacheKey('stress', 'failed')))->toBe(1);
    expect((int) Cache::get(massOpCacheKey('stress', 'processed')))->toBe(1);
    expect(Cache::get(massOpCacheKey('stress', 'status')))->toBe('done');
});

it('broadcasts a done event when the last stress row fails externally', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('stress', 1);

    $job = new MassOperationJob(OperationType::Stress, massOpSessionId(), 0, 1, 'source', 'russian');
    $job->failed(new RuntimeException('boom'));

    Event::assertDispatched(MassOperationProgressEvent::class, function ($event): bool {
        return $event->status === 'done'
            && $event->failedCount === 1;
    });
});

// ---------------------------------------------------------------------------
// TTS jobs
// ---------------------------------------------------------------------------

it('calls generateAudio with the russian text for a TTS job', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('tts', 1);

    $russianText = 'Я раб<b>о</b>таю из д<b>о</b>ма.';
    $normalizedText = 'Я работаю из дома.';

    $ttsMock = $this->mock(RussianTextToSpeechService::class);
    $ttsMock->shouldReceive('normalizeForSpeech')->with($russianText)->andReturn($normalizedText);
    $ttsMock->shouldReceive('audioFileExists')->andReturn(false);
    $ttsMock->shouldReceive('generateAudio')->with($russianText)->once();

    (new MassOperationJob(OperationType::Tts, massOpSessionId(), 0, 1, '', $russianText))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect((int) Cache::get(massOpCacheKey('tts', 'processed')))->toBe(1);
});

it('skips TTS generation when the russian text is empty', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('tts', 1);

    $ttsMock = $this->mock(RussianTextToSpeechService::class);
    $ttsMock->shouldReceive('normalizeForSpeech')->with('   ')->andReturn('');
    $ttsMock->shouldNotReceive('generateAudio');

    (new MassOperationJob(OperationType::Tts, massOpSessionId(), 0, 1, '', '   '))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect((int) Cache::get(massOpCacheKey('tts', 'processed')))->toBe(1);
});

it('sets status to done when the last TTS row is processed', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('tts', 1);

    $ttsMock = $this->mock(RussianTextToSpeechService::class);
    $ttsMock->shouldReceive('normalizeForSpeech')->andReturn('russian');
    $ttsMock->shouldReceive('audioFileExists')->andReturn(false);
    $ttsMock->shouldReceive('generateAudio')->once();

    (new MassOperationJob(OperationType::Tts, massOpSessionId(), 0, 1, '', 'russian'))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect(Cache::get(massOpCacheKey('tts', 'status')))->toBe('done');
});

it('increments failed and processed counters when a TTS job fails', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('tts', 1);

    $job = new MassOperationJob(OperationType::Tts, massOpSessionId(), 0, 1, '', 'russian');
    $job->failed(new RuntimeException('TTS API error'));

    expect((int) Cache::get(massOpCacheKey('tts', 'failed')))->toBe(1);
    expect((int) Cache::get(massOpCacheKey('tts', 'processed')))->toBe(1);
    expect(Cache::get(massOpCacheKey('tts', 'status')))->toBe('done');
});

it('records a failure and keeps the batch running when a TTS API call throws', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('tts', 2);

    $ttsMock = $this->mock(RussianTextToSpeechService::class);
    $ttsMock->shouldReceive('normalizeForSpeech')->andReturn('russian');
    $ttsMock->shouldReceive('audioFileExists')->andReturn(false);
    $ttsMock->shouldReceive('generateAudio')->andThrow(new RuntimeException('TTS API error'));

    // Should NOT throw — exception must be caught inside handle()
    (new MassOperationJob(OperationType::Tts, massOpSessionId(), 0, 2, '', 'russian'))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect((int) Cache::get(massOpCacheKey('tts', 'failed')))->toBe(1);
    expect((int) Cache::get(massOpCacheKey('tts', 'processed')))->toBe(1);
    // Batch is NOT done yet — second row hasn't run.
    expect(Cache::get(massOpCacheKey('tts', 'status')))->toBe('running');
});

it('increments generated count and accumulates actual chars after a successful TTS job', function (): void {
    Event::fake([MassOperationProgressEvent::class]);
    seedBatchCache('tts', 2);

    $russianA = 'Я раб<b>о</b>таю.'; // normalises to "Я работаю." — 10 chars
    $russianB = 'Хор<b>о</b>шо.';    // normalises to "Хорошо."    —  7 chars

    $this->mock(RussianTextToSpeechService::class)
        ->shouldReceive('normalizeForSpeech')
        ->andReturnUsing(fn (string $text) => trim(str_replace(['<b>', '</b>'], '', $text)))
        ->shouldReceive('audioFileExists')
        ->andReturn(false)
        ->shouldReceive('generateAudio')
        ->twice();

    (new MassOperationJob(OperationType::Tts, massOpSessionId(), 0, 2, '', $russianA))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );
    (new MassOperationJob(OperationType::Tts, massOpSessionId(), 1, 2, '', $russianB))->handle(
        app(RussianAccentService::class),
        app(OpenAiTranslationService::class),
        app(RussianTextToSpeechService::class),
    );

    expect((int) Cache::get(massOpCacheKey('tts', 'generated')))->toBe(2);
    expect((int) Cache::get(massOpCacheKey('tts', 'actual_chars')))->toBe(17); // 10 + 7
});
