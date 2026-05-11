<x-layout :title="config('app.name')">
    <x-site-header />

    {{-- Hero --}}
    <div class="mx-auto max-w-3xl py-20 text-center">
        <img class="mx-auto mb-6 size-16" src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" />

        <h1 class="mb-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
            {{ __('welcome.hero_title') }}
        </h1>

        <p class="mx-auto mb-10 max-w-xl text-lg text-zinc-500">
            {{ __('welcome.hero_description') }}
        </p>

        <div class="flex flex-wrap items-center justify-center gap-3">
            <flux:button class="rounded-full! px-6!" href="{{ route('register') }}" variant="primary">
                {{ __('welcome.cta_register') }}
            </flux:button>
            <flux:button class="rounded-full! px-6!" href="{{ route('login') }}" variant="ghost">
                {{ __('welcome.cta_login') }}
            </flux:button>
        </div>
    </div>

    {{-- Features --}}
    <div class="mx-auto max-w-4xl pb-20">
        <h2 class="mb-10 text-center text-xl font-semibold text-zinc-700">
            {{ __('welcome.features_title') }}
        </h2>

        <div class="grid gap-4 sm:grid-cols-2">

            <div class="shadow-xs rounded-xl border border-zinc-200 bg-white p-6">
                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-violet-100">
                    <flux:icon.language class="size-5 text-violet-600" />
                </div>
                <h3 class="mb-1 font-semibold text-zinc-900">{{ __('welcome.feature_translation_title') }}</h3>
                <p class="text-sm text-zinc-500">{{ __('welcome.feature_translation_description') }}</p>
            </div>

            <div class="shadow-xs rounded-xl border border-zinc-200 bg-white p-6">
                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-amber-100">
                    <flux:icon.pencil-square class="size-5 text-amber-600" />
                </div>
                <h3 class="mb-1 font-semibold text-zinc-900">{{ __('welcome.feature_stress_title') }}</h3>
                <p class="text-sm text-zinc-500">{{ __('welcome.feature_stress_description') }}</p>
            </div>

            <div class="shadow-xs rounded-xl border border-zinc-200 bg-white p-6">
                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-sky-100">
                    <flux:icon.speaker-wave class="size-5 text-sky-600" />
                </div>
                <h3 class="mb-1 font-semibold text-zinc-900">{{ __('welcome.feature_tts_title') }}</h3>
                <p class="text-sm text-zinc-500">{{ __('welcome.feature_tts_description') }}</p>
            </div>

            <div class="shadow-xs rounded-xl border border-zinc-200 bg-white p-6">
                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-emerald-100">
                    <flux:icon.arrow-down-tray class="size-5 text-emerald-600" />
                </div>
                <h3 class="mb-1 font-semibold text-zinc-900">{{ __('welcome.feature_export_title') }}</h3>
                <p class="text-sm text-zinc-500">{{ __('welcome.feature_export_description') }}</p>
            </div>

        </div>
    </div>
</x-layout>
