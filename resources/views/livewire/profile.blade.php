<div class="space-y-6">

    {{-- Informations du profil --}}
    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('profile.profile_information') }}</flux:heading>
            <flux:text>{{ __('profile.profile_information_description') }}</flux:text>
        </div>

        <form class="space-y-4" wire:submit="updateProfile">
            <flux:field>
                <flux:label>{{ __('profile.name') }}</flux:label>
                <flux:input type="text" wire:model="name" autocomplete="name" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('profile.email') }}</flux:label>
                <flux:input type="email" wire:model="email" autocomplete="email" required />
                <flux:error name="email" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button class="rounded-full!" type="submit" icon="check" variant="primary">{{ __('profile.save') }}</flux:button>

                @if ($profileSaved)
                    <flux:text class="text-green-600">{{ __('profile.saved') }}</flux:text>
                @endif
            </div>
        </form>
    </flux:card>

    {{-- Mot de passe --}}
    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('profile.update_password') }}</flux:heading>
            <flux:text>{{ __('profile.update_password_description') }}</flux:text>
        </div>

        <form class="space-y-4" wire:submit="updatePassword">
            <flux:field>
                <flux:label>{{ __('profile.current_password') }}</flux:label>
                <flux:input type="password" wire:model="current_password" autocomplete="current-password" required />
                <flux:error name="current_password" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('profile.new_password') }}</flux:label>
                <flux:input type="password" wire:model="password" autocomplete="new-password" required />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('profile.confirm_password') }}</flux:label>
                <flux:input type="password" wire:model="password_confirmation" autocomplete="new-password" required />
                <flux:error name="password_confirmation" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button class="rounded-full!" type="submit" icon="check" variant="primary">{{ __('profile.save') }}</flux:button>

                @if ($passwordSaved)
                    <flux:text class="text-green-600">{{ __('profile.saved') }}</flux:text>
                @endif
            </div>
        </form>
    </flux:card>

    {{-- Contexte d'apprentissage --}}
    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('profile.learning_context') }}</flux:heading>
            <flux:text>{{ __('profile.learning_context_description') }}</flux:text>
        </div>

        <form class="space-y-4" wire:submit="saveLearningContext">
            <flux:field>
                <flux:label>{{ __('profile.learning_context_label') }}</flux:label>
                <flux:textarea wire:model="learning_context" rows="5" :placeholder="__('profile.learning_context_placeholder')" />
                <flux:error name="learning_context" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:button class="rounded-full!" type="submit" icon="check" variant="primary">{{ __('profile.save') }}</flux:button>

                @if ($learningContextSaved)
                    <flux:text class="text-green-600">{{ __('profile.saved') }}</flux:text>
                @endif
            </div>
        </form>
    </flux:card>

    {{-- Style des accents --}}
    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('profile.accent_style') }}</flux:heading>
            <flux:text>{{ __('profile.accent_style_description') }}</flux:text>
        </div>

        <div class="space-y-5" x-data="{
            color: '',
            bold: true,
            unicode: false,
            get noneActive() { return !this.color && !this.bold && !this.unicode; },
            init() {
                this.color = $wire.accentColor || '';
                this.bold = $wire.accentBold;
                this.unicode = $wire.accentUnicode;
            },
        }">
            <flux:switch wire:ignore :label="__('profile.accent_unicode')" :description="__('profile.accent_unicode_description')" x-model="unicode" />

            <div class="flex flex-col gap-2">
                <flux:label>{{ __('profile.accent_color') }}</flux:label>
                <flux:color-picker type="button" clearable wire:ignore x-model="color" />
            </div>

            <flux:switch wire:ignore :label="__('profile.accent_bold')" x-model="bold" />

            <flux:callout x-show="noneActive" variant="warning" icon="eye-slash">
                <flux:callout.text>{{ __('profile.accent_none_note') }}</flux:callout.text>
            </flux:callout>

            <div class="flex items-center gap-4">
                <flux:button class="rounded-full!" icon="check" variant="primary" x-on:click="$wire.saveAccentStyle(color || null, bold, unicode)">
                    {{ __('profile.save') }}
                </flux:button>

                @if ($accentStyleSaved)
                    <flux:text class="text-green-600">{{ __('profile.saved') }}</flux:text>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- Clé API OpenAI --}}
    <flux:card class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('profile.openai_api_key') }}</flux:heading>
            <flux:text>{{ __('profile.openai_api_key_description') }}</flux:text>
        </div>

        @if ($this->hasOpenAiKey)
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
                <flux:badge color="green" icon="check" size="lg">{{ __('profile.api_key_set') }}</flux:badge>
                <flux:button class="hover:bg-red-50! text-red-600! hover:text-red-700!" icon:variant="outline" icon="trash" wire:click="clearApiKey" wire:confirm="{{ __('profile.api_key_clear_confirm') }}" variant="subtle" size="sm">
                    {{ __('profile.api_key_clear') }}
                </flux:button>
            </div>

            <flux:separator />

            {{-- Statistiques d'utilisation API --}}
            <div class="space-y-2">
                <flux:heading class="mb-4 flex flex-col items-center gap-2 sm:mb-2 sm:flex-row sm:items-center" size="lg">
                    <span>{{ __('profile.api_usage_stats') }}</span>
                    <flux:button href="https://platform.openai.com/usage" target="_blank" size="xs" icon:trailing="arrow-up-right">
                        {{ __('profile.go_to_api_usage') }}
                    </flux:button>
                </flux:heading>
                <flux:text>{{ __('profile.api_usage_stats_description') }}</flux:text>
            </div>
            @if ($this->usageStats->isEmpty())
                <flux:text class="italic text-zinc-400">{{ __('profile.api_usage_empty') }}</flux:text>
            @else
                {{-- Vue tableau (sm et plus) --}}
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

                            {{-- Ligne total --}}
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

                {{-- Vue blocs (mobile uniquement) --}}
                <div class="space-y-3 sm:hidden">
                    @foreach ($this->usageStats as $stat)
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-3 font-semibold text-zinc-800 dark:text-zinc-100">
                                {{ __('profile.operation_' . $stat['operation']) }}
                            </div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('profile.stats_calls') }}</div>
                                <div class="text-right font-medium tabular-nums">{{ number_format($stat['calls']) }}</div>

                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('profile.stats_tokens_in_out') }}</div>
                                <div class="text-right font-medium tabular-nums">{{ $stat['prompt_tokens'] > 0 ? number_format($stat['prompt_tokens']) : '-' }}/{{ $stat['completion_tokens'] > 0 ? number_format($stat['completion_tokens']) : '-' }}</div>

                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('profile.stats_characters') }}</div>
                                <div class="text-right font-medium tabular-nums">{{ $stat['characters'] > 0 ? number_format($stat['characters']) : '-' }}</div>

                                <div class="text-zinc-500 dark:text-zinc-400">{{ __('profile.stats_cost') }}</div>
                                <div class="text-right font-medium tabular-nums">${{ number_format($stat['estimated_cost'], 4) }}</div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Bloc total --}}
                    <div class="rounded-lg border border-zinc-300 bg-zinc-50 p-4 dark:border-zinc-600 dark:bg-zinc-800">
                        <div class="mb-3 font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ __('profile.stats_total') }}
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div class="text-zinc-500 dark:text-zinc-400">{{ __('profile.stats_calls') }}</div>
                            <div class="text-right font-semibold tabular-nums">{{ number_format($this->usageStats->sum('calls')) }}</div>

                            <div class="text-zinc-500 dark:text-zinc-400">{{ __('profile.stats_tokens_in_out') }}</div>
                            <div class="text-right font-semibold tabular-nums">{{ number_format($this->usageStats->sum('prompt_tokens')) }}/{{ number_format($this->usageStats->sum('completion_tokens')) }}</div>

                            <div class="text-zinc-500 dark:text-zinc-400">{{ __('profile.stats_characters') }}</div>
                            <div class="text-right font-semibold tabular-nums">{{ number_format($this->usageStats->sum('characters')) }}</div>

                            <div class="text-zinc-500 dark:text-zinc-400">{{ __('profile.stats_cost') }}</div>
                            <div class="text-right font-semibold tabular-nums">${{ number_format($this->usageStats->sum('estimated_cost'), 4) }}</div>
                        </div>
                    </div>
                </div>
            @endif
        @else
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

                <div class="flex items-center gap-4">
                    <flux:button class="rounded-full!" type="submit" icon="check" variant="primary">{{ __('profile.save') }}</flux:button>

                    @if ($apiKeySaved)
                        <flux:text class="text-green-600">{{ __('profile.saved') }}</flux:text>
                    @endif
                </div>
            </form>
        @endif
    </flux:card>
</div>
