<x-layout :title="__('legal.mentions_title')">
    <x-site-header />

    <div class="mx-auto max-w-2xl">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>{{ __('legal.mentions_title') }}</flux:breadcrumbs.item>
        </x-breadcrumbs>

        <flux:heading class="mb-2" size="xl">{{ __('legal.mentions_title') }}</flux:heading>
        <flux:subheading class="mb-8">{{ __('legal.mentions_subtitle') }}</flux:subheading>

        <div class="space-y-6 text-sm text-zinc-700">
            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.publisher_title') }}</flux:heading>
                <p>{{ config('app.name') }}<br>{{ __('legal.publisher_content') }}</p>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.hosting_title') }}</flux:heading>
                <p>{{ __('legal.hosting_content') }}</p>
            </section>
        </div>
    </div>
</x-layout>
