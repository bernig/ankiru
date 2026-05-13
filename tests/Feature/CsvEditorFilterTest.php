<?php

use App\Livewire\CsvEditor;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create();

    $this->actingAs($authenticatedUser);
});

// ── Default state ────────────────────────────────────────────────────────────

test('filter toggles are false by default', function (): void {
    Livewire::test(CsvEditor::class)
        ->assertSet('filterAccentNeeded', false)
        ->assertSet('filterNoAudio', false);
});

// ── filterAccentNeeded ────────────────────────────────────────────────────────

test('filterAccentNeeded shows no-results message when all rows already have accents', function (): void {
    $rows = [
        ['Row A', 'Я раб<b>о</b>таю.'],
        ['Row B', 'всё'],
    ];

    Livewire::test(CsvEditor::class)
        ->set('csvRows', $rows)
        ->set('hasCsvLoaded', true)
        ->set('filterAccentNeeded', true)
        ->assertSee(__('csv_editor.no_search_results'));
});

test('filterAccentNeeded does not show no-results message when some rows need accents', function (): void {
    $rows = [
        ['Row A', 'работать'],           // needs accent (3 vowels, no <b>)
        ['Row B', 'Я раб<b>о</b>таю.'], // already accented
    ];

    Livewire::test(CsvEditor::class)
        ->set('csvRows', $rows)
        ->set('hasCsvLoaded', true)
        ->set('filterAccentNeeded', true)
        ->assertDontSee(__('csv_editor.no_search_results'));
});

test('filterAccentNeeded can be toggled on and off', function (): void {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('filterAccentNeeded', true)
        ->assertSet('filterAccentNeeded', true)
        ->set('filterAccentNeeded', false)
        ->assertSet('filterAccentNeeded', false);
});

// ── filterNoAudio ─────────────────────────────────────────────────────────────

test('filterNoAudio shows no-results message when all rows have audio', function (): void {
    Storage::fake('local');

    $russianText = 'Я работаю.';
    $hash = hash('sha256', $russianText);
    Storage::disk('local')->put("tts/{$hash}.mp3", 'fake-audio');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Row A', $russianText]])
        ->set('hasCsvLoaded', true)
        ->set('filterNoAudio', true)
        ->assertSee(__('csv_editor.no_search_results'));
});

test('filterNoAudio does not show no-results when some rows are missing audio', function (): void {
    Storage::fake('local');

    $rowWithAudio = ['Row A', 'Я работаю.'];
    $rowWithoutAudio = ['Row B', 'Я читаю книгу.'];
    $hash = hash('sha256', 'Я работаю.');
    Storage::disk('local')->put("tts/{$hash}.mp3", 'fake-audio');

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [$rowWithAudio, $rowWithoutAudio])
        ->set('hasCsvLoaded', true)
        ->set('filterNoAudio', true)
        ->assertDontSee(__('csv_editor.no_search_results'));
});

test('filterNoAudio excludes rows with empty russian text', function (): void {
    Storage::fake('local');

    $rows = [
        ['Row A', ''],           // empty russian — not included in "no audio" filter
        ['Row B', 'Я читаю.'],   // has russian text, no audio — should appear
    ];

    Livewire::test(CsvEditor::class)
        ->set('csvRows', $rows)
        ->set('hasCsvLoaded', true)
        ->set('filterNoAudio', true)
        ->assertDontSee(__('csv_editor.no_search_results'));
});

// ── Combined filters ──────────────────────────────────────────────────────────

test('filterAccentNeeded and search query can be combined', function (): void {
    $rows = [
        ['Bonjour', 'работать'],            // needs accent, matches "bonjour"
        ['Au revoir', 'работать'],          // needs accent, does not match "bonjour"
        ['Bonjour', 'Я раб<b>о</b>таю.'],  // already accented, matches "bonjour"
    ];

    // Only "Bonjour / работать" should pass both filters.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', $rows)
        ->set('hasCsvLoaded', true)
        ->set('filterAccentNeeded', true)
        ->set('searchQuery', 'Bonjour')
        ->assertDontSee(__('csv_editor.no_search_results'));
});

// ── Filters reset on file switch ──────────────────────────────────────────────

test('filters are cleared when resetting the editor', function (): void {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('filterAccentNeeded', true)
        ->set('filterNoAudio', true)
        ->call('resetEditor')
        ->assertSet('filterAccentNeeded', false)
        ->assertSet('filterNoAudio', false);
});

test('filters are cleared when creating a new file', function (): void {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->set('filterAccentNeeded', true)
        ->set('filterNoAudio', true)
        ->call('createNewFile')
        ->assertSet('filterAccentNeeded', false)
        ->assertSet('filterNoAudio', false);
});
