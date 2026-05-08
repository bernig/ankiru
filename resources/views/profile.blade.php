<x-layout :title="__('profile.title')">
    <x-site-header />

    <div class="mx-auto max-w-2xl py-8">
        <flux:heading class="mb-6" size="xl">{{ __('profile.title') }}</flux:heading>
        <livewire:profile />
    </div>
</x-layout>
