<!DOCTYPE html>
<html class="bg-zinc-50" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link href="/favicon.ico" rel="icon" sizes="any">
    <link type="image/svg+xml" href="/favicon.svg" rel="icon">
    <link href="/apple-touch-icon.png" rel="apple-touch-icon" sizes="180x180">
    <link type="image/png" href="/favicon-32x32.png" rel="icon" sizes="32x32">
    <link type="image/png" href="/favicon-16x16.png" rel="icon" sizes="16x16">
    <link href="/site.webmanifest" rel="manifest">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {!! CookieConsent::styles() !!}
</head>

<body class="flex min-h-screen flex-col">
    <main class="mx-auto w-full max-w-7xl flex-1 px-6 pb-12 pt-3">
        {{ $slot }}
    </main>

    <footer class="bg-taupe-200 border-taupe-300 border-t p-6">
        <div class="flex flex-col items-center gap-8">
            <div class="flex flex-wrap items-center justify-center gap-2 text-sm text-zinc-700">
                <a class="hover:text-zinc-900" href="{{ route('about') }}">{{ __('about.title') }}</a>
                <flux:separator vertical />
                <a class="hover:text-zinc-900" href="{{ route('contact') }}">{{ __('contact.title') }}</a>
                <flux:separator vertical />
                <a class="hover:text-zinc-900" href="{{ route('legal.mentions') }}">{{ __('legal.mentions_title') }}</a>
                <flux:separator vertical />
                <a class="hover:text-zinc-900" href="{{ route('legal.privacy') }}">{{ __('legal.privacy_title') }}</a>
                <flux:separator vertical />
                <a class="showHideToggleCookiePreferencesModal cursor-pointer hover:text-zinc-900">{{ __('cookie_consent.manage_preferences') }}</a>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-zinc-500">&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                <a class="flex items-center gap-1.5 text-xs text-zinc-500 hover:text-zinc-800" href="https://github.com/bernig/ankiru" target="_blank" rel="noopener noreferrer">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.418 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.009-.868-.013-1.703-2.782.604-3.369-1.341-3.369-1.341-.454-1.154-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0 1 12 6.836a9.59 9.59 0 0 1 2.504.337c1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.202 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z"/></svg>
                    <span>GitHub</span>
                </a>
                <a class="text-accent-content hover:text-accent-foreground flex items-center gap-1.5 text-xs" href="https://buymeacoffee.com/bernig" target="_blank" rel="noopener noreferrer">
                    <span>☕</span>
                    <span>Buy me a coffee</span>
                </a>
            </div>
        </div>
    </footer>
    @auth
        <livewire:open-ai-key-setup />
    @endauth
    {!! CookieConsent::scripts([
        'cookie_title'                 => __('cookie_consent.title'),
        'cookie_description'           => __('cookie_consent.description'),
        'cookie_accept_btn_text'       => __('cookie_consent.accept'),
        'cookie_reject_btn_text'       => __('cookie_consent.reject'),
        'cookie_preferences_btn_text'  => __('cookie_consent.manage_preferences'),
        'cookie_modal_title'           => __('cookie_consent.modal_title'),
        'cookie_modal_intro'           => __('cookie_consent.modal_intro'),
        'cookie_preferences_save_text' => __('cookie_consent.save'),
        'policy_links' => [['text' => __('cookie_consent.policy_link'), 'link' => '/legal/confidentialite']],
        'preferences_modal_enabled' => true,
        'cookie_categories' => [
            'necessary' => [
                'enabled'     => true,
                'locked'      => true,
                'title'       => __('cookie_consent.necessary_title'),
                'description' => __('cookie_consent.necessary_description'),
            ],
        ],
    ]) !!}
    @fluxScripts
</body>

</html>
