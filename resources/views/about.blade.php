<x-layout :title="__('about.title')" :description="__('about.seo_description')">
    <x-site-header />

    <div class="mx-auto max-w-2xl space-y-10">
        <div>
            <x-breadcrumbs>
                <flux:breadcrumbs.item>{{ __('about.title') }}</flux:breadcrumbs.item>
            </x-breadcrumbs>

            <flux:heading class="mb-2" size="xl">{{ __('about.title') }}</flux:heading>
            <flux:subheading class="mb-8">{{ __('about.subtitle') }}</flux:subheading>

            <div class="prose prose-zinc max-w-none text-sm text-zinc-700">
                <p>{{ __('about.paragraph_1') }}</p>
                <p>{{ __('about.paragraph_2') }}</p>
                <p>{{ __('about.paragraph_3') }}</p>
            </div>
        </div>

        <flux:separator />

        {{-- Pourquoi une clé API personnelle --}}
        <div class="space-y-4">
            <div class="flex gap-4">
                <div class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                    <flux:icon.key class="size-4 text-amber-600" />
                </div>
                <flux:heading class="mt-1" size="lg">{{ __('about.key_title') }}</flux:heading>
            </div>

            <div class="prose prose-zinc max-w-none text-sm text-zinc-700">
                <p>{{ __('about.key_paragraph_1') }}</p>
                <p>{{ __('about.key_paragraph_2') }}</p>
                <p>{{ __('about.key_paragraph_3') }}</p>
            </div>
        </div>
    </div>
</x-layout>
