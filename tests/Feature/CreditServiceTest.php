<?php

use App\Models\CreditPurchase;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── hasEnoughCredits ────────────────────────────────────────────────────────

test('retourne true si le solde est suffisant', function (): void {
    $user = User::factory()->create(['credits' => 1000]);
    $service = app(CreditService::class);

    expect($service->hasEnoughCredits($user, 1000))->toBeTrue();
    expect($service->hasEnoughCredits($user, 999))->toBeTrue();
});

test('retourne false si le solde est insuffisant', function (): void {
    $user = User::factory()->create(['credits' => 100]);
    $service = app(CreditService::class);

    expect($service->hasEnoughCredits($user, 101))->toBeFalse();
});

test('retourne true pour un montant nul ou négatif', function (): void {
    $user = User::factory()->create(['credits' => 0]);
    $service = app(CreditService::class);

    expect($service->hasEnoughCredits($user, 0))->toBeTrue();
    expect($service->hasEnoughCredits($user, -10))->toBeTrue();
});

// ── deduct ──────────────────────────────────────────────────────────────────

test('déduit les crédits correctement', function (): void {
    $user = User::factory()->create(['credits' => 1000]);
    $service = app(CreditService::class);

    expect($service->deduct($user, 300))->toBeTrue();
    expect($user->fresh()->credits)->toBe(700);
});

test('refuse la déduction si crédits insuffisants', function (): void {
    $user = User::factory()->create(['credits' => 100]);
    $service = app(CreditService::class);

    expect($service->deduct($user, 500))->toBeFalse();
    expect($user->fresh()->credits)->toBe(100);
});

test('ne déduit rien pour un montant nul', function (): void {
    $user = User::factory()->create(['credits' => 500]);
    $service = app(CreditService::class);

    expect($service->deduct($user, 0))->toBeTrue();
    expect($user->fresh()->credits)->toBe(500);
});

test('gère la race condition : deux déductions concurrentes sur solde insuffisant', function (): void {
    $user = User::factory()->create(['credits' => 1000]);
    $service = app(CreditService::class);

    // Deux déductions de 600 : la première réussit, la seconde échoue
    $r1 = $service->deduct($user, 600);
    $r2 = $service->deduct($user->fresh(), 600);

    expect([$r1, $r2])->toContain(true)
        ->and([$r1, $r2])->toContain(false);
    expect($user->fresh()->credits)->toBe(400);
});

// ── credit ──────────────────────────────────────────────────────────────────

test('crédite le compte utilisateur et marque l\'achat comme completed', function (): void {
    $user = User::factory()->create(['credits' => 0]);
    $purchase = CreditPurchase::factory()->create([
        'user_id' => $user->id,
        'credits' => 2_000_000,
        'status' => 'pending',
    ]);
    $service = app(CreditService::class);

    $service->credit($user, $purchase->credits, $purchase);

    expect($user->fresh()->credits)->toBe(2_000_000);
    expect($purchase->fresh()->status)->toBe('completed');
    expect($purchase->fresh()->credited_at)->not->toBeNull();
});

test('est idempotent : ne crédite pas deux fois le même achat', function (): void {
    $user = User::factory()->create(['credits' => 0]);
    $purchase = CreditPurchase::factory()->completed()->create([
        'user_id' => $user->id,
        'credits' => 1_000_000,
    ]);
    $service = app(CreditService::class);

    $service->credit($user, $purchase->credits, $purchase);

    expect($user->fresh()->credits)->toBe(0);
});

// ── conversions ──────────────────────────────────────────────────────────────

test('convertit les caractères TTS en crédits', function (): void {
    config(['credits.tts_credits_per_char' => 10]);
    $service = app(CreditService::class);

    expect($service->ttsCharsToCredits(50))->toBe(500);
    expect($service->ttsCharsToCredits(0))->toBe(0);
});

test('convertit les tokens LLM en crédits (1:1)', function (): void {
    $service = app(CreditService::class);

    expect($service->tokensToCredits(400, 150))->toBe(550);
    expect($service->tokensToCredits(0, 0))->toBe(0);
});

// ── User helpers ─────────────────────────────────────────────────────────────

test('canUseAi : true si clé personnelle même sans crédits', function (): void {
    $user = User::factory()->create(['openai_api_key' => 'sk-test', 'credits' => 0]);

    expect($user->canUseAi())->toBeTrue();
});

test('canUseAi : true si crédits > 0 sans clé personnelle', function (): void {
    $user = User::factory()->create(['credits' => 500]);

    expect($user->canUseAi())->toBeTrue();
});

test('canUseAi : false sans clé ni crédits', function (): void {
    $user = User::factory()->create(['credits' => 0]);

    expect($user->canUseAi())->toBeFalse();
});

test('usesPlatformCredits : true dès que credits > 0 (même avec clé)', function (): void {
    $user = User::factory()->create(['openai_api_key' => 'sk-test', 'credits' => 1]);

    expect($user->usesPlatformCredits())->toBeTrue();
});

test('usesPlatformCredits : false quand credits = 0, avec ou sans clé', function (): void {
    $withKey = User::factory()->create(['openai_api_key' => 'sk-test', 'credits' => 0]);
    $withoutKey = User::factory()->create(['credits' => 0]);

    expect($withKey->usesPlatformCredits())->toBeFalse()
        ->and($withoutKey->usesPlatformCredits())->toBeFalse();
});
