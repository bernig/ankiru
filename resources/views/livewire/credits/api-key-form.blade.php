<div class="space-y-5">

    @if ($this->hasOpenAiKey)

        {{-- Key is set --}}
        <div class="flex items-center gap-3 rounded-xl border border-green-100 bg-green-50 px-4 py-3 dark:border-green-900 dark:bg-green-950/40">
            <flux:icon class="size-5 shrink-0 text-green-500" name="check-circle" variant="solid" />
            <div class="flex min-w-0 flex-1 items-center justify-between gap-3">
                <flux:text class="text-sm font-medium text-green-700 dark:text-green-300">
                    {{ __('profile.api_key_set') }}
                </flux:text>
                <flux:button class="hover:bg-red-50! text-red-500! hover:text-red-600! shrink-0" icon="trash" icon:variant="outline" wire:click="clearApiKey" wire:confirm="{{ __('profile.api_key_clear_confirm') }}" variant="ghost" size="xs">
                    {{ __('profile.api_key_clear') }}
                </flux:button>
            </div>
        </div>

        <flux:separator />

        {{-- Usage stats --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm font-medium">{{ __('profile.api_usage_stats') }}</flux:text>
                <flux:button href="https://platform.openai.com/usage" target="_blank" size="xs" variant="ghost" icon:trailing="arrow-up-right">
                    {{ __('profile.go_to_api_usage') }}
                </flux:button>
            </div>

            @if ($this->usageStats->isEmpty())
                <flux:text class="text-sm italic text-zinc-400">{{ __('profile.api_usage_empty') }}</flux:text>
            @else
                {{-- Table view (sm and up) --}}
                <div class="hidden sm:block">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('profile.stats_operation') }}</flux:table.column>
                            <flux:table.column class="text-right">{{ __('profile.stats_calls') }}</flux:table.column>
                            <flux:table.column class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    {{ __('profile.stats_tokens_in_out') }}
                                    <flux:tooltip toggleable :content="__('profile.stats_tokens_tooltip')">
                                        <flux:button class="text-zinc-400!" icon="information-circle" variant="ghost" size="xs" />
                                    </flux:tooltip>
                                </div>
                            </flux:table.column>
                            <flux:table.column class="text-right">{{ __('profile.stats_characters') }}</flux:table.column>
                            <flux:table.column class="text-right">{{ __('profile.stats_cost') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($this->usageStats as $stat)
                                <flux:table.row>
                                    <flux:table.cell>{{ __('profile.operation_' . $stat['operation']) }}</flux:table.cell>
                                    <flux:table.cell class="text-right tabular-nums">{{ number_format($stat['calls']) }}</flux:table.cell>
                                    <flux:table.cell class="text-right tabular-nums">{{ $stat['prompt_tokens'] > 0 ? number_format($stat['prompt_tokens']) : '-' }}/{{ $stat['completion_tokens'] > 0 ? number_format($stat['completion_tokens']) : '-' }}</flux:table.cell>
                                    <flux:table.cell class="text-right tabular-nums">{{ $stat['characters'] > 0 ? number_format($stat['characters']) : '-' }}</flux:table.cell>
                                    <flux:table.cell class="text-right tabular-nums">${{ number_format($stat['estimated_cost'], 4) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                            <flux:table.row class="border-t border-zinc-200 font-semibold">
                                <flux:table.cell>{{ __('profile.stats_total') }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ number_format($this->usageStats->sum('calls')) }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ number_format($this->usageStats->sum('prompt_tokens')) }}/{{ number_format($this->usageStats->sum('completion_tokens')) }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ number_format($this->usageStats->sum('characters')) }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">${{ number_format($this->usageStats->sum('estimated_cost'), 4) }}</flux:table.cell>
                            </flux:table.row>
                        </flux:table.rows>
                    </flux:table>
                </div>

                {{-- Card view (mobile) --}}
                <div class="space-y-2 sm:hidden">
                    @foreach ($this->usageStats as $stat)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="mb-2 text-sm font-semibold">{{ __('profile.operation_' . $stat['operation']) }}</div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                <span class="text-zinc-500">{{ __('profile.stats_calls') }}</span>
                                <span class="text-right font-medium tabular-nums">{{ number_format($stat['calls']) }}</span>
                                <span class="text-zinc-500">{{ __('profile.stats_tokens_in_out') }}</span>
                                <span class="text-right font-medium tabular-nums">{{ $stat['prompt_tokens'] > 0 ? number_format($stat['prompt_tokens']) : '-' }}/{{ $stat['completion_tokens'] > 0 ? number_format($stat['completion_tokens']) : '-' }}</span>
                                <span class="text-zinc-500">{{ __('profile.stats_characters') }}</span>
                                <span class="text-right font-medium tabular-nums">{{ $stat['characters'] > 0 ? number_format($stat['characters']) : '-' }}</span>
                                <span class="text-zinc-500">{{ __('profile.stats_cost') }}</span>
                                <span class="text-right font-medium tabular-nums">${{ number_format($stat['estimated_cost'], 4) }}</span>
                            </div>
                        </div>
                    @endforeach
                    <div class="rounded-lg border border-zinc-300 bg-zinc-50 p-3 dark:border-zinc-600 dark:bg-zinc-800">
                        <div class="mb-2 text-sm font-semibold">{{ __('profile.stats_total') }}</div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                            <span class="text-zinc-500">{{ __('profile.stats_calls') }}</span>
                            <span class="text-right font-semibold tabular-nums">{{ number_format($this->usageStats->sum('calls')) }}</span>
                            <span class="text-zinc-500">{{ __('profile.stats_tokens_in_out') }}</span>
                            <span class="text-right font-semibold tabular-nums">{{ number_format($this->usageStats->sum('prompt_tokens')) }}/{{ number_format($this->usageStats->sum('completion_tokens')) }}</span>
                            <span class="text-zinc-500">{{ __('profile.stats_characters') }}</span>
                            <span class="text-right font-semibold tabular-nums">{{ number_format($this->usageStats->sum('characters')) }}</span>
                            <span class="text-zinc-500">{{ __('profile.stats_cost') }}</span>
                            <span class="text-right font-semibold tabular-nums">${{ number_format($this->usageStats->sum('estimated_cost'), 4) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @else
        {{-- Entry form --}}
        <form class="space-y-4" wire:submit="saveApiKey">
            <flux:field>
                <flux:label class="flex items-center gap-2">
                    <span>{{ __('profile.openai_api_key') }}</span>
                    <flux:button href="https://platform.openai.com/api-keys" target="_blank" size="xs" icon:trailing="arrow-up-right">
                        {{ __('profile.go_to_api_keys') }}
                    </flux:button>
                </flux:label>
                <flux:input type="password" wire:model="openai_api_key" placeholder="sk-..." viewable />
                <flux:error name="openai_api_key" />
            </flux:field>

            <div class="flex items-center gap-3">
                <flux:button class="rounded-full!" type="submit" icon="check" variant="primary">
                    {{ __('profile.save') }}
                </flux:button>
                @if ($apiKeySaved)
                    <flux:text class="text-sm text-green-600">{{ __('profile.saved') }}</flux:text>
                @endif
            </div>
        </form>

        {{-- Help note --}}
        <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
            <flux:text class="text-sm text-zinc-500">{{ __('credits.api_key_help') }}</flux:text>
        </div>

    @endif

</div>
