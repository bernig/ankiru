<?php

namespace Database\Factories;

use App\Models\CreditPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditPurchase>
 */
class CreditPurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $credits = $quantity * config('credits.unit_credits', 100_000);
        $baseCents = $quantity * config('credits.unit_price_cents', 100);
        $totalFeeCents = 0;

        foreach (config('credits.fees', []) as $fee) {
            $totalFeeCents += (int) round($baseCents * ($fee['percent'] ?? 0) / 100) + ($fee['fixed_cents'] ?? 0);
        }

        return [
            'user_id' => User::factory(),
            'pack_slug' => "qty:{$quantity}",
            'credits' => $credits,
            'amount_cents' => $baseCents + $totalFeeCents,
            'currency' => 'eur',
            'stripe_session_id' => 'cs_test_'.fake()->unique()->lexify('??????????'),
            'stripe_payment_intent_id' => null,
            'status' => 'pending',
            'credited_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'credited_at' => now(),
        ]);
    }
}
