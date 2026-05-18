<?php

use App\Ai\Agents\SourceToRussianTranslatorAgent;
use App\Livewire\CsvEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ── apiKeyMissing — comportement selon le mode ────────────────────────────────

test('dispatch open-openai-key-setup si ni clé ni crédits', function (): void {
    $user = User::factory()->create(['credits' => 0]);
    $this->actingAs($user);

    SourceToRussianTranslatorAgent::fake()->preventStrayPrompts();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertDispatched('open-openai-key-setup')
        ->assertNotDispatched('open-credits-shop');

    SourceToRussianTranslatorAgent::assertNeverPrompted();
});

// ── Mode clé personnelle (sans crédits) — pas de déduction ───────────────────

test('autorise la traduction avec clé personnelle (sans crédits), ne déduit pas', function (): void {
    $user = User::factory()->create(['openai_api_key' => 'sk-test', 'credits' => 0]);
    $this->actingAs($user);

    SourceToRussianTranslatorAgent::fake(['Бонж<b>у</b>р']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', '')
        ->assertSet('csvRows.0.1', 'Бонж<b>у</b>р');

    expect($user->fresh()->credits)->toBe(0);
});

// ── Crédits prioritaires (même avec clé personnelle) ─────────────────────────

test('les crédits ont la priorité sur la clé personnelle', function (): void {
    // Utilisateur avec clé ET crédits → crédits utilisés en priorité
    $user = User::factory()->create(['openai_api_key' => 'sk-test', 'credits' => 100_000]);
    $this->actingAs($user);

    SourceToRussianTranslatorAgent::fake(['Бонж<b>у</b>р']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', '');

    // usesPlatformCredits() = true (credits > 0) → déduction tentée
    expect($user->fresh()->credits)->toBeLessThanOrEqual(100_000);
});

// ── Mode crédits — déduction post-opération ───────────────────────────────────

test('autorise la traduction avec crédits suffisants sans bloquer l\'opération', function (): void {
    $user = User::factory()->create(['credits' => 100_000]);
    $this->actingAs($user);

    SourceToRussianTranslatorAgent::fake(['Бонж<b>у</b>р']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', '')
        ->assertSet('csvRows.0.1', 'Бонж<b>у</b>р');

    expect($user->fresh()->credits)->toBeLessThanOrEqual(100_000);
});

test('bloque la traduction si crédits insuffisants', function (): void {
    // Solde trop bas (100 < 550 crédits estimés)
    $user = User::factory()->create(['credits' => 100]);
    $this->actingAs($user);

    SourceToRussianTranslatorAgent::fake()->preventStrayPrompts();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Bonjour', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', __('csv_editor.error_insufficient_credits'))
        ->assertDispatched('open-credits-shop');

    SourceToRussianTranslatorAgent::assertNeverPrompted();
    expect($user->fresh()->credits)->toBe(100);
});

// ── User model — usesPlatformCredits ─────────────────────────────────────────

test('usesPlatformCredits : true dès que credits > 0, même avec clé personnelle', function (): void {
    $user = User::factory()->create(['openai_api_key' => 'sk-test', 'credits' => 1]);

    expect($user->usesPlatformCredits())->toBeTrue();
});

test('usesPlatformCredits : false quand credits = 0 même sans clé', function (): void {
    $user = User::factory()->create(['credits' => 0]);

    expect($user->usesPlatformCredits())->toBeFalse();
});
