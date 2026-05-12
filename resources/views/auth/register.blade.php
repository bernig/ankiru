<x-layout title="{{ config('app.name') }} - {{ __('auth.register') }}">
    <x-site-header />

    <div class="mx-auto max-w-md">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>{{ __('auth.register') }}</flux:breadcrumbs.item>
        </x-breadcrumbs>
    </div>

    <main class="flex justify-center pb-16 pt-4">
        <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h1 class="mb-2 text-2xl font-semibold text-zinc-900">{{ __('auth.register') }}</h1>

            <p class="mb-6 text-sm text-zinc-600">{{ __('auth.register_subtitle') }}</p>

            <form class="space-y-4" action="{{ route('register') }}" method="POST">
                @csrf

                <flux:input id="name" name="name" type="text" value="{{ old('name') }}" label="{{ __('auth.name') }}" required />
                <flux:input id="email" name="email" type="email" value="{{ old('email') }}" label="{{ __('auth.email') }}" required />
                <flux:input id="password" name="password" type="password" label="{{ __('auth.password_label') }}" required />
                <flux:input id="password_confirmation" name="password_confirmation" type="password" label="{{ __('auth.password_confirmation') }}" required />

                <flux:button class="mt-4 w-full" type="submit" variant="primary">{{ __('auth.create_account') }}</flux:button>
            </form>

            <p class="mt-4 text-sm text-zinc-600">
                {{ __('auth.already_registered') }}
                <a class="font-medium text-zinc-900 underline" href="{{ route('login') }}" wire:navigate>
                    {{ __('auth.sign_in') }}
                </a>
            </p>
        </div>
    </main>
</x-layout>
