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

    {{-- Clé API OpenAI → déplacée vers la page Crédits --}}
    <flux:card>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('profile.openai_api_key') }}</flux:heading>
                <flux:text>{{ __('profile.openai_api_key_moved') }}</flux:text>
            </div>
            <flux:button href="{{ route('credits.index') }}" icon:trailing="arrow-right" variant="primary" wire:navigate>
                {{ __('credits.nav_credits') }}
            </flux:button>
        </div>
    </flux:card>

</div>
