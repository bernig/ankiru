<x-layout :title="__('legal.mentions_title')" :description="__('legal.mentions_subtitle')">
    <x-site-header />

    <div class="mx-auto max-w-2xl py-8">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>{{ __('legal.mentions_title') }}</flux:breadcrumbs.item>
        </x-breadcrumbs>

        <flux:heading class="mb-2" size="xl">{{ __('legal.mentions_title') }}</flux:heading>
        <flux:subheading class="mb-1">{{ __('legal.mentions_subtitle') }}</flux:subheading>
        <p class="mb-8 text-xs text-zinc-400">{{ __('legal.mentions_updated') }}</p>

        <div class="space-y-8 text-sm text-zinc-700">

            {{-- Éditeur --}}
            <section>
                <flux:heading class="mb-3" size="lg">{{ __('legal.publisher_title') }}</flux:heading>
                <p class="mb-3">{{ __('legal.publisher_intro') }}</p>
                <dl class="space-y-1">
                    <div class="flex gap-2">
                        <dt class="w-24 shrink-0 font-medium text-zinc-500">{{ __('legal.publisher_name_label') }}</dt>
                        <dd>{{ __('legal.publisher_name_value') }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="w-24 shrink-0 font-medium text-zinc-500">{{ __('legal.publisher_address_label') }}</dt>
                        <dd class="text-zinc-500">-</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="w-24 shrink-0 font-medium text-zinc-500">{{ __('legal.publisher_email_label') }}</dt>
                        <dd class="text-zinc-500">-</dd>
                    </div>
                </dl>
                <p class="mt-4 text-xs italic text-zinc-400">{{ __('legal.publisher_lcen_note') }}</p>
            </section>

            <flux:separator />

            {{-- Directeur de la publication --}}
            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.publication_director_title') }}</flux:heading>
                <p>{{ __('legal.publication_director_value') }}</p>
            </section>

            <flux:separator />

            {{-- Hébergement --}}
            <section>
                <flux:heading class="mb-3" size="lg">{{ __('legal.hosting_title') }}</flux:heading>
                <p class="mb-3">{{ __('legal.hosting_intro') }}</p>
                <dl class="space-y-1">
                    <div class="flex gap-2">
                        <dt class="w-24 shrink-0 font-medium text-zinc-500">{{ __('legal.hosting_name_label') }}</dt>
                        <dd>Laravel Holdings Inc</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="w-24 shrink-0 font-medium text-zinc-500">{{ __('legal.hosting_address_label') }}</dt>
                        <dd>60 Broad Street, 24th Floor #1559, New York, New York 10004, United States</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="w-24 shrink-0 font-medium text-zinc-500">{{ __('legal.hosting_website_label') }}</dt>
                        <dd><a href="https://cloud.laravel.com" target="_blank">https://cloud.laravel.com</a></dd>
                    </div>
                </dl>
            </section>

            <flux:separator />

            {{-- Propriété intellectuelle --}}
            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.ip_title') }}</flux:heading>
                <p>{{ __('legal.ip_content') }}</p>
            </section>

            <flux:separator />

            {{-- Limitation de responsabilité --}}
            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.liability_title') }}</flux:heading>
                <p>{{ __('legal.liability_content') }}</p>
            </section>

        </div>
    </div>
</x-layout>
