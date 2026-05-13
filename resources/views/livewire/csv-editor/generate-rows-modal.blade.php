@php
    $cost = $this->generationCostEstimate;
@endphp

<flux:modal class="md:w-2xl" name="generate-rows" scroll="body" x-on:open-generate-rows-modal.window="$flux.modal('generate-rows').show()" x-on:close="$wire.generateRowsReset()">
    <div class="flex flex-col gap-5">

        @if ($generatedRows !== [])

            {{-- ── Rapport de génération ── --}}
            <div class="flex items-center gap-2">
                <flux:icon.check-circle class="size-6 shrink-0 text-green-500" />
                <flux:heading size="lg">{{ trans_choice('csv_editor.generate_rows_success_title', count($generatedRows), ['count' => count($generatedRows)]) }}</flux:heading>
            </div>

            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                @foreach ($generatedRows as $pair)
                    <div class="flex items-start gap-3 px-4 py-2.5 text-sm odd:bg-white even:bg-zinc-50 dark:odd:bg-zinc-800/40 dark:even:bg-zinc-800/20">
                        <span class="min-w-0 flex-1 text-zinc-600 dark:text-zinc-400">{{ $pair['source'] }}</span>
                        <flux:icon.arrow-right class="mt-0.5 size-4 shrink-0 text-zinc-300 dark:text-zinc-600" />
                        <span class="min-w-0 flex-1 font-medium text-zinc-900 dark:text-zinc-100">{!! strip_tags($pair['russian'], '<b>') !!}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between">
                <flux:button icon="sparkles" icon:variant="outline" wire:click="generateRowsReset">
                    {{ __('csv_editor.generate_rows_generate_more') }}
                </flux:button>
                <flux:modal.close>
                    <flux:button variant="primary" icon="check">{{ __('csv_editor.close') }}</flux:button>
                </flux:modal.close>
            </div>
        @else
            {{-- ── Formulaire de génération ── --}}
            <div>
                <flux:heading size="lg">{{ __('csv_editor.generate_rows_title') }}</flux:heading>
                <flux:text class="mt-1">{{ __('csv_editor.generate_rows_description') }}</flux:text>
            </div>

            {{-- Contexte personnel --}}
            <flux:field>
                <flux:label class="flex items-center gap-2">
                    {{ __('csv_editor.generate_rows_context_label') }}
                    <flux:tooltip toggleable :content="__('csv_editor.generate_rows_context_tooltip')">
                        <flux:button class="text-zinc-400!" icon="information-circle" variant="ghost" size="xs" />
                    </flux:tooltip>
                </flux:label>
                <flux:description>
                    {!! __('csv_editor.generate_rows_context_hint', ['url' => route('profile')]) !!}
                </flux:description>
                <flux:textarea wire:model.live.debounce.600ms="generateRowsContext" rows="3" :placeholder="__('csv_editor.generate_rows_context_placeholder')" />
            </flux:field>

            {{-- Nombre de lignes --}}
            <flux:input class="w-28" type="number" description="{{ __('csv_editor.generate_rows_count_hint') }}" label="{{ __('csv_editor.generate_rows_count_label') }}" wire:model.live="generateRowsCount" min="1" max="50" />

            {{-- Partager les lignes existantes --}}
            <div class="flex flex-col gap-2">
                <flux:switch wire:model.live="generateRowsIncludeExisting" :label="__('csv_editor.generate_rows_include_existing')" align="left" />

                @if ($generateRowsIncludeExisting)
                    @if ($cost['existingCount'] === 0)
                        <flux:text class="text-xs text-zinc-400">
                            {{ __('csv_editor.generate_rows_include_existing_empty') }}
                        </flux:text>
                    @elseif ($cost['willSendActualRows'])
                        <flux:text class="text-xs text-zinc-500">
                            {{ __('csv_editor.generate_rows_include_existing_sends_rows', [
                                'count' => $cost['existingCount'],
                                'tokens' => number_format($cost['existingTokens']),
                            ]) }}
                        </flux:text>
                    @else
                        <flux:text class="text-xs text-amber-600 dark:text-amber-400">
                            {{ __('csv_editor.generate_rows_include_existing_count_only', [
                                'count' => $cost['existingCount'],
                            ]) }}
                        </flux:text>
                    @endif
                @endif
            </div>

            {{-- Composer / invite --}}
            <flux:field>
                <flux:composer label="{{ __('csv_editor.generate_rows_prompt_label') }}" wire:model.live.debounce.800ms="generateRowsPrompt" :placeholder="__('csv_editor.generate_rows_prompt_placeholder')" x-on:submit="$wire.generateRows()">
                    <x-slot name="actionsTrailing">
                        <flux:button size="sm" variant="primary" icon="sparkles" wire:click="generateRows" wire:loading.attr="disabled" wire:target="generateRows">
                            <flux:icon.loading class="size-4" wire:loading wire:target="generateRows" />
                            <span wire:loading.remove wire:target="generateRows">{{ __('csv_editor.generate_rows_submit') }}</span>
                        </flux:button>
                    </x-slot>
                </flux:composer>
            </flux:field>

            {{-- Indicateur de coût --}}
            <div class="flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                <flux:icon.information-circle class="size-4 shrink-0" />
                <span>{{ __('csv_editor.generate_rows_cost_hint', [
                    'cost' => '$' . number_format($cost['totalCost'], 3),
                    'tokens_in' => number_format($cost['inputTokens']),
                    'tokens_out' => number_format($cost['outputTokens']),
                ]) }}</span>
            </div>

            {{-- Erreur --}}
            @if ($translationError)
                <div class="flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
                    <flux:icon.exclamation-triangle class="size-4 shrink-0" />
                    <span>{{ $translationError }}</span>
                </div>
            @endif

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('csv_editor.close') }}</flux:button>
                </flux:modal.close>
            </div>

        @endif

    </div>
</flux:modal>
