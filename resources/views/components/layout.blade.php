<!DOCTYPE html>
<html class="bg-zinc-50" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link href="/favicon.ico" rel="icon" sizes="any">
    <link type="image/svg+xml" href="/favicon.svg" rel="icon">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col">
    <main class="mx-auto w-full max-w-7xl flex-1 px-6 pb-12 pt-3">
        {{ $slot }}
    </main>

    <footer class="bg-taupe-200 border-taupe-300 border-t py-6">
        <div class="flex items-center justify-center gap-2 text-sm text-zinc-700">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
            <flux:separator vertical />
            <a class="hover:text-zinc-900" href="#">GitHub</a>
        </div>
    </footer>
    @fluxScripts
</body>

</html>
