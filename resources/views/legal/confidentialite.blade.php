@php
    // HTML-entity-encoded mailto link, renders correctly in browsers,
    // opaque to most e-mail harvesting bots.
    $obfuscatedEmail = '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#109;&#97;&#110;&#117;&#64;&#98;&#101;&#114;&#110;&#105;&#103;&#46;&#102;&#114;" class="underline decoration-zinc-300 hover:text-zinc-900">&#109;&#97;&#110;&#117;&#64;&#98;&#101;&#114;&#110;&#105;&#103;&#46;&#102;&#114;</a>';
@endphp

<x-layout :title="__('legal.privacy_title')" :description="__('legal.privacy_seo_description')">
    <x-site-header />

    <div class="mx-auto max-w-2xl py-8">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>{{ __('legal.privacy_title') }}</flux:breadcrumbs.item>
        </x-breadcrumbs>

        <flux:heading class="mb-2" size="xl">{{ __('legal.privacy_title') }}</flux:heading>
        <flux:subheading class="mb-1">{{ __('legal.privacy_subtitle') }}</flux:subheading>
        <p class="mb-8 text-xs text-zinc-400">{{ __('legal.privacy_updated') }}</p>

        <div class="space-y-8 text-sm text-zinc-700">

            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.controller_title') }}</flux:heading>
                <p>{!! __('legal.controller_content', ['contact_email' => $obfuscatedEmail]) !!}</p>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-3" size="lg">{{ __('legal.collected_data_title') }}</flux:heading>
                <p class="mb-3">{{ __('legal.collected_data_intro') }}</p>
                <ul class="space-y-2 pl-4">
                    @foreach (['account', 'apikey', 'drafts', 'logs', 'contact', 'cookies'] as $item)
                        <li class="flex gap-2">
                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-zinc-400"></span>
                            <span>{{ __('legal.collected_data_' . $item) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-3" size="lg">{{ __('legal.purposes_title') }}</flux:heading>
                <p class="mb-3">{{ __('legal.purposes_intro') }}</p>
                <ul class="space-y-2 pl-4">
                    @foreach (['auth', 'ai', 'contact', 'prefs'] as $item)
                        <li class="flex gap-2">
                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-zinc-400"></span>
                            <span>{{ __('legal.purposes_' . $item) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-3" size="lg">{{ __('legal.retention_title') }}</flux:heading>
                <ul class="space-y-2 pl-4">
                    @foreach (['account', 'drafts', 'contact'] as $item)
                        <li class="flex gap-2">
                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-zinc-400"></span>
                            <span>{{ __('legal.retention_' . $item) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-3" size="lg">{{ __('legal.third_parties_title') }}</flux:heading>
                <ul class="space-y-2 pl-4">
                    <li class="flex gap-2">
                        <span class="mt-2 size-1.5 shrink-0 rounded-full bg-zinc-400"></span>
                        <span>{{ __('legal.third_parties_openai') }}</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-2 size-1.5 shrink-0 rounded-full bg-zinc-400"></span>
                        <span>{{ __('legal.third_parties_none') }}</span>
                    </li>
                </ul>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-3" size="lg">{{ __('legal.cookies_title') }}</flux:heading>
                <p class="mb-3">{{ __('legal.cookies_intro') }}</p>
                <ul class="space-y-2 pl-4">
                    @foreach (['session', 'csrf', 'locale'] as $item)
                        <li class="flex gap-2">
                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-zinc-400"></span>
                            <span>{{ __('legal.cookies_' . $item) }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 italic text-zinc-500">{{ __('legal.cookies_no_tracking') }}</p>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.security_title') }}</flux:heading>
                <p>{{ __('legal.security_content') }}</p>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-2" size="lg">{{ __('legal.rights_title') }}</flux:heading>
                <p>{!! __('legal.rights_content', ['contact_email' => $obfuscatedEmail]) !!}</p>
            </section>

        </div>
    </div>
</x-layout>
