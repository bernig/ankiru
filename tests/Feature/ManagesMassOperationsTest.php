<?php

use App\Ai\Agents\RussianStressCorrectorAgent;
use App\Enums\OperationType;
use App\Jobs\MassOperationJob;
use App\Livewire\CsvEditor;
use App\Models\User;
use App\Services\MassOperationService;
use App\Services\OpenAiTranslationService;
use App\Services\RussianAccentService;
use App\Services\RussianTextToSpeechService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery\MockInterface;

beforeEach(function (): void {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create(['openai_api_key' => 'sk-test-key-for-automated-tests']);

    $this->actingAs($authenticatedUser);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Two CSV rows that already carry stress marks (using <b> tags, which is the
 * application's canonical format). Every multi-vowel Russian word has exactly one
 * accented vowel, so missingStressRowCount should equal 0 for these rows.
 *
 * @return array<int, array<int, string>>
 */
function massOpRows(): array
{
    return [
        ['Je travaille depuis chez moi.', 'Я раб<b>о</b>таю из д<b>о</b>ма.'],
        ['Je suis développeur web.', 'Я веб-разраб<b>о</b>тчик.'],
    ];
}

/**
 * Stub the two cost-estimate methods on a MassOperationService mock so they
 * return zero values. Used in tests that trigger estimate refreshes as a side
 * effect but do not assert on the estimate values themselves.
 */
function stubMassServiceEstimates(MockInterface $mockService): void
{
    $mockService->shouldReceive('estimateStressBatchCost')->andReturn([
        'rowCount' => 0, 'inputTokens' => 0, 'outputTokens' => 0, 'estimatedCost' => 0.0,
    ]);
    $mockService->shouldReceive('estimateTtsBatchCost')->andReturn([
        'rowCount' => 0, 'totalChars' => 0, 'estimatedCost' => 0.0,
    ]);
}

// ---------------------------------------------------------------------------
// Default state
// ---------------------------------------------------------------------------

it('has idle batch statuses by default', function () {
    Livewire::test(CsvEditor::class)
        ->assertSet('stressBatchStatus', 'idle')
        ->assertSet('ttsBatchStatus', 'idle')
        ->assertSet('stressBatchProgress', 0)
        ->assertSet('ttsBatchProgress', 0);
});

// ---------------------------------------------------------------------------
// openBulkActionsModal – estimate population
// ---------------------------------------------------------------------------

it('populates estimates when the bulk actions modal is opened', function () {
    Storage::fake('local');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', massOpRows())
        ->set('hasCsvLoaded', true)
        ->call('openBulkActionsModal')
        ->assertSet('missingStressRowCount', 0)   // rows already have stress marks
        ->assertSet('missingAudioRowCount', 2);    // no audio files exist yet
});

it('syncs in-flight stress progress from cache when modal is opened', function () {
    // Mock MassOperationService so we control what getOperationProgress returns
    // without having to coordinate session IDs between the test body and Livewire.
    $mockService = Mockery::mock(MassOperationService::class);

    $mockService->shouldReceive('getOperationProgress')
        ->with(Mockery::any(), OperationType::Stress)
        ->andReturn(['status' => 'running', 'total' => 5, 'processed' => 3, 'failed' => 1]);

    $mockService->shouldReceive('getOperationProgress')
        ->with(Mockery::any(), OperationType::Tts)
        ->andReturn(['status' => 'idle', 'total' => 0, 'processed' => 0, 'failed' => 0]);

    stubMassServiceEstimates($mockService);

    app()->instance(MassOperationService::class, $mockService);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', massOpRows())
        ->set('hasCsvLoaded', true)
        ->call('openBulkActionsModal')
        ->assertSet('stressBatchStatus', 'running')
        ->assertSet('stressBatchTotal', 5)
        ->assertSet('stressBatchProgress', 3)
        ->assertSet('stressBatchFailed', 1);
});

it('refreshes running batch progress from cache when a websocket update is missed', function () {
    Storage::fake('local');

    $sessionId = session()->getId();
    Cache::put("mass_op:{$sessionId}:stress:row:0", 'Я говор<b>ю</b>.', now()->addMinutes(10));

    $mockService = Mockery::mock(MassOperationService::class);

    $mockService->shouldReceive('getOperationProgress')
        ->with(Mockery::any(), OperationType::Stress)
        ->andReturn(['status' => 'done', 'total' => 1, 'processed' => 1, 'failed' => 0]);

    $mockService->shouldReceive('getOperationProgress')
        ->with(Mockery::any(), OperationType::Tts)
        ->andReturn(['status' => 'idle', 'total' => 0, 'processed' => 0, 'failed' => 0]);

    $mockService->shouldReceive('getStressReport')
        ->andReturn(['corrected' => 1, 'promptTokens' => 24, 'completionTokens' => 8]);

    stubMassServiceEstimates($mockService);

    app()->instance(MassOperationService::class, $mockService);

    $lw = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je parle.', 'Я говорю.']])
        ->set('hasCsvLoaded', true)
        ->set('stressBatchStatus', 'running')
        ->set('stressBatchTotal', 1)
        ->call('refreshRunningBatchProgress');

    $lw->assertSet('stressBatchStatus', 'done')
        ->assertSet('stressBatchProgress', 1)
        ->assertSet('stressBatchFailed', 0)
        ->assertSet('stressBatchCorrectedCount', 1);

    $csvRows = $lw->get('csvRows');
    expect($csvRows[0][1])->toBe('Я говор<b>ю</b>.');
});

// ---------------------------------------------------------------------------
// dispatchStressBatch
// ---------------------------------------------------------------------------

it('dispatches stress jobs and sets running status', function () {
    Queue::fake();

    $rowsNeedingCorrection = [
        ['Je parle.', 'Я говорю.'],   // no stress marks → needs correction
        ['Il fait beau.', ''],          // no Russian text → skipped
    ];

    Livewire::test(CsvEditor::class)
        ->set('csvRows', $rowsNeedingCorrection)
        ->set('hasCsvLoaded', true)
        ->call('dispatchStressBatch')
        ->assertSet('stressBatchStatus', 'running')
        ->assertSet('stressBatchTotal', 1)
        ->assertSet('stressBatchProgress', 0);

    Queue::assertPushed(MassOperationJob::class, 1);
});

it('does not dispatch stress jobs when batch is already running', function () {
    Queue::fake();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', massOpRows())
        ->set('hasCsvLoaded', true)
        ->set('stressBatchStatus', 'running')
        ->call('dispatchStressBatch');

    Queue::assertNothingPushed();
});

it('does not dispatch stress jobs when no CSV is loaded', function () {
    Queue::fake();

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', false)
        ->call('dispatchStressBatch');

    Queue::assertNothingPushed();
});

// ---------------------------------------------------------------------------
// dispatchTtsBatch
// ---------------------------------------------------------------------------

it('dispatches TTS jobs and sets running status', function () {
    Queue::fake();
    Storage::fake('local');

    $rowsNeedingAudio = [
        ['Je parle.', 'Я говорю.'],
        ['Il fait beau.', 'Хорошая погода.'],
    ];

    Livewire::test(CsvEditor::class)
        ->set('csvRows', $rowsNeedingAudio)
        ->set('hasCsvLoaded', true)
        ->call('dispatchTtsBatch')
        ->assertSet('ttsBatchStatus', 'running')
        ->assertSet('ttsBatchTotal', 2)
        ->assertSet('ttsBatchProgress', 0);

    Queue::assertPushed(MassOperationJob::class, 2);
});

it('does not dispatch TTS jobs when batch is already running', function () {
    Queue::fake();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', massOpRows())
        ->set('hasCsvLoaded', true)
        ->set('ttsBatchStatus', 'running')
        ->call('dispatchTtsBatch');

    Queue::assertNothingPushed();
});

// ---------------------------------------------------------------------------
// Sync-queue completion — stress
// ---------------------------------------------------------------------------

it('immediately applies stress batch completion when the sync queue driver runs jobs inline', function () {
    Storage::fake('local');

    $sessionId = session()->getId();

    // Pre-seed cache to simulate all jobs having finished inline.
    Cache::put("mass_op:{$sessionId}:stress:row:0", 'Я говор<b>ю</b>.', now()->addMinutes(10));
    Cache::put("mass_op:{$sessionId}:stress:row:1", 'Хорош<b>а</b>я погод<b>а</b>.', now()->addMinutes(10));

    $rowsNeedingCorrection = [
        ['Je parle.', 'Я говорю.'],
        ['Il fait beau.', 'Хорошая погода.'],
    ];

    $mockService = Mockery::mock(MassOperationService::class);

    $mockService->shouldReceive('dispatchStressBatch')->once()->andReturn(2);

    $mockService->shouldReceive('getOperationProgress')
        ->with(Mockery::any(), OperationType::Stress)
        ->andReturn(['status' => 'done', 'total' => 2, 'processed' => 2, 'failed' => 0]);

    $mockService->shouldReceive('getStressReport')
        ->andReturn(['corrected' => 2, 'promptTokens' => 50, 'completionTokens' => 30]);

    stubMassServiceEstimates($mockService);

    app()->instance(MassOperationService::class, $mockService);

    $lw = Livewire::test(CsvEditor::class)
        ->set('csvRows', $rowsNeedingCorrection)
        ->set('hasCsvLoaded', true)
        ->call('dispatchStressBatch');

    $lw->assertSet('stressBatchStatus', 'done')
        ->assertSet('stressBatchProgress', 2)
        ->assertSet('stressBatchFailed', 0)
        ->assertSet('stressBatchCorrectedCount', 2);

    $csvRows = $lw->get('csvRows');
    expect($csvRows[0][1])->toBe('Я говор<b>ю</b>.')
        ->and($csvRows[1][1])->toBe('Хорош<b>а</b>я погод<b>а</b>.');
});

it('bulk-corrects bare ё without calling the AI (real services, sync queue)', function () {
    Storage::fake('local');
    config(['queue.default' => 'sync']); // run jobs inline so merge happens in-request

    // The AI must NEVER be called — ё normalization is sufficient for these rows.
    RussianStressCorrectorAgent::fake(function () {
        throw new RuntimeException('AI should not have been called for a bare-ё-only fix.');
    });

    $rows = [
        // "Пойдём" has bare ё — the only issue in this sentence.
        ['Rentrons.', 'Пойдём дом<b>о</b>й.'],
        // "идёт" has bare ё — similar case with the original user example.
        ['Quel bus va au centre ?', 'Как<b>о</b>й авт<b>о</b>бус идёт в ц<b>е</b>нтр?'],
    ];

    $lw = Livewire::test(CsvEditor::class)
        ->set('csvRows', $rows)
        ->set('hasCsvLoaded', true)
        ->call('dispatchStressBatch');

    $csvRows = $lw->get('csvRows');

    expect($csvRows[0][1])->toBe('Пойд<b>ё</b>м дом<b>о</b>й.')
        ->and($csvRows[1][1])->toBe('Как<b>о</b>й авт<b>о</b>бус ид<b>ё</b>т в ц<b>е</b>нтр?');
});

it('pre-normalises ё immediately when batch is dispatched with an async queue driver', function () {
    Storage::fake('local');
    Queue::fake(); // jobs are pushed but NOT executed — simulates the async database driver

    $rows = [
        // "Пойдём" has bare ё — normalization must happen in-request, not in the job.
        ['Rentrons.', 'Пойдём дом<b>о</b>й.'],
        // Row with no ё issue remains unchanged.
        ['Je parle.', 'Я говорю.'],
    ];

    $lw = Livewire::test(CsvEditor::class)
        ->set('csvRows', $rows)
        ->set('hasCsvLoaded', true)
        ->call('dispatchStressBatch');

    $csvRows = $lw->get('csvRows');

    // ё row fixed immediately, before any job ran.
    expect($csvRows[0][1])->toBe('Пойд<b>ё</b>м дом<b>о</b>й.');
    // Row that still needs AI was pushed to the queue.
    Queue::assertPushed(MassOperationJob::class);
});

// ---------------------------------------------------------------------------
// Sync-queue completion — openBulkActionsModal catches missed 'done' event
// ---------------------------------------------------------------------------

it('merges pending stress corrections when the modal is opened after a missed done event', function () {
    Storage::fake('local');

    $sessionId = session()->getId();

    // Simulate the corrected texts sitting in cache (jobs ran, but 'done' event was missed).
    Cache::put("mass_op:{$sessionId}:stress:row:0", 'Я говор<b>ю</b>.', now()->addMinutes(10));
    Cache::put("mass_op:{$sessionId}:stress:row:1", 'Хорош<b>а</b>я погод<b>а</b>.', now()->addMinutes(10));

    $rowsNeedingCorrection = [
        ['Je parle.', 'Я говорю.'],
        ['Il fait beau.', 'Хорошая погода.'],
    ];

    $mockService = Mockery::mock(MassOperationService::class);

    $mockService->shouldReceive('getOperationProgress')
        ->with(Mockery::any(), OperationType::Stress)
        ->andReturn(['status' => 'done', 'total' => 2, 'processed' => 2, 'failed' => 0]);

    $mockService->shouldReceive('getOperationProgress')
        ->with(Mockery::any(), OperationType::Tts)
        ->andReturn(['status' => 'idle', 'total' => 0, 'processed' => 0, 'failed' => 0]);

    $mockService->shouldReceive('getStressReport')
        ->andReturn(['corrected' => 2, 'promptTokens' => 30, 'completionTokens' => 20]);

    stubMassServiceEstimates($mockService);

    app()->instance(MassOperationService::class, $mockService);

    $lw = Livewire::test(CsvEditor::class)
        ->set('csvRows', $rowsNeedingCorrection)
        ->set('hasCsvLoaded', true)
        ->call('openBulkActionsModal');

    $lw->assertSet('stressBatchStatus', 'done')
        ->assertSet('stressBatchCorrectedCount', 2);

    $csvRows = $lw->get('csvRows');
    expect($csvRows[0][1])->toBe('Я говор<b>ю</b>.')
        ->and($csvRows[1][1])->toBe('Хорош<b>а</b>я погод<b>а</b>.');

    // Row cache keys must be cleared after merging.
    expect(Cache::has("mass_op:{$sessionId}:stress:row:0"))->toBeFalse()
        ->and(Cache::has("mass_op:{$sessionId}:stress:row:1"))->toBeFalse();
});

// ---------------------------------------------------------------------------
// handleBatchProgressUpdate
// ---------------------------------------------------------------------------

it('updates stress state from a progress broadcast', function () {
    Livewire::test(CsvEditor::class)
        ->call('handleBatchProgressUpdate', [
            'operationType' => 'stress',
            'status' => 'running',
            'processedCount' => 2,
            'totalCount' => 5,
            'failedCount' => 0,
        ])
        ->assertSet('stressBatchStatus', 'running')
        ->assertSet('stressBatchProgress', 2)
        ->assertSet('stressBatchTotal', 5)
        ->assertSet('stressBatchFailed', 0);
});

it('updates TTS state from a progress broadcast', function () {
    Livewire::test(CsvEditor::class)
        ->call('handleBatchProgressUpdate', [
            'operationType' => 'tts',
            'status' => 'running',
            'processedCount' => 1,
            'totalCount' => 3,
            'failedCount' => 1,
        ])
        ->assertSet('ttsBatchStatus', 'running')
        ->assertSet('ttsBatchProgress', 1)
        ->assertSet('ttsBatchTotal', 3)
        ->assertSet('ttsBatchFailed', 1);
});

it('merges stress results from cache into csvRows when stress batch completes', function () {
    $sessionId = session()->getId();

    Cache::put("mass_op:{$sessionId}:stress:row:0", 'Я говорю́.', now()->addMinutes(10));
    Cache::put("mass_op:{$sessionId}:stress:row:1", 'Хоро́шая поро́да.', now()->addMinutes(10));

    $lw = Livewire::test(CsvEditor::class)
        ->set('csvRows', massOpRows())
        ->set('hasCsvLoaded', true)
        ->set('stressBatchStatus', 'running')
        ->call('handleBatchProgressUpdate', [
            'operationType' => 'stress',
            'status' => 'done',
            'processedCount' => 2,
            'totalCount' => 2,
            'failedCount' => 0,
        ]);

    $lw->assertSet('stressBatchStatus', 'done');

    $csvRows = $lw->get('csvRows');
    expect($csvRows[0][1])->toBe('Я говорю́.')
        ->and($csvRows[1][1])->toBe('Хоро́шая поро́да.');

    // Cache keys must be cleared after merging.
    expect(Cache::has("mass_op:{$sessionId}:stress:row:0"))->toBeFalse()
        ->and(Cache::has("mass_op:{$sessionId}:stress:row:1"))->toBeFalse();
});

it('populates TTS report props when TTS batch completes', function () {
    $mockService = Mockery::mock(MassOperationService::class);

    $mockService->shouldReceive('getTtsReport')
        ->once()
        ->andReturn(['generated' => 8, 'actualChars' => 320]);

    stubMassServiceEstimates($mockService);
    $mockService->shouldReceive('getOperationProgress')->andReturn([
        'status' => 'idle', 'total' => 0, 'processed' => 0, 'failed' => 0,
    ]);

    app()->instance(MassOperationService::class, $mockService);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', massOpRows())
        ->set('hasCsvLoaded', true)
        ->set('ttsBatchStatus', 'running')
        ->call('handleBatchProgressUpdate', [
            'operationType' => 'tts',
            'status' => 'done',
            'processedCount' => 8,
            'totalCount' => 8,
            'failedCount' => 0,
        ])
        ->assertSet('ttsBatchStatus', 'done')
        ->assertSet('ttsBatchGeneratedCount', 8)
        ->assertSet('ttsBatchActualChars', 320);
});

// ---------------------------------------------------------------------------
// syncProgressFromCache — orphaned running batch auto-cancel
// ---------------------------------------------------------------------------

it('auto-cancels a running batch when no jobs remain in the queue', function () {
    config(['queue.default' => 'database']);

    $mockService = Mockery::mock(MassOperationService::class);

    $mockService->shouldReceive('getOperationProgress')
        ->with(Mockery::any(), OperationType::Tts)
        ->andReturn(['status' => 'running', 'total' => 16, 'processed' => 7, 'failed' => 0]);

    $mockService->shouldReceive('getOperationProgress')
        ->with(Mockery::any(), OperationType::Stress)
        ->andReturn(['status' => 'idle', 'total' => 0, 'processed' => 0, 'failed' => 0]);

    $mockService->shouldReceive('getTtsReport')
        ->andReturn(['generated' => 7, 'actualChars' => 200]);

    stubMassServiceEstimates($mockService);

    app()->instance(MassOperationService::class, $mockService);

    // No jobs in the queue — the batch is orphaned.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', massOpRows())
        ->set('hasCsvLoaded', true)
        ->call('openBulkActionsModal')
        ->assertSet('ttsBatchStatus', 'done');
});

// ---------------------------------------------------------------------------
// cancelStressBatch / cancelTtsBatch
// ---------------------------------------------------------------------------

it('cancelling a stress batch sets its status to done and removes pending jobs', function () {
    Queue::fake();

    $rowsNeedingCorrection = [
        ['Je parle.', 'Я говорю.'],
        ['Il dit bonjour.', 'Он говорит привет.'],
    ];

    $lw = Livewire::test(CsvEditor::class)
        ->set('csvRows', $rowsNeedingCorrection)
        ->set('hasCsvLoaded', true)
        ->call('dispatchStressBatch')
        ->assertSet('stressBatchStatus', 'running');

    $lw->call('cancelStressBatch')
        ->assertSet('stressBatchStatus', 'done');

    $sessionId = session()->getId();
    expect(Cache::get("mass_op:{$sessionId}:stress:cancelled"))->toBeTrue();
    expect(Cache::get("mass_op:{$sessionId}:stress:status"))->toBe('done');
});

it('cancelling a TTS batch sets its status to done', function () {
    Queue::fake();
    Storage::fake('local');

    $rows = [
        ['Je parle.', 'Я говорю.'],
    ];

    $lw = Livewire::test(CsvEditor::class)
        ->set('csvRows', $rows)
        ->set('hasCsvLoaded', true)
        ->call('dispatchTtsBatch')
        ->assertSet('ttsBatchStatus', 'running');

    $lw->call('cancelTtsBatch')
        ->assertSet('ttsBatchStatus', 'done');

    $sessionId = session()->getId();
    expect(Cache::get("mass_op:{$sessionId}:tts:cancelled"))->toBeTrue();
});

it('a cancelled job skips processing', function () {
    $sessionId = 'test-session-cancel';
    Cache::put("mass_op:{$sessionId}:tts:cancelled", true, ttl: 60);

    $ttsService = Mockery::mock(RussianTextToSpeechService::class);
    $ttsService->shouldNotReceive('generateAudio');

    $job = new MassOperationJob(
        operationType: OperationType::Tts,
        sessionId: $sessionId,
        rowIndex: 0,
        totalRows: 1,
        sourceText: 'Je parle.',
        russianText: 'Я говорю.',
    );

    $accentService = app(RussianAccentService::class);
    $translationService = app(OpenAiTranslationService::class);

    $job->handle($accentService, app(OpenAiTranslationService::class), $ttsService);
});

// ---------------------------------------------------------------------------
// isStressBatchRunning / isTtsBatchRunning computed helpers
// ---------------------------------------------------------------------------

it('reports isStressBatchRunning correctly', function () {
    Livewire::test(CsvEditor::class)
        ->set('stressBatchStatus', 'idle')
        ->assertSet('isStressBatchRunning', false)
        ->set('stressBatchStatus', 'running')
        ->assertSet('isStressBatchRunning', true)
        ->set('stressBatchStatus', 'done')
        ->assertSet('isStressBatchRunning', false);
});

it('reports isTtsBatchRunning correctly', function () {
    Livewire::test(CsvEditor::class)
        ->set('ttsBatchStatus', 'idle')
        ->assertSet('isTtsBatchRunning', false)
        ->set('ttsBatchStatus', 'running')
        ->assertSet('isTtsBatchRunning', true)
        ->set('ttsBatchStatus', 'done')
        ->assertSet('isTtsBatchRunning', false);
});

// ---------------------------------------------------------------------------
// Per-row action guards
// ---------------------------------------------------------------------------

it('blocks translateWithChatGpt while a stress batch is running', function () {
    $translationService = Mockery::mock(OpenAiTranslationService::class);
    $translationService->shouldNotReceive('translateSourceToRussianWithUsage');
    app()->instance(OpenAiTranslationService::class, $translationService);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je parle.', '']])
        ->set('hasCsvLoaded', true)
        ->set('stressBatchStatus', 'running')
        ->call('translateWithChatGpt', 0)
        ->assertSet('translatingRowIndex', -1);
});

it('blocks correctStressMarks while a stress batch is running', function () {
    $translationService = Mockery::mock(OpenAiTranslationService::class);
    $translationService->shouldNotReceive('correctRussianStressMarksWithUsage');
    app()->instance(OpenAiTranslationService::class, $translationService);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je parle.', 'Я говорю.']])
        ->set('hasCsvLoaded', true)
        ->set('stressBatchStatus', 'running')
        ->call('correctStressMarks', 0)
        ->assertSet('correctingStressRowIndex', -1);
});

it('blocks generateTtsAudio while a TTS batch is running', function () {
    $ttsService = Mockery::mock(RussianTextToSpeechService::class);
    $ttsService->shouldNotReceive('generateAudio');
    // normalizeForSpeech is called before the batch-running guard.
    $ttsService->shouldReceive('normalizeForSpeech')->andReturn('Я говорю.');
    // audioFilesExistBatch is called during render for the audioExistenceByRowIndex computed.
    $ttsService->shouldReceive('audioFilesExistBatch')->andReturn([]);
    app()->instance(RussianTextToSpeechService::class, $ttsService);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je parle.', 'Я говорю.']])
        ->set('hasCsvLoaded', true)
        ->set('ttsBatchStatus', 'running')
        ->call('generateTtsAudio', 0)
        ->assertSet('ttsGeneratingRowIndex', -1);
});

// ---------------------------------------------------------------------------
// Phase 8 — rendered HTML reflects disabled state
// ---------------------------------------------------------------------------

it('renders the stress-correction button as disabled when a stress batch is running', function () {
    Storage::fake('local');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je parle.', 'Я говорю.']])
        ->set('hasCsvLoaded', true)
        ->set('stressBatchStatus', 'running')
        ->assertSeeHtml('wire:poll.3s="refreshRunningBatchProgress"')
        ->assertSeeHtml('disabled');
});

it('renders the TTS button as disabled when a TTS batch is running', function () {
    Storage::fake('local');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je parle.', 'Я говорю.']])
        ->set('hasCsvLoaded', true)
        ->set('ttsBatchStatus', 'running')
        ->assertSeeHtml('disabled');
});
