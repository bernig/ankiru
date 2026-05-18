<?php

use App\Models\CreditPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

function buildStripeSignature(string $payload, string $secret): string
{
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return "t={$timestamp},v1={$signature}";
}

function stripeWebhookPayload(string $sessionId, int $purchaseId, int $userId, string $type = 'checkout.session.completed'): string
{
    return json_encode([
        'type' => $type,
        'data' => [
            'object' => [
                'id' => $sessionId,
                'payment_intent' => 'pi_test_xxx',
                'metadata' => [
                    'purchase_id' => $purchaseId,
                    'user_id' => $userId,
                ],
            ],
        ],
    ]);
}

beforeEach(function (): void {
    Config::set('services.stripe.webhook_secret', 'whsec_test_secret');
});

// ── checkout.session.completed ───────────────────────────────────────────────

test('crédite le compte utilisateur sur checkout.session.completed', function (): void {
    $user = User::factory()->create(['credits' => 0]);
    $purchase = CreditPurchase::factory()->create([
        'user_id' => $user->id,
        'credits' => 2_200_000,
        'status' => 'pending',
        'stripe_session_id' => 'cs_test_abc123',
    ]);

    $payload = stripeWebhookPayload('cs_test_abc123', $purchase->id, $user->id);

    $this->postJson(route('stripe.webhook'), json_decode($payload, true), [
        'Stripe-Signature' => buildStripeSignature($payload, 'whsec_test_secret'),
    ])->assertOk();

    expect($user->fresh()->credits)->toBe(2_200_000);
    expect($purchase->fresh()->status)->toBe('completed');
    expect($purchase->fresh()->credited_at)->not->toBeNull();
    expect($purchase->fresh()->stripe_payment_intent_id)->toBe('pi_test_xxx');
});

test('est idempotent : ne crédite pas deux fois le même achat', function (): void {
    $user = User::factory()->create(['credits' => 0]);
    $purchase = CreditPurchase::factory()->completed()->create([
        'user_id' => $user->id,
        'credits' => 1_000_000,
        'stripe_session_id' => 'cs_test_idempotent',
    ]);

    $payload = stripeWebhookPayload('cs_test_idempotent', $purchase->id, $user->id);

    $this->postJson(route('stripe.webhook'), json_decode($payload, true), [
        'Stripe-Signature' => buildStripeSignature($payload, 'whsec_test_secret'),
    ])->assertOk();

    expect($user->fresh()->credits)->toBe(0);
});

// ── Validation signature ─────────────────────────────────────────────────────

test('retourne 400 si la signature Stripe est invalide', function (): void {
    $this->postJson(route('stripe.webhook'), ['type' => 'checkout.session.completed'], [
        'Stripe-Signature' => 't=123,v1=invalide',
    ])->assertStatus(400);
});

test('retourne 200 pour les événements non gérés', function (): void {
    $payload = json_encode(['type' => 'payment_intent.created', 'data' => ['object' => []]]);

    $this->postJson(route('stripe.webhook'), json_decode($payload, true), [
        'Stripe-Signature' => buildStripeSignature($payload, 'whsec_test_secret'),
    ])->assertOk();
});

// ── Cas d'erreur ─────────────────────────────────────────────────────────────

test('retourne 200 si purchase_id est manquant dans metadata', function (): void {
    $payload = json_encode([
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_test_no_meta',
                'metadata' => [],
            ],
        ],
    ]);

    $this->postJson(route('stripe.webhook'), json_decode($payload, true), [
        'Stripe-Signature' => buildStripeSignature($payload, 'whsec_test_secret'),
    ])->assertOk();
});
