<div x-on:open-openai-key-setup.window="$flux.modal('openai-key-setup').show()" x-on:openai-key-saved.window="$flux.modal('openai-key-setup').close()">
    <flux:modal class="max-w-lg space-y-6" name="openai-key-setup">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('profile.openai_key_modal_title') }}</flux:heading>
            <flux:text>{{ __('profile.openai_key_modal_description') }}</flux:text>
        </div>

        <div class="space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <flux:text class="font-medium text-amber-900">{{ __('profile.openai_key_modal_how_to_get') }}</flux:text>
            <ol class="list-inside list-decimal space-y-1 text-sm text-amber-800">
                <li>{{ __('profile.openai_key_modal_step_1') }}</li>
                <li>{{ __('profile.openai_key_modal_step_2') }}</li>
                <li>{{ __('profile.openai_key_modal_step_3') }}</li>
            </ol>
            <div>
                <flux:button href="https://platform.openai.com/api-keys" target="_blank" size="sm" icon:trailing="arrow-up-right">
                    {{ __('profile.go_to_api_keys') }}
                </flux:button>
            </div>
        </div>

        <form class="space-y-4" wire:submit="saveApiKey">
            <flux:field>
                <flux:label>{{ __('profile.openai_api_key') }}</flux:label>
                <flux:input type="password" wire:model="openai_api_key" placeholder="sk-..." viewable />
                <flux:error name="openai_api_key" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button class="rounded-full!" variant="subtle">{{ __('profile.openai_key_modal_later') }}</flux:button>
                </flux:modal.close>
                <flux:button class="rounded-full!" type="submit" variant="primary" icon="key">
                    {{ __('profile.save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
