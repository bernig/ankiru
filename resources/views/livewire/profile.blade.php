<div class="space-y-6">

    {{-- Informations du profil --}}
    <flux:card class="space-y-6">
        <flux:heading size="lg">{{ __('profile.profile_information') }}</flux:heading>
        <flux:text>{{ __('profile.profile_information_description') }}</flux:text>

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
                <flux:button type="submit" variant="primary">{{ __('profile.save') }}</flux:button>

                @if ($profileSaved)
                    <flux:text class="text-green-600">{{ __('profile.saved') }}</flux:text>
                @endif
            </div>
        </form>
    </flux:card>

    {{-- Clé API OpenAI --}}
    <flux:card class="space-y-6">
        <flux:heading size="lg">{{ __('profile.openai_api_key') }}</flux:heading>
        <flux:text>{{ __('profile.openai_api_key_description') }}</flux:text>

        @if ($this->hasOpenAiKey)
            <div class="flex items-center gap-4">
                <flux:badge color="green" icon="check-circle">{{ __('profile.api_key_set') }}</flux:badge>
                <flux:button wire:click="clearApiKey" wire:confirm="{{ __('profile.api_key_clear_confirm') }}" variant="ghost" size="sm">
                    {{ __('profile.api_key_clear') }}
                </flux:button>
            </div>

            {{-- Statistiques d'utilisation API --}}
            <flux:heading size="lg">{{ __('profile.api_usage_stats') }}</flux:heading>
            <flux:text>{{ __('profile.api_usage_stats_description') }}</flux:text>

            @if ($this->usageStats->isEmpty())
                <flux:text class="italic text-zinc-400">{{ __('profile.api_usage_empty') }}</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('profile.stats_operation') }}</flux:table.column>
                        <flux:table.column class="text-right">{{ __('profile.stats_calls') }}</flux:table.column>
                        <flux:table.column class="text-right">{{ __('profile.stats_tokens_in') }}</flux:table.column>
                        <flux:table.column class="text-right">{{ __('profile.stats_tokens_out') }}</flux:table.column>
                        <flux:table.column class="text-right">{{ __('profile.stats_characters') }}</flux:table.column>
                        <flux:table.column class="text-right">{{ __('profile.stats_cost') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->usageStats as $stat)
                            <flux:table.row>
                                <flux:table.cell>{{ __('profile.operation_' . $stat['operation']) }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ number_format($stat['calls']) }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $stat['prompt_tokens'] > 0 ? number_format($stat['prompt_tokens']) : '—' }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $stat['completion_tokens'] > 0 ? number_format($stat['completion_tokens']) : '—' }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">{{ $stat['characters'] > 0 ? number_format($stat['characters']) : '—' }}</flux:table.cell>
                                <flux:table.cell class="text-right tabular-nums">${{ number_format($stat['estimated_cost'], 4) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach

                        {{-- Ligne total --}}
                        <flux:table.row class="border-t border-zinc-200 font-semibold">
                            <flux:table.cell>{{ __('profile.stats_total') }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ number_format($this->usageStats->sum('calls')) }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ number_format($this->usageStats->sum('prompt_tokens')) }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ number_format($this->usageStats->sum('completion_tokens')) }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">{{ number_format($this->usageStats->sum('characters')) }}</flux:table.cell>
                            <flux:table.cell class="text-right tabular-nums">${{ number_format($this->usageStats->sum('estimated_cost'), 4) }}</flux:table.cell>
                        </flux:table.row>
                    </flux:table.rows>
                </flux:table>
            @endif
        @else
            <form class="space-y-4" wire:submit="saveApiKey">
                <flux:field>
                    <flux:label>{{ __('profile.openai_api_key') }}</flux:label>
                    <flux:input type="password" wire:model="openai_api_key" placeholder="sk-..." viewable />
                    <flux:error name="openai_api_key" />
                </flux:field>

                <div class="flex items-center gap-4">
                    <flux:button type="submit" variant="primary">{{ __('profile.save') }}</flux:button>

                    @if ($apiKeySaved)
                        <flux:text class="text-green-600">{{ __('profile.saved') }}</flux:text>
                    @endif
                </div>
            </form>
        @endif
    </flux:card>

    {{-- Mot de passe --}}
    <flux:card class="space-y-6">
        <flux:heading size="lg">{{ __('profile.update_password') }}</flux:heading>
        <flux:text>{{ __('profile.update_password_description') }}</flux:text>

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
                <flux:button type="submit" variant="primary">{{ __('profile.save') }}</flux:button>

                @if ($passwordSaved)
                    <flux:text class="text-green-600">{{ __('profile.saved') }}</flux:text>
                @endif
            </div>
        </form>
    </flux:card>

</div>
