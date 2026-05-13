<?php

use App\Livewire\CsvEditor;
use App\Models\User;
use Livewire\Livewire;

// ── Accent style preferences ─────────────────────────────────────────────────
test('mount loads accent style from the authenticated user', function (): void {
    /** @var User $user */
    $user = User::factory()->create([
        'accent_color' => '#ff0000',
        'accent_bold' => false,
    ]);

    Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->assertSet('accentColor', '#ff0000')
        ->assertSet('accentBold', false);
});

test('saveAccentStyle persists color and bold to the database', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->call('saveAccentStyle', '#1d4ed8', true);

    $user->refresh();
    expect($user->accent_color)->toBe('#1d4ed8')
        ->and($user->accent_bold)->toBeTrue();
});

test('saveAccentStyle accepts null color for bold-only style', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['accent_color' => '#ff0000']);

    Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->call('saveAccentStyle', null, true);

    $user->refresh();
    expect($user->accent_color)->toBeNull()
        ->and($user->accent_bold)->toBeTrue();
});

test('saveAccentStyle ignores invalid hex color strings', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['accent_color' => '#d97706']);

    Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->call('saveAccentStyle', 'not-a-color', true);

    $user->refresh();
    expect($user->accent_color)->toBe('#d97706');
});

test('csv export wraps stressed vowels with font and bold tags when color and bold are set', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['accent_color' => '#ff0000', 'accent_bold' => true]);

    $test = Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', 'раб<b>о</b>тать']])
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'test.csv')
        ->call('downloadCsv');

    $content = base64_decode($test->effects['download']['content']);

    // fputcsv doubles quote characters inside quoted fields.
    expect($content)->toContain('<font color=""#ff0000""><b>о</b></font>');
});

test('csv export wraps stressed vowels with font tag only when color is set and bold is disabled', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['accent_color' => '#0000ff', 'accent_bold' => false]);

    $test = Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', 'раб<b>о</b>тать']])
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'test.csv')
        ->call('downloadCsv');

    $content = base64_decode($test->effects['download']['content']);

    // fputcsv doubles quote characters inside quoted fields.
    expect($content)->toContain('<font color=""#0000ff"">о</font>')
        ->and($content)->not->toContain('<b>');
});

test('saveAccentStyle persists unicode mode to the database', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['accent_unicode' => false]);

    Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->call('saveAccentStyle', null, true, true);

    $user->refresh();
    expect($user->accent_unicode)->toBeTrue();
});

test('mount loads accent_unicode from the authenticated user', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['accent_unicode' => true]);

    Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->assertSet('accentUnicode', true);
});

test('csv export replaces stressed vowels with combining acute accent in unicode mode', function (): void {
    /** @var User $user */
    $user = User::factory()->create([
        'accent_unicode' => true,
        'accent_color' => null,
        'accent_bold' => false,
    ]);

    $test = Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', 'раб<b>о</b>тать']])
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'test.csv')
        ->call('downloadCsv');

    $content = base64_decode($test->effects['download']['content']);

    // о + U+0301 combining acute accent, no HTML tags
    expect($content)->toContain("рабо\u{0301}тать")
        ->and($content)->not->toContain('<b>')
        ->and($content)->not->toContain('<font');
});

test('csv export does not add combining accent to ё in unicode mode', function (): void {
    /** @var User $user */
    $user = User::factory()->create([
        'accent_unicode' => true,
        'accent_color' => null,
        'accent_bold' => false,
    ]);

    $test = Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', 'в<b>ё</b>л']])
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'test.csv')
        ->call('downloadCsv');

    $content = base64_decode($test->effects['download']['content']);

    // ё should stay as-is: no combining accent added
    expect($content)->toContain('вёл')
        ->and($content)->not->toContain('<b>');
});

test('csv export combines unicode accent, color, and bold when all three options are active', function (): void {
    /** @var User $user */
    $user = User::factory()->create([
        'accent_unicode' => true,
        'accent_color' => '#ff0000',
        'accent_bold' => true,
    ]);

    $test = Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', 'раб<b>о</b>тать']])
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'test.csv')
        ->call('downloadCsv');

    $content = base64_decode($test->effects['download']['content']);

    // fputcsv doubles quote characters inside quoted fields.
    // о + U+0301 inside <b> inside <font color>
    expect($content)->toContain("<font color=\"\"#ff0000\"\"><b>о\u{0301}</b></font>");
});

test('csv export keeps plain bold tags when no color is set', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['accent_color' => null, 'accent_bold' => true]);

    $test = Livewire::actingAs($user)
        ->test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', 'раб<b>о</b>тать']])
        ->set('hasCsvLoaded', true)
        ->set('originalFileName', 'test.csv')
        ->call('downloadCsv');

    $content = base64_decode($test->effects['download']['content']);

    expect($content)->toContain('<b>о</b>')
        ->and($content)->not->toContain('<font');
});
