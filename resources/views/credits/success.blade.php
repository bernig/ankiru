<x-layout :title="__('credits.success_title')">
    <x-site-header />

    <div class="mx-auto max-w-lg py-16 text-center">
        <div class="mb-6 flex justify-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-green-100">
                <flux:icon class="text-green-600" name="check" variant="outline" />
            </div>
        </div>
        <flux:heading class="mb-2" size="xl">{{ __('credits.success_title') }}</flux:heading>
        <flux:text class="mb-8">{{ __('credits.success_description') }}</flux:text>
        <flux:button href="{{ route('credits.index') }}" variant="primary" wire:navigate>
            {{ __('credits.back_to_credits') }}
        </flux:button>
    </div>
</x-layout>
