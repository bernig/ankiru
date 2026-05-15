<?php

use App\Livewire\CsvEditor;
use App\Models\CardReview;
use App\Models\CsvDraft;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->user = $user;
});

// ── Queue building ─────────────────────────────────────────────────────────

test('practice queue contains new cards when no reviews exist', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->assertSet('practiceQueue', [0, 1])
        ->assertSet('practiceQueuePosition', 0)
        ->assertSet('practiceCardFlipped', false)
        ->assertSet('practiceSessionDone', false);
});

test('practice queue excludes cards not due yet', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    CardReview::create([
        'user_id' => $this->user->id,
        'csv_draft_id' => $draft->id,
        'row_index' => 0,
        'repetitions' => 2,
        'ease_factor' => '2.50',
        'interval_days' => 10,
        'due_date' => Carbon::today()->addDays(5),
        'last_reviewed_at' => now(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->assertSet('practiceQueue', [1]);
});

test('practice queue includes overdue cards', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    CardReview::create([
        'user_id' => $this->user->id,
        'csv_draft_id' => $draft->id,
        'row_index' => 0,
        'repetitions' => 1,
        'ease_factor' => '2.50',
        'interval_days' => 6,
        'due_date' => Carbon::yesterday(),
        'last_reviewed_at' => now()->subDays(7),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->assertSet('practiceQueue', [0, 1]);
});

test('cards with empty source or russian text are excluded from the queue', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => [
            ['Bonjour', ''],
            ['', 'Мир'],
            ['Au revoir', 'До свидания'],
        ],
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->assertSet('practiceQueue', [2]);
});

// ── Flipping ───────────────────────────────────────────────────────────────

test('flip reveals the back of the card', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->assertSet('practiceCardFlipped', false)
        ->call('flipPracticeCard')
        ->assertSet('practiceCardFlipped', true);
});

// ── SM-2 grading ──────────────────────────────────────────────────────────

test('grading Again (0) resets repetitions and queues the card again', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->call('flipPracticeCard')
        ->call('gradePracticeCard', 0);

    $review = CardReview::where('user_id', $this->user->id)
        ->where('csv_draft_id', $draft->id)
        ->where('row_index', 0)
        ->first();

    expect($review)->not->toBeNull()
        ->and($review->repetitions)->toBe(0)
        ->and($review->interval_days)->toBe(1);
});

test('grading Good (4) on first repetition sets interval to 1', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->call('flipPracticeCard')
        ->call('gradePracticeCard', 4);

    $review = CardReview::where('user_id', $this->user->id)
        ->where('csv_draft_id', $draft->id)
        ->where('row_index', 0)
        ->first();

    expect($review->repetitions)->toBe(1)
        ->and($review->interval_days)->toBe(1)
        ->and($review->due_date->isTomorrow())->toBeTrue();
});

test('grading Good (4) on second repetition sets interval to 6', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => [['Bonjour', 'Привет']],
    ]);

    CardReview::create([
        'user_id' => $this->user->id,
        'csv_draft_id' => $draft->id,
        'row_index' => 0,
        'repetitions' => 1,
        'ease_factor' => '2.50',
        'interval_days' => 1,
        'due_date' => Carbon::today(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->call('flipPracticeCard')
        ->call('gradePracticeCard', 4);

    $review = CardReview::where('row_index', 0)->where('csv_draft_id', $draft->id)->first();

    expect($review->repetitions)->toBe(2)
        ->and($review->interval_days)->toBe(6);
});

test('grading Easy (5) increases ease factor', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => [['Bonjour', 'Привет']],
    ]);

    CardReview::create([
        'user_id' => $this->user->id,
        'csv_draft_id' => $draft->id,
        'row_index' => 0,
        'repetitions' => 2,
        'ease_factor' => '2.50',
        'interval_days' => 6,
        'due_date' => Carbon::today(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->call('flipPracticeCard')
        ->call('gradePracticeCard', 5);

    $review = CardReview::where('row_index', 0)->where('csv_draft_id', $draft->id)->first();

    expect((float) $review->ease_factor)->toBeGreaterThan(2.50)
        ->and($review->interval_days)->toBe(15); // round(6 * 2.5) = 15
});

// ── Session completion ─────────────────────────────────────────────────────

test('session is done after grading all cards', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => [['Bonjour', 'Привет']],
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->call('flipPracticeCard')
        ->call('gradePracticeCard', 4)
        ->assertSet('practiceSessionDone', true)
        ->assertSet('practiceReviewedCount', 1);
});

test('again cards are re-appended to the queue and session ends after re-review', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => [['Bonjour', 'Привет']],
    ]);

    $component = Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->call('flipPracticeCard')
        ->call('gradePracticeCard', 0)
        ->assertSet('practiceSessionDone', false);

    // The card was re-queued; review it again as Good.
    $component
        ->call('flipPracticeCard')
        ->call('gradePracticeCard', 4)
        ->assertSet('practiceSessionDone', true);
});

test('reopening mid-session resumes at the current position without rebuilding the queue', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    $component = Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openPracticeMode')
        ->call('flipPracticeCard')
        ->call('gradePracticeCard', 4); // review card 0, advance to position 1

    $component->assertSet('practiceQueuePosition', 1);

    // Close then reopen.
    $component
        ->call('closePracticeMode')
        ->call('openPracticeMode')
        ->assertSet('practiceQueuePosition', 1) // still at card 1, not reset to 0
        ->assertSet('practiceSessionDone', false);
});
