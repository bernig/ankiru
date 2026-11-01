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

/**
 * @return array<int, array<int, string>>
 */
function numberedRows(int $count): array
{
    return array_map(fn (int $i): array => ["Phrase {$i}", "Фраза {$i}"], range(0, $count - 1));
}

test('keeps the deck out of the snapshot and sends only the visible page', function (): void {
    CsvDraft::factory()->create(['user_id' => $this->user->id, 'csv_rows' => numberedRows(120)]);

    $component = Livewire::test(CsvEditor::class)
        ->assertSnapshotSetStrict('csvRows', null)
        ->assertSnapshotSet('pageRows', fn (array $rows): bool => array_keys($rows) === range(0, 49));

    expect(json_encode($component->snapshot))->not->toContain('Phrase 119');

    $component->call('setPage', 2)
        ->assertSnapshotSet('pageRows', fn (array $rows): bool => array_keys($rows) === range(50, 99))
        ->assertSnapshotSet('pageRows.50.0', 'Phrase 50');
});

test('keeps the absolute row index in the page sent to the client when filtering', function (): void {
    CsvDraft::factory()->create(['user_id' => $this->user->id, 'csv_rows' => numberedRows(5)]);

    Livewire::test(CsvEditor::class)
        ->set('searchQuery', 'Phrase 3')
        ->assertSet('pageRows', [3 => ['Phrase 3', 'Фраза 3']]);
});

test('reloads the rows from the draft on every request', function (): void {
    $draft = CsvDraft::factory()->create(['user_id' => $this->user->id, 'csv_rows' => numberedRows(2)]);

    $component = Livewire::test(CsvEditor::class);

    // Another tab, or a finished batch, changes row 1 in the meantime.
    $draft->update(['csv_rows' => [['Phrase 0', 'Фраза 0'], ['Changed elsewhere', 'Изменено']]]);

    $component->call('updateCell', 0, 0, 'Edited here');

    expect($draft->fresh()->csv_rows)->toBe([
        ['Edited here', 'Фраза 0'],
        ['Changed elsewhere', 'Изменено'],
    ]);
});

test('sends the test cards to the client only while test mode is open', function (): void {
    CsvDraft::factory()->create([
        'user_id' => $this->user->id,
        'csv_rows' => [['Bonjour', 'Привет'], ['Sans traduction', ''], ['Merci', 'Спасибо']],
    ]);

    $component = Livewire::test(CsvEditor::class)
        ->call('openTestMode');

    $testCards = $component->get('testCards');
    ksort($testCards);

    expect($testCards)->toBe([0 => ['Bonjour', 'Привет'], 2 => ['Merci', 'Спасибо']]);

    $component->call('closeTestMode')
        ->assertSet('testCards', []);
});

test('shows an accent placed from test mode on the test card', function (): void {
    CsvDraft::factory()->create(['user_id' => $this->user->id, 'csv_rows' => [['Travailler', 'работать']]]);

    Livewire::test(CsvEditor::class)
        ->call('openTestMode')
        ->call('placeAccentOnVowel', 0, 1, 3)
        ->assertSet('testCards.0.1', 'раб<b>о</b>тать');
});
