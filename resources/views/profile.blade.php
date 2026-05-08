<x-layout :title="__('profile.title')">
    <x-site-header />

    <div class="py-8 max-w-2xl mx-auto">
        <flux:heading class="mb-6" size="xl">{{ __('profile.title') }}</flux:heading>
        <livewire:profile />
    </div>
</x-layout>
