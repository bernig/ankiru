<x-layout title="{{ config('app.name') }} - {{ __('auth.login') }}">
    <x-site-header />
    <main class="flex justify-center py-16">
        <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h1 class="mb-2 text-2xl font-semibold text-zinc-900">{{ __('auth.login') }}</h1>

            <p class="mb-6 text-sm text-zinc-600">{{ __('auth.login_subtitle') }}</p>

            <form class="space-y-4" action="{{ route('login') }}" method="POST">
                @csrf

                <flux:input id="email" name="email" type="email" value="{{ old('email') }}" label="{{ __('auth.email') }}" required />
                <flux:input id="password" name="password" type="password" label="{{ __('auth.password_label') }}" required />
                <flux:field variant="inline">
                    <flux:checkbox name="remember" type="checkbox" value="1" label="{{ __('auth.remember_me') }}" />
                </flux:field>

                <flux:button class="mt-4 w-full" type="submit" variant="primary">
                    {{ __('auth.sign_in') }}
                </flux:button>
            </form>

            <p class="mt-4 text-sm text-zinc-600">
                {{ __('auth.no_account_yet') }}
                <a class="font-medium text-zinc-900 underline" href="{{ route('register') }}">{{ __('auth.register') }}</a>
            </p>
        </div>
    </main>
</x-layout>
