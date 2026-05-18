<?php

namespace App\Livewire\Credits;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Shop extends Component
{
    public int $quantity = 1;

    public function increment(): void
    {
        $this->quantity = min(config('credits.max_quantity', 50), $this->quantity + 1);
    }

    public function decrement(): void
    {
        $this->quantity = max(config('credits.min_quantity', 1), $this->quantity - 1);
    }

    /**
     * Price breakdown: base cost + each fee + total.
     *
     * @return array{base_cents: int, fees: list<array{label: string, cents: int}>, total_cents: int, total_credits: int}
     */
    #[Computed]
    public function priceBreakdown(): array
    {
        $quantity = max(config('credits.min_quantity', 1), min($this->quantity, config('credits.max_quantity', 50)));
        $baseCents = $quantity * config('credits.unit_price_cents');
        $totalCredits = $quantity * config('credits.unit_credits');

        $feeLines = [];
        $totalFeeCents = 0;

        foreach (config('credits.fees', []) as $fee) {
            $feeCents = (int) round($baseCents * ($fee['percent'] ?? 0) / 100) + ($fee['fixed_cents'] ?? 0);
            $feeLines[] = [
                'label' => $fee['label'],
                'cents' => $feeCents,
            ];
            $totalFeeCents += $feeCents;
        }

        return [
            'base_cents' => $baseCents,
            'fees' => $feeLines,
            'total_cents' => $baseCents + $totalFeeCents,
            'total_credits' => $totalCredits,
        ];
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.credits.shop', [
            'balance' => $user->credits,
            'hasPersonalApiKey' => $user->hasPersonalApiKey(),
            'purchases' => $user->creditPurchases()->limit(5)->get(),
            'unitCredits' => config('credits.unit_credits'),
            'minQuantity' => config('credits.min_quantity'),
            'maxQuantity' => config('credits.max_quantity'),
        ]);
    }
}
