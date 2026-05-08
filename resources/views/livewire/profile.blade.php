<div class="space-y-6">

    {{-- Informations du profil --}}
    <flux:card class="space-y-6">
        <flux:heading size="lg">{{ __('profile.profile_information') }}</flux:heading>
        <flux:text>{{ __('profile.profile_information_description') }}</flux:text>

        <form wire:submit="updateProfile" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('profile.name') }}</flux:label>
                <flux:input wire:model="name" type="text" autocomplete="name" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('profile.email') }}</flux:label>
                <flux:input wire:model="email" type="email" autocomplete="email" required />
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
        @else
            <form wire:submit="saveApiKey" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('profile.openai_api_key') }}</flux:label>
                    <flux:input wire:model="openai_api_key" type="password" placeholder="sk-..." viewable />
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

        <form wire:submit="updatePassword" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('profile.current_password') }}</flux:label>
                <flux:input wire:model="current_password" type="password" autocomplete="current-password" required />
                <flux:error name="current_password" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('profile.new_password') }}</flux:label>
                <flux:input wire:model="password" type="password" autocomplete="new-password" required />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('profile.confirm_password') }}</flux:label>
                <flux:input wire:model="password_confirmation" type="password" autocomplete="new-password" required />
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
