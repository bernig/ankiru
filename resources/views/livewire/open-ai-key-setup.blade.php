<div x-on:open-openai-key-setup.window="$flux.modal('openai-key-setup').show()">
    <flux:modal class="max-w-md " name="openai-key-setup">
        <div class="flex items-start gap-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/40">
                <flux:icon name="sparkles" variant="outline" class="size-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('profile.openai_key_modal_title') }}</flux:heading>
                <flux:text>{{ __('profile.openai_key_modal_description') }}</flux:text>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6  sm:flex-row flex-col">
            <flux:modal.close>
                <flux:button class="w-full sm:w-auto" variant="ghost">{{ __('profile.openai_key_modal_later') }}</flux:button>
            </flux:modal.close>
            <flux:modal.close>
                <flux:button class="w-full sm:w-auto" href="{{ route('credits.index') }}" variant="primary" icon="key" wire:navigate>
                    {{ __('profile.openai_key_modal_cta') }}
                </flux:button>
            </flux:modal.close>
        </div>
    </flux:modal>
</div>
