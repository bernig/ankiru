<x-layout :title="__('credits.cancel_title')">
    <x-site-header />

    <div class="mx-auto max-w-lg py-16 text-center">
        <div class="mb-6 flex justify-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100">
                <flux:icon class="text-zinc-500" name="x-mark" variant="outline" />
            </div>
        </div>
        <flux:heading class="mb-2" size="xl">{{ __('credits.cancel_title') }}</flux:heading>
        <flux:text class="mb-8">{{ __('credits.cancel_description') }}</flux:text>
        <flux:button href="{{ route('credits.index') }}" wire:navigate>
            {{ __('credits.back_to_credits') }}
        </flux:button>
    </div>
</x-layout>
