<?php

use App\Livewire\CsvEditor;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create();

    $this->actingAs($authenticatedUser);
});

// ── Cell Editing ────────────────────────────────────────────────────────────
test('updates a cell value in the left column', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 0, 'Je reste à la maison.')
        ->assertSet('csvRows.0.0', 'Je reste à la maison.');
});

test('updates a cell value in the right column', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 1, 'Я ост<b>а</b>юсь д<b>о</b>ма.')
        ->assertSet('csvRows.0.1', 'Я ост<b>а</b>юсь д<b>о</b>ма.');
});

test('row needs stress correction returns false after updating column 1 with fully accented text', function () {
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 1, 'Я ост<b>а</b>юсь д<b>о</b>ма.');

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
});

test('row needs stress correction is unaffected when only the left column is edited', function () {
    // Start with Russian text that has no accent on a 3-vowel word.
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('updateCell', 0, 0, 'Je rentre chez moi.');

    // Russian column was not touched — still needs an accent.
    expect($component->instance()->rowNeedsStressCorrection(0))->toBeTrue();
});

// ── Row Management ──────────────────────────────────────────────────────────
test('adds an empty row at the end', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('addRow')
        ->assertCount('csvRows', 3)
        ->assertSet('csvRows.2.0', '')
        ->assertSet('csvRows.2.1', '');
});

test('navigates to the last page when an added row creates a second page', function () {
    $fullPageRows = array_fill(0, 50, ['French text.', 'Russian text.']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', $fullPageRows)
        ->set('hasCsvLoaded', true)
        ->call('addRow')
        ->assertSet('paginators.page', 2);
});

test('deletes a row by its index and re-indexes remaining rows', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->call('deleteRow', 0)
        ->assertCount('csvRows', 1)
        ->assertSet('csvRows.0.0', 'Je suis développeur web.');
});

test('clamps the current page after deleting the only row on the last page', function () {
    $rows = array_fill(0, 51, ['French text.', 'Russian text.']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', $rows)
        ->set('hasCsvLoaded', true)
        ->call('setPage', 2)
        ->call('deleteRow', 50)
        ->assertSet('paginators.page', 1);
});

test('row needs stress correction resets correctly after a row is deleted and indices shift', function () {
    // Two rows: first has no accent (needs correction), second has accent (does not).
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [
            ['Je travaille.', 'Я работаю.'],
            ['Je reste.', 'Я ост<b>а</b>юсь.'],
        ])
        ->set('hasCsvLoaded', true)
        ->call('deleteRow', 0);

    // After deleting row 0, the former row 1 becomes row 0 — it has an accent.
    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
});

// ── Russian Accent Mode ─────────────────────────────────────────────────────
test('accent mode is active by default and the edit pencil button is visible for rows with russian text', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertSee(__('csv_editor.edit_russian_text'));
});

test('accent mode pencil button is rendered when the russian column is empty', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', '']])
        ->set('hasCsvLoaded', true)
        ->assertSee(__('csv_editor.edit_russian_text'));
});

test('source text pencil button is always rendered for editing the left column', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertSee(__('csv_editor.edit_source_text'));
});

test('retranslate button is rendered when both source and russian text exist', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', sampleRows())
        ->set('hasCsvLoaded', true)
        ->assertSee(__('csv_editor.retranslate_with_chatgpt'));
});

test('retranslate button is not rendered when russian column is empty', function () {
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', '']])
        ->set('hasCsvLoaded', true)
        ->assertDontSee(__('csv_editor.retranslate_with_chatgpt'));
});

// ── Accent Placement ────────────────────────────────────────────────────────
test('places accent on the correct russian vowel', function () {
    // Plain text: "работаю" → р(0) а(1) б(2) о(3) т(4) а(5) ю(6)
    // Clicking position 3 ('о') should produce 'раб<b>о</b>таю'.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'работаю']])
        ->set('hasCsvLoaded', true)
        ->call('placeAccentOnVowel', 0, 1, 3)
        ->assertSet('csvRows.0.1', 'раб<b>о</b>таю');
});

test('moves the accent from one vowel to another within the same word', function () {
    // Start with accent on 'о' (position 3); move it to 'а' (position 5).
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'раб<b>о</b>таю']])
        ->set('hasCsvLoaded', true)
        ->call('placeAccentOnVowel', 0, 1, 5)
        ->assertSet('csvRows.0.1', 'работ<b>а</b>ю');
});

test('does not alter text when a non-vowel character position is clicked', function () {
    // Position 2 = 'б' (a consonant) — text must remain unchanged.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'работаю']])
        ->set('hasCsvLoaded', true)
        ->call('placeAccentOnVowel', 0, 1, 2)
        ->assertSet('csvRows.0.1', 'работаю');
});

test('preserves accent marks on other words when accenting a vowel in one word', function () {
    // Plain text: "работаю из дома"
    // р(0)а(1)б(2)о(3)т(4)а(5)ю(6) (7)и(8)з(9) (10)д(11)о(12)м(13)а(14)
    // Accent 'о' at position 3 in "работаю"; "дома" keeps its existing accent.
    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'работаю из д<b>о</b>ма']])
        ->set('hasCsvLoaded', true)
        ->call('placeAccentOnVowel', 0, 1, 3)
        ->assertSet('csvRows.0.1', 'раб<b>о</b>таю из д<b>о</b>ма');
});

test('single-syllable words are never flagged as needing a stress mark', function () {
    // "я из" — "я" (1 vowel) and "из" (1 vowel) are both single-syllable; neither needs an accent.
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Phrase.', 'я из']])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
});

test('single-syllable words mixed with multi-syllable words only flag the multi-syllable word', function () {
    // "он работает" — "он" (1 vowel, single-syllable) should not affect the flag;
    // "работает" (4 vowels, no accent) should trigger the flag.
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Phrase.', 'он работает']])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeTrue();
});

test('two-vowel word without a stress mark is flagged as needing correction', function () {
    // "яма" has 2 vowels and no accent — the ≥2-vowel rule means it should be flagged.
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Phrase.', 'яма']])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeTrue();
});

test('two-vowel word with a stress mark is not flagged as needing correction', function () {
    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Phrase.', 'ям<b>а</b>']])
        ->set('hasCsvLoaded', true);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
});
