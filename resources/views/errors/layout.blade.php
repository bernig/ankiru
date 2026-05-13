<!DOCTYPE html>
<html class="light bg-zinc-50" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <link href="/favicon.ico" rel="icon" sizes="any">
    <link type="image/svg+xml" href="/favicon.svg" rel="icon">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col">
    <header class="mx-auto w-full max-w-7xl px-6 pt-4">
        <div class="flex items-center justify-between pb-3">
            <a class="group flex items-center gap-3" href="{{ route('csv-editor') }}">
                <img class="size-7" src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" />
                <flux:heading class="text-zinc-700 group-hover:text-zinc-900" size="xl">{{ config('app.name') }}</flux:heading>
            </a>
        </div>
        <div class="border-b border-zinc-200"></div>
    </header>

    <main class="mx-auto flex w-full max-w-7xl flex-1 items-center justify-center px-6 py-20">
        @yield('slot')
    </main>

    <footer class="border-t border-zinc-200 bg-zinc-100 p-6">
        <div class="flex items-center justify-center">
            <span class="text-xs text-zinc-500">&copy; {{ date('Y') }} {{ config('app.name') }}</span>
        </div>
    </footer>
</body>

</html>
