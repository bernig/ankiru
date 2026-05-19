<div class="flex flex-1 flex-col space-y-5">

    {{-- Current balance --}}
    <div class="flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50 px-5 py-4 dark:border-blue-900 dark:bg-blue-950/40">
        <div>
            <flux:text class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('credits.your_balance') }}</flux:text>
            <flux:text class="mt-0.5 text-xs text-blue-500 dark:text-blue-500">{{ __('credits.balance_description_short') }}</flux:text>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold tabular-nums text-blue-700 dark:text-blue-300">
                {{ number_format($balance, 0, ',', ' ') }}
            </div>
            <div class="text-xs text-blue-500">{{ __('credits.credits') }}</div>
        </div>
    </div>

    {{-- Purchase configurator --}}
    <flux:card class="space-y-5">

        {{-- Quantity selector --}}
        <div class="space-y-3">
            <flux:text class="text-sm font-medium">{{ __('credits.configure_purchase') }}</flux:text>

            <div class="flex items-center gap-3">
                <flux:button class="rounded-full!" square size="sm" variant="ghost" icon="minus" wire:click="decrement" :disabled="$this->quantity <= $minQuantity" />
                <div class="flex min-w-0 flex-1 flex-col items-center">
                    <span class="text-3xl font-bold tabular-nums leading-none">{{ number_format($this->priceBreakdown['total_credits'] / 1000, 0, ',', ' ') }}k</span>
                    <span class="mt-1 text-xs text-zinc-400">{{ __('credits.credits') }}</span>
                </div>
                <flux:button class="rounded-full!" square size="sm" variant="ghost" icon="plus" wire:click="increment" :disabled="$this->quantity >= $maxQuantity" />
            </div>

            {{-- Progress bar --}}
            <div class="relative h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                <div class="h-full rounded-full bg-blue-400 transition-all duration-200" style="width: {{ round((($this->quantity - $minQuantity) / max(1, $maxQuantity - $minQuantity)) * 100) }}%"></div>
            </div>
            <div class="flex justify-between text-xs text-zinc-400">
                <span>{{ number_format(($minQuantity * $unitCredits) / 1000, 0) }}k</span>
                <span>{{ number_format(($maxQuantity * $unitCredits) / 1000, 0) }}k</span>
            </div>
        </div>

        <flux:separator />

        {{-- Price breakdown --}}
        <div class="space-y-1.5">
            <div class="flex items-center justify-between text-sm">
                <span class="text-zinc-600 dark:text-zinc-400">{{ __('credits.base_price') }}</span>
                <span class="font-medium tabular-nums">{{ number_format($this->priceBreakdown['base_cents'] / 100, 2, ',', ' ') }} €</span>
            </div>

            @foreach ($this->priceBreakdown['fees'] as $fee)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-400 dark:text-zinc-500">{{ $fee['label'] }}</span>
                    <span class="tabular-nums text-zinc-400 dark:text-zinc-500">+ {{ number_format($fee['cents'] / 100, 2, ',', ' ') }} €</span>
                </div>
            @endforeach

            <div class="mt-2 flex items-center justify-between border-t border-zinc-100 pt-2 dark:border-zinc-800">
                <span class="font-semibold">{{ __('credits.total') }}</span>
                <span class="text-xl font-bold tabular-nums">{{ number_format($this->priceBreakdown['total_cents'] / 100, 2, ',', ' ') }} €</span>
            </div>
        </div>

        {{-- Buy button (disabled — coming soon) --}}
        <flux:tooltip :content="__('credits.coming_soon_tooltip')">
            <flux:button class="rounded-full! w-full" disabled variant="primary" icon="clock">
                {{ __('credits.coming_soon_button') }}
            </flux:button>
        </flux:tooltip>

        <flux:text class="text-center text-xs text-zinc-400">{{ __('credits.pricing_transparent') }}</flux:text>
    </flux:card>

    {{-- Purchase history --}}
    @if ($purchases->isNotEmpty())
        <flux:card class="space-y-3">
            <flux:heading size="sm">{{ __('credits.purchase_history') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('credits.date') }}</flux:table.column>
                    <flux:table.column>{{ __('credits.amount') }}</flux:table.column>
                    <flux:table.column>{{ __('credits.price') }}</flux:table.column>
                    <flux:table.column>{{ __('credits.status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($purchases as $purchase)
                        <flux:table.row :key="$purchase->id">
                            <flux:table.cell>{{ $purchase->created_at->translatedFormat('d M Y') }}</flux:table.cell>
                            <flux:table.cell class="tabular-nums">{{ number_format($purchase->credits / 1000, 0, ',', ' ') }}k</flux:table.cell>
                            <flux:table.cell class="tabular-nums">{{ number_format($purchase->amount_cents / 100, 2, ',', ' ') }} €</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $purchase->isCompleted() ? 'green' : 'yellow' }}" size="sm">
                                    {{ __('credits.status_' . $purchase->status) }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- Credits-take-priority notice (shown only when user also has a personal key) --}}
    @if ($hasPersonalApiKey)
        <flux:callout color="green" icon="information-circle" size="sm">
            <flux:callout.text>{{ __('credits.has_key_description') }}</flux:callout.text>
        </flux:callout>
    @endif

</div>
