<x-layout title="{{ __('contact.title') }}" description="{{ __('contact.subheading') }}">
    <x-site-header />

    <div class="mx-auto max-w-2xl">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>{{ __('contact.heading') }}</flux:breadcrumbs.item>
        </x-breadcrumbs>

        <flux:heading class="mb-2" size="xl">{{ __('contact.heading') }}</flux:heading>
        <flux:subheading class="mb-8">{{ __('contact.subheading') }}</flux:subheading>

        <livewire:contact />
    </div>
</x-layout>
