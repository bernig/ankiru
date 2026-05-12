<x-layout title="{{ config('app.name') }} - {{ __('auth.reset_password') }}">
    <x-site-header />

    <div class="mx-auto max-w-md">
        <x-breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('login') }}" wire:navigate>{{ __('auth.login') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('auth.reset_password') }}</flux:breadcrumbs.item>
        </x-breadcrumbs>
    </div>

    <main class="flex justify-center pb-16 pt-4">
        <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h1 class="mb-2 text-2xl font-semibold text-zinc-900">{{ __('auth.reset_password') }}</h1>

            <p class="mb-6 text-sm text-zinc-600">{{ __('auth.reset_password_subtitle') }}</p>

            <form class="space-y-4" action="{{ route('password.update') }}" method="POST">
                @csrf

                <input name="token" type="hidden" value="{{ $request->route('token') }}" />

                <flux:input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" label="{{ __('auth.email') }}" required />
                <flux:input id="password" name="password" type="password" label="{{ __('auth.new_password') }}" required />
                <flux:input id="password_confirmation" name="password_confirmation" type="password" label="{{ __('auth.password_confirmation') }}" required />

                <flux:button class="mt-4 w-full" type="submit" variant="primary">
                    {{ __('auth.save_password') }}
                </flux:button>
            </form>
        </div>
    </main>
</x-layout>
