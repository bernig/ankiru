<x-layout :title="__('about.title')">
    <x-site-header />

    <div class="mx-auto max-w-2xl py-8">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>{{ __('about.title') }}</flux:breadcrumbs.item>
        </x-breadcrumbs>

        <flux:heading class="mb-2" size="xl">{{ __('about.title') }}</flux:heading>
        <flux:subheading class="mb-8">{{ __('about.subtitle', ['app' => config('app.name')]) }}</flux:subheading>

        <div class="prose prose-zinc max-w-none text-sm text-zinc-700">
            <p>{{ __('about.description', ['app' => config('app.name')]) }}</p>
        </div>
    </div>
</x-layout>
