<!DOCTYPE html>
<html class="bg-zinc-50 p-6" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link href="/favicon.ico" rel="icon" sizes="any">
    <link type="image/svg+xml" href="/favicon.svg" rel="icon">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="mx-auto min-h-screen max-w-7xl">
    {{ $slot }}
    @fluxScripts
</body>

</html>
