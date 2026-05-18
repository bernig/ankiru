<?php

use App\Models\CreditPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('services.stripe.secret', 'sk_test_fake');
    Config::set('credits.unit_credits', 100_000);
    Config::set('credits.unit_price_cents', 100);
    Config::set('credits.min_quantity', 1);
    Config::set('credits.max_quantity', 50);
    Config::set('credits.fees', [
        ['label' => 'Frais Stripe', 'percent' => 2.9, 'fixed_cents' => 30],
    ]);
});

// ── GET /credits ──────────────────────────────────────────────────────────────

test('affiche la page de crédits pour un utilisateur connecté', function (): void {
    $user = User::factory()->create(['credits' => 1_500]);
    $this->actingAs($user);

    $this->get(route('credits.index'))->assertOk();
});

test('redirige vers login si non authentifié', function (): void {
    $this->get(route('credits.index'))->assertRedirect(route('login'));
});

// ── POST /credits/checkout ────────────────────────────────────────────────────

test('redirige vers Stripe checkout pour une quantité valide', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $stripeMock = Mockery::mock(StripeClient::class);
    $sessionsMock = Mockery::mock();
    $stripeMock->checkout = Mockery::mock();
    $stripeMock->checkout->sessions = $sessionsMock;
    $sessionsMock
        ->shouldReceive('create')
        ->once()
        ->andReturn((object) [
            'id' => 'cs_test_qty5',
            'url' => 'https://checkout.stripe.com/pay/cs_test_qty5',
        ]);

    app()->instance(StripeClient::class, $stripeMock);

    $this->actingAs($user)
        ->post(route('credits.checkout'), ['quantity' => 5])
        ->assertRedirect('https://checkout.stripe.com/pay/cs_test_qty5');

    expect(CreditPurchase::where('user_id', $user->id)->where('pack_slug', 'qty:5')->exists())->toBeTrue();
});

test('crée un CreditPurchase avec les bons montants (quantité 3)', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $stripeMock = Mockery::mock(StripeClient::class);
    $stripeMock->checkout = Mockery::mock();
    $stripeMock->checkout->sessions = Mockery::mock();
    $stripeMock->checkout->sessions
        ->shouldReceive('create')
        ->andReturn((object) ['id' => 'cs_test_qty3', 'url' => 'https://stripe.com']);

    app()->instance(StripeClient::class, $stripeMock);

    $this->actingAs($user)->post(route('credits.checkout'), ['quantity' => 3]);

    $purchase = CreditPurchase::where('user_id', $user->id)->first();

    // 3 × 100k = 300k crédits, base = 3 × 100 = 300 cts, frais Stripe = 300*2.9/100 + 30 = 39 cts
    expect($purchase)->not->toBeNull()
        ->and($purchase->credits)->toBe(300_000)
        ->and($purchase->amount_cents)->toBe(339)
        ->and($purchase->status)->toBe('pending');
});

test('retourne une erreur de validation pour une quantité supérieure au max', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('credits.checkout'), ['quantity' => 999])
        ->assertSessionHasErrors('quantity');
});

test('retourne une erreur de validation si la quantité est absente', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('credits.checkout'), [])
        ->assertSessionHasErrors('quantity');
});

// ── GET /credits/success & /credits/cancel ────────────────────────────────────

test('affiche la page de succès', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('credits.success'))->assertOk();
});

test("affiche la page d'annulation", function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('credits.cancel'))->assertOk();
});
