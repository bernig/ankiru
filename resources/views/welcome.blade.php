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

        <a class="shadow-xs mt-6 inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-xs text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-800" href="https://github.com/bernig/ankiru" target="_blank" rel="noopener noreferrer">
            <svg class="size-3.5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.477 2 2 6.477 2 12c0 4.418 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.009-.868-.013-1.703-2.782.604-3.369-1.341-3.369-1.341-.454-1.154-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0 1 12 6.836a9.59 9.59 0 0 1 2.504.337c1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.202 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z" />
            </svg>
            <span class="font-medium text-zinc-700">{{ __('welcome.open_source_badge') }}</span>
            <span>—</span>
            <span>{{ __('welcome.open_source_label') }}</span>
        </a>
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
