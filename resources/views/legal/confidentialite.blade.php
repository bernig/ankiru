<x-layout :title="__('legal.privacy_title')">
    <x-site-header />

    <div class="mx-auto max-w-2xl">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>{{ __('legal.privacy_title') }}</flux:breadcrumbs.item>
        </x-breadcrumbs>

        <flux:heading class="mb-2" size="xl">{{ __('legal.privacy_title') }}</flux:heading>
        <flux:subheading class="mb-8">{{ __('legal.privacy_subtitle') }}</flux:subheading>

        <div class="space-y-6 text-sm text-zinc-700">
            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.collected_data_title') }}</flux:heading>
                <p>{{ __('legal.collected_data_content') }}</p>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.rights_title') }}</flux:heading>
                <p>{{ __('legal.rights_content') }}</p>
            </section>
        </div>
    </div>
</x-layout>
