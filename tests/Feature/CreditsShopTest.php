<?php

use App\Livewire\Credits\Shop;
use App\Models\CreditPurchase;
use App\Models\User;
use Livewire\Livewire;
use Stripe\StripeClient;

beforeEach(function (): void {
    config([
        'services.stripe.secret' => 'sk_test_fake',
        'credits.unit_credits' => 100_000,
        'credits.unit_price_cents' => 100,
        'credits.min_quantity' => 1,
        'credits.max_quantity' => 10,
        'credits.fees' => [
            ['label' => 'Stripe', 'percent' => 2.9, 'fixed_cents' => 30],
            ['label' => 'Hosting', 'percent' => 5.0, 'fixed_cents' => 0],
        ],
    ]);

    $this->actingAs(User::factory()->create(['credits' => 0]));
});

test('quantity cannot go below the minimum or above the maximum', function (): void {
    $component = Livewire::test(Shop::class)
        ->call('decrement')
        ->assertSet('quantity', 1);

    foreach (range(1, 12) as $click) {
        $component->call('increment');
    }

    $component->assertSet('quantity', 10);
});

test('price breakdown adds each fee to the base price', function (int $quantity, int $baseCents, array $feeCents, int $totalCents): void {
    $breakdown = Livewire::test(Shop::class)
        ->set('quantity', $quantity)
        ->instance()
        ->priceBreakdown();

    expect($breakdown)->toBe([
        'base_cents' => $baseCents,
        'fees' => [
            ['label' => 'Stripe', 'cents' => $feeCents[0]],
            ['label' => 'Hosting', 'cents' => $feeCents[1]],
        ],
        'total_cents' => $totalCents,
        'total_credits' => $quantity * 100_000,
    ]);
})->with([
    'one unit' => [1, 100, [33, 5], 138],
    'half-cent fee rounds up' => [5, 500, [45, 25], 570],
    'several units' => [7, 700, [50, 35], 785],
]);

test('price breakdown clamps a tampered quantity to the configured bounds', function (): void {
    $breakdown = Livewire::test(Shop::class)
        ->set('quantity', 999)
        ->instance()
        ->priceBreakdown();

    expect($breakdown['base_cents'])->toBe(1_000)
        ->and($breakdown['total_credits'])->toBe(1_000_000);
});

test('the displayed total is the amount charged by Stripe checkout', function (int $quantity): void {
    $displayedTotalCents = Livewire::test(Shop::class)
        ->set('quantity', $quantity)
        ->instance()
        ->priceBreakdown()['total_cents'];

    $stripeMock = Mockery::mock(StripeClient::class);
    $stripeMock->checkout = Mockery::mock();
    $stripeMock->checkout->sessions = Mockery::mock();
    $stripeMock->checkout->sessions
        ->shouldReceive('create')
        ->once()
        ->withArgs(fn (array $params) => $params['line_items'][0]['price_data']['unit_amount'] === $displayedTotalCents)
        ->andReturn((object) ['id' => 'cs_test_parity', 'url' => 'https://checkout.stripe.com/pay/cs_test_parity']);
    app()->instance(StripeClient::class, $stripeMock);

    $this->post(route('credits.checkout'), ['quantity' => $quantity])->assertRedirect();

    expect(CreditPurchase::sole()->amount_cents)->toBe($displayedTotalCents);
})->with([1, 5, 7, 10]);

test('renders the balance and the recent purchases', function (): void {
    $user = User::factory()->create(['credits' => 250_000]);
    CreditPurchase::factory()->for($user)->create(['credits' => 300_000, 'amount_cents' => 412]);

    $this->actingAs($user);

    Livewire::test(Shop::class)
        ->assertSee('250 000')
        ->assertSee('4,12 €');
});
