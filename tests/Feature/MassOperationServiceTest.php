<?php

use App\Jobs\MassOperationJob;
use App\Services\MassOperationService;
use App\Services\RussianAccentService;
use App\Services\RussianTextToSpeechService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
/**
 * Build a MassOperationService with real service dependencies.
 * TTS audio existence checks are faked via Storage.
 */
function makeService(): MassOperationService
{
    return new MassOperationService(
        new RussianAccentService,
        new RussianTextToSpeechService,
    );
}
/**
 * Sample rows: two rows needing stress, one already accented, one empty.
 *
 * @return array<int, array<int, string>>
 */
function stressSampleRows(): array
{
    return [
        0 => ['Je travaille.', 'Я работаю.'],           // needs stress (работаю has 4 vowels)
        1 => ['Je suis ici.',  'Я здесь.'],              // no stress needed (здесь is 1 vowel, я is 1 vowel)
        2 => ['Je mange.',     'Я <b>е</b>м.'],          // already accented / single vowel — no correction
        3 => ['Source.',       'Хор<b>о</b>шо.'],        // already accented
        4 => ['Empty.',        ''],                      // empty russian — skip
    ];
}
/**
 * Sample rows for TTS: two rows missing audio, one with existing audio.
 *
 * @return array<int, array<int, string>>
 */
function ttsSampleRows(): array
{
    return [
        0 => ['Source A', 'Я работаю.'],
        1 => ['Source B', 'Хорошо.'],
        2 => ['Source C', 'Привет.'],    // will be faked as "has audio"
    ];
}
// ---------------------------------------------------------------------------
// Stress counting & estimation
// ---------------------------------------------------------------------------
it('counts zero stress rows for a fully-accented CSV', function (): void {
    $rows = [
        ['Source', 'Хор<b>о</b>шо.'],
        ['Source', 'Я здесь.'],
    ];
    expect(makeService()->countRowsMissingStress($rows))->toBe(0);
});
it('counts only rows that genuinely need stress correction', function (): void {
    expect(makeService()->countRowsMissingStress(stressSampleRows()))->toBe(1);
});
it('returns zero tokens and cost when no rows need stress correction', function (): void {
    $rows = [['Source', 'Хор<b>о</b>шо.']];
    $result = makeService()->estimateStressBatchCost($rows);
    expect($result['rowCount'])->toBe(0)
        ->and($result['inputTokens'])->toBe(0)
        ->and($result['outputTokens'])->toBe(0)
        ->and($result['estimatedCost'])->toBe(0.0);
});
it('estimates tokens correctly for a known input', function (): void {
    // One row: source = "Je travaille." (13 chars), russian = "Я работаю." (10 chars)
    // input  = 420 + ceil((13 + 10) / 3.5) = 420 + 7 = 427
    // output = ceil(10 / 3.5) = 3
    $rows = [['Je travaille.', 'Я работаю.']];
    $result = makeService()->estimateStressBatchCost($rows);
    expect($result['rowCount'])->toBe(1)
        ->and($result['inputTokens'])->toBe(427)
        ->and($result['outputTokens'])->toBe(3);
});
// ---------------------------------------------------------------------------
// TTS counting & estimation
// ---------------------------------------------------------------------------
it('counts zero TTS rows when all phrases already have audio', function (): void {
    Storage::fake('local');
    $service = makeService();
    $rows = [['Source', 'Привет.']];
    // Seed a fake audio file so existence check passes.
    $hash = (new RussianTextToSpeechService)->buildFilenameHash('Привет.');
    Storage::disk('local')->put("tts/{$hash}.mp3", 'fake-audio');
    expect($service->countRowsMissingAudio($rows))->toBe(0);
});
it('counts rows missing audio correctly', function (): void {
    Storage::fake('local');
    $rows = ttsSampleRows();
    // Seed audio only for "Привет." (row 2).
    $hash = (new RussianTextToSpeechService)->buildFilenameHash('Привет.');
    Storage::disk('local')->put("tts/{$hash}.mp3", 'fake-audio');
    expect(makeService()->countRowsMissingAudio($rows))->toBe(2);
});
it('returns zero chars and cost when all TTS audio exists', function (): void {
    Storage::fake('local');
    $rows = [['Source', 'Привет.']];
    $hash = (new RussianTextToSpeechService)->buildFilenameHash('Привет.');
    Storage::disk('local')->put("tts/{$hash}.mp3", 'fake-audio');
    $result = makeService()->estimateTtsBatchCost($rows);
    expect($result['rowCount'])->toBe(0)
        ->and($result['totalChars'])->toBe(0)
        ->and($result['estimatedCost'])->toBe(0.0);
});
it('estimates TTS characters for known missing rows', function (): void {
    Storage::fake('local');
    // "Я работаю." normalised = "Я работаю." — 10 chars
    // "Хорошо."   normalised = "Хорошо."    —  7 chars
    $rows = [['A', 'Я работаю.'], ['B', 'Хорошо.']];
    $result = makeService()->estimateTtsBatchCost($rows);
    expect($result['rowCount'])->toBe(2)
        ->and($result['totalChars'])->toBe(17);
});
// ---------------------------------------------------------------------------
// Batch dispatching
// ---------------------------------------------------------------------------
it('dispatches the correct number of stress jobs', function (): void {
    Queue::fake();
    $dispatched = makeService()->dispatchStressBatch(stressSampleRows(), 'sess-1');
    expect($dispatched)->toBe(1);
    Queue::assertPushed(MassOperationJob::class, 1);
});
it('dispatches stress jobs with the correct operation type', function (): void {
    Queue::fake();
    makeService()->dispatchStressBatch(stressSampleRows(), 'sess-1');
    Queue::assertPushed(MassOperationJob::class, function (MassOperationJob $job): bool {
        return $job->operationType === 'stress'
            && $job->sessionId === 'sess-1'
            && $job->totalRows === 1;
    });
});
it('initialises cache keys when dispatching a stress batch', function (): void {
    Queue::fake();
    makeService()->dispatchStressBatch(stressSampleRows(), 'sess-2');
    expect(Cache::get('mass_op:sess-2:stress:status'))->toBe('running')
        ->and((int) Cache::get('mass_op:sess-2:stress:total'))->toBe(1)
        ->and((int) Cache::get('mass_op:sess-2:stress:processed'))->toBe(0)
        ->and((int) Cache::get('mass_op:sess-2:stress:failed'))->toBe(0);
});
it('returns 0 and dispatches nothing when no rows need stress correction', function (): void {
    Queue::fake();
    $rows = [['Source', 'Хор<b>о</b>шо.']];
    $dispatched = makeService()->dispatchStressBatch($rows, 'sess-3');
    expect($dispatched)->toBe(0);
    Queue::assertNothingPushed();
});
it('dispatches the correct number of TTS jobs', function (): void {
    Queue::fake();
    Storage::fake('local');
    $dispatched = makeService()->dispatchTtsBatch(ttsSampleRows(), 'sess-4');
    // All three rows have non-empty russian text but no cached audio.
    expect($dispatched)->toBe(3);
    Queue::assertPushed(MassOperationJob::class, 3);
});
it('dispatches TTS jobs with the correct operation type', function (): void {
    Queue::fake();
    Storage::fake('local');
    makeService()->dispatchTtsBatch(ttsSampleRows(), 'sess-4');
    Queue::assertPushed(MassOperationJob::class, function (MassOperationJob $job): bool {
        return $job->operationType === 'tts';
    });
});
it('returns 0 and dispatches nothing when all TTS audio exists', function (): void {
    Queue::fake();
    Storage::fake('local');
    $rows = [['Source', 'Привет.']];
    $hash = (new RussianTextToSpeechService)->buildFilenameHash('Привет.');
    Storage::disk('local')->put("tts/{$hash}.mp3", 'fake-audio');
    $dispatched = makeService()->dispatchTtsBatch($rows, 'sess-5');
    expect($dispatched)->toBe(0);
    Queue::assertNothingPushed();
});
// ---------------------------------------------------------------------------
// Progress reading
// ---------------------------------------------------------------------------
it('returns idle progress when no batch has been started', function (): void {
    $progress = makeService()->getOperationProgress('unknown-session', 'stress');
    expect($progress['status'])->toBe('idle')
        ->and($progress['total'])->toBe(0)
        ->and($progress['processed'])->toBe(0)
        ->and($progress['failed'])->toBe(0);
});
it('returns the current progress from cache', function (): void {
    Cache::put('mass_op:sess-6:tts:status', 'running', 3600);
    Cache::put('mass_op:sess-6:tts:total', 10, 3600);
    Cache::put('mass_op:sess-6:tts:processed', 4, 3600);
    Cache::put('mass_op:sess-6:tts:failed', 1, 3600);
    $progress = makeService()->getOperationProgress('sess-6', 'tts');
    expect($progress['status'])->toBe('running')
        ->and($progress['total'])->toBe(10)
        ->and($progress['processed'])->toBe(4)
        ->and($progress['failed'])->toBe(1);
});

it('returns zero stress report values when no batch has run', function (): void {
    $report = makeService()->getStressReport('unknown-session');
    expect($report['corrected'])->toBe(0)
        ->and($report['promptTokens'])->toBe(0)
        ->and($report['completionTokens'])->toBe(0);
});

it('returns the stress report from cache after a batch', function (): void {
    Cache::put('mass_op:sess-7:stress:corrected', 5, 3600);
    Cache::put('mass_op:sess-7:stress:prompt_tokens', 1200, 3600);
    Cache::put('mass_op:sess-7:stress:completion_tokens', 300, 3600);
    $report = makeService()->getStressReport('sess-7');
    expect($report['corrected'])->toBe(5)
        ->and($report['promptTokens'])->toBe(1200)
        ->and($report['completionTokens'])->toBe(300);
});

it('returns zero TTS report values when no batch has run', function (): void {
    $report = makeService()->getTtsReport('unknown-session');
    expect($report['generated'])->toBe(0)
        ->and($report['actualChars'])->toBe(0);
});

it('returns the TTS report from cache after a batch', function (): void {
    Cache::put('mass_op:sess-8:tts:generated', 12, 3600);
    Cache::put('mass_op:sess-8:tts:actual_chars', 480, 3600);
    $report = makeService()->getTtsReport('sess-8');
    expect($report['generated'])->toBe(12)
        ->and($report['actualChars'])->toBe(480);
});

it('initialises TTS report cache keys when dispatching a TTS batch', function (): void {
    Queue::fake();
    Storage::fake('local');
    makeService()->dispatchTtsBatch(ttsSampleRows(), 'sess-9');
    expect((int) Cache::get('mass_op:sess-9:tts:generated'))->toBe(0)
        ->and((int) Cache::get('mass_op:sess-9:tts:actual_chars'))->toBe(0);
});
