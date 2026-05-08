<x-layout title="{{ config('app.name') }} - {{ __('auth.login') }}">
    <x-site-header />
    <main class="flex justify-center py-16">
        <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h1 class="mb-2 text-2xl font-semibold text-zinc-900">{{ __('auth.login') }}</h1>
            <p class="mb-6 text-sm text-zinc-600">{{ __('auth.login_subtitle') }}</p>

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form class="space-y-4" action="{{ route('login') }}" method="POST">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700" for="email">{{ __('auth.email') }}</label>
                    <input class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm" id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700" for="password">{{ __('auth.password_label') }}</label>
                    <input class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm" id="password" name="password" type="password" required>
                </div>

                <label class="flex items-center gap-2 text-sm text-zinc-700">
                    <input name="remember" type="checkbox" value="1">
                    {{ __('auth.remember_me') }}
                </label>

                <button class="w-full rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800" type="submit">
                    {{ __('auth.sign_in') }}
                </button>
            </form>

            <p class="mt-4 text-sm text-zinc-600">
                {{ __('auth.no_account_yet') }}
                <a class="font-medium text-zinc-900 underline" href="{{ route('register') }}">{{ __('auth.register') }}</a>
            </p>
        </div>
    </main>
</x-layout>
