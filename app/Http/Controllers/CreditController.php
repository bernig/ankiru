<?php

namespace App\Http\Controllers;

use App\Models\CreditPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\StripeClient;

class CreditController extends Controller
{
    public function __construct(
        private readonly StripeClient $stripe,
    ) {}

    public function index(): View
    {
        $user = auth()->user();

        return view('credits.index', [
            'balance' => $user->credits,
            'purchases' => $user->creditPurchases()->limit(5)->get(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:'.config('credits.min_quantity'), 'max:'.config('credits.max_quantity')],
        ]);

        $quantity = (int) $request->quantity;
        $user = $request->user();

        $totalCredits = $quantity * config('credits.unit_credits');
        $totalCents = $this->calculateTotalCents($quantity);

        $purchase = CreditPurchase::create([
            'user_id' => $user->id,
            'pack_slug' => "qty:{$quantity}",
            'credits' => $totalCredits,
            'amount_cents' => $totalCents,
            'currency' => 'eur',
            'stripe_session_id' => 'pending_'.uniqid(),
            'status' => 'pending',
        ]);

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $totalCents,
                    'product_data' => [
                        'name' => 'Anki AI — '.number_format($totalCredits / 1000, 0, '.', ' ').'k crédits',
                        'description' => $this->buildFeeDescription($quantity),
                    ],
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('credits.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('credits.cancel'),
            'metadata' => [
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
            ],
            'customer_email' => $user->email,
        ]);

        $purchase->update(['stripe_session_id' => $session->id]);

        return redirect($session->url);
    }

    public function success(): View
    {
        return view('credits.success');
    }

    public function cancel(): View
    {
        return view('credits.cancel');
    }

    private function calculateTotalCents(int $quantity): int
    {
        $baseCents = $quantity * config('credits.unit_price_cents');
        $totalFeeCents = 0;

        foreach (config('credits.fees', []) as $fee) {
            $totalFeeCents += (int) round($baseCents * ($fee['percent'] ?? 0) / 100) + ($fee['fixed_cents'] ?? 0);
        }

        return $baseCents + $totalFeeCents;
    }

    private function buildFeeDescription(int $quantity): string
    {
        $baseCents = $quantity * config('credits.unit_price_cents');
        $lines = [number_format($baseCents / 100, 2, ',', ' ').' € (base)'];

        foreach (config('credits.fees', []) as $fee) {
            $feeCents = (int) round($baseCents * ($fee['percent'] ?? 0) / 100) + ($fee['fixed_cents'] ?? 0);
            $lines[] = $fee['label'].': +'.number_format($feeCents / 100, 2, ',', ' ').' €';
        }

        return implode(' · ', $lines);
    }
}
