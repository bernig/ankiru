<x-layout :title="__('admin.title')">
    <x-site-header />

    <div class="space-y-6 py-8">
        <flux:heading size="xl">{{ __('admin.title') }}</flux:heading>
        <livewire:admin.dashboard />
    </div>
</x-layout>
