<x-layout title="{{ config('app.name') }} - {{ __('auth.login') }}">
    <x-site-header />

    <div class="mx-auto max-w-md">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>{{ __('auth.login') }}</flux:breadcrumbs.item>
        </x-breadcrumbs>
    </div>

    <main class="flex justify-center pb-16 pt-4">
        <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h1 class="mb-2 text-2xl font-semibold text-zinc-900">{{ __('auth.login') }}</h1>

            <p class="mb-6 text-sm text-zinc-600">{{ __('auth.login_subtitle') }}</p>

            <form class="space-y-4" action="{{ route('login') }}" method="POST">
                @csrf
                <x-honeypot />

                <flux:input id="email" name="email" type="email" value="{{ old('email') }}" label="{{ __('auth.email') }}" required />

                <flux:field class="mb-6">
                    <flux:label for="password">{{ __('auth.password_label') }}</flux:label>
                    <flux:input class="mb-0!" id="password" name="password" type="password" required />
                    <div class="w-full text-right">
                        <a class="text-xs text-zinc-500 underline hover:text-zinc-700" href="{{ route('password.request') }}" wire:navigate>{{ __('auth.forgot_password') }}</a>
                    </div>
                    <flux:error name="password" />
                </flux:field>

                <flux:field variant="inline">
                    <flux:checkbox name="remember" type="checkbox" value="1" label="{{ __('auth.remember_me') }}" />
                </flux:field>

                <flux:button class="w-full" type="submit" variant="primary">
                    {{ __('auth.sign_in') }}
                </flux:button>
            </form>

            <p class="mt-4 text-sm text-zinc-600">
                {{ __('auth.no_account_yet') }}
                <a class="font-medium text-zinc-900 underline" href="{{ route('register') }}" wire:navigate>{{ __('auth.register') }}</a>
            </p>
        </div>
    </main>
</x-layout>
