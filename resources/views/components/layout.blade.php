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
</head>

<body class="flex min-h-screen flex-col">
    <main class="mx-auto w-full max-w-7xl flex-1 px-6 pb-12 pt-3">
        {{ $slot }}
    </main>

    <footer class="bg-taupe-200 border-taupe-300 border-t py-6">
        <div class="flex flex-col items-center gap-3">
            <div class="flex flex-wrap items-center justify-center gap-2 text-sm text-zinc-700">
                <a class="hover:text-zinc-900" href="{{ route('about') }}">{{ __('about.title') }}</a>
                <flux:separator vertical />
                <a class="hover:text-zinc-900" href="{{ route('contact') }}">{{ __('contact.title') }}</a>
                <flux:separator vertical />
                <a class="hover:text-zinc-900" href="{{ route('legal.mentions') }}">{{ __('legal.mentions_title') }}</a>
                <flux:separator vertical />
                <a class="hover:text-zinc-900" href="{{ route('legal.privacy') }}">{{ __('legal.privacy_title') }}</a>
            </div>
            <span class="text-xs text-zinc-500">&copy; {{ date('Y') }} {{ config('app.name') }}</span>
        </div>
    </footer>
    @fluxScripts
</body>

</html>
