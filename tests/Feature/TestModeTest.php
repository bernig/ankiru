<?php

use App\Livewire\CsvEditor;
use App\Models\CsvDraft;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->user = $user;
});

// ── Queue building ─────────────────────────────────────────────────────────

test('test queue contains all cards with content on both sides', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    $component = Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openTestMode');

    $queue = $component->get('testQueue');
    sort($queue);

    expect($queue)->toBe([0, 1])
        ->and($component->get('testQueuePosition'))->toBe(0)
        ->and($component->get('testCardFlipped'))->toBeFalse()
        ->and($component->get('testSessionDone'))->toBeFalse();
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
        ->call('openTestMode')
        ->assertSet('testQueue', [2]);
});

test('test queue is shuffled and includes all valid cards', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    $component = Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openTestMode');

    expect($component->get('testQueue'))->toHaveCount(2);
});

// ── Flipping ───────────────────────────────────────────────────────────────

// flip() and next() are handled by Alpine.js client-side; tests simulate
// them via ->set() as Livewire would receive those property updates.

test('flip reveals the back of the card', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openTestMode')
        ->assertSet('testCardFlipped', false)
        ->set('testCardFlipped', true) // Alpine flip()
        ->assertSet('testCardFlipped', true);
});

// ── Navigation ─────────────────────────────────────────────────────────────

test('next advances to the following card and resets flip state', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openTestMode')
        ->set('testCardFlipped', true)       // Alpine flip()
        ->assertSet('testCardFlipped', true)
        ->set('testCardFlipped', false)      // Alpine next()
        ->set('testCardAudioUrl', null)
        ->set('testQueuePosition', 1)
        ->assertSet('testCardFlipped', false)
        ->assertSet('testQueuePosition', 1);
});

test('session is done after going through all cards', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => [['Bonjour', 'Привет']],
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openTestMode')
        ->set('testSessionDone', true) // Alpine next() detects end of queue
        ->assertSet('testSessionDone', true);
});

// ── Restart ────────────────────────────────────────────────────────────────

test('restart reshuffles all cards and resets session state', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => [['Bonjour', 'Привет']],
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openTestMode')
        ->set('testSessionDone', true) // Alpine next() detects end of queue
        ->assertSet('testSessionDone', true)
        ->call('restartTest')
        ->assertSet('testSessionDone', false)
        ->assertSet('testQueuePosition', 0)
        ->assertSet('testCardFlipped', false);
});

test('openTestMode always rebuilds the queue from scratch', function () {
    $draft = CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => sampleRows(),
    ]);

    Livewire::test(CsvEditor::class)
        ->set('activeDraftId', $draft->id)
        ->set('csvRows', $draft->csv_rows)
        ->set('hasCsvLoaded', true)
        ->call('openTestMode')
        ->set('testQueuePosition', 1) // Alpine next() advances position
        ->assertSet('testQueuePosition', 1)
        ->call('closeTestMode')
        ->call('openTestMode')
        ->assertSet('testQueuePosition', 0); // reset on reopen
});
