<!DOCTYPE html>
<html class="bg-zinc-50 p-6" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Register</title>
    <link href="/favicon.ico" rel="icon" sizes="any">
    <link type="image/svg+xml" href="/favicon.svg" rel="icon">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="mx-auto flex min-h-screen w-full max-w-md items-center">
    <div class="w-full rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h1 class="mb-2 text-2xl font-semibold text-zinc-900">Register</h1>
        <p class="mb-6 text-sm text-zinc-600">Create an account to persist your CSV drafts.</p>
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        <form class="space-y-4" action="{{ route('register') }}" method="POST">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700" for="name">Name</label>
                <input class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm" id="name" name="name" type="text" value="{{ old('name') }}" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700" for="email">Email</label>
                <input class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm" id="email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700" for="password">Password</label>
                <input class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm" id="password" name="password" type="password" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700" for="password_confirmation">Confirm password</label>
                <input class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm" id="password_confirmation" name="password_confirmation" type="password" required>
            </div>
            <button class="w-full rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800" type="submit">Create account</button>
        </form>
        <p class="mt-4 text-sm text-zinc-600">Already registered? <a class="font-medium text-zinc-900 underline" href="{{ route('login') }}">Sign in</a></p>
    </div>
</body>

</html>
