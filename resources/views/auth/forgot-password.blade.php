<x-layout title="{{ config('app.name') }} - {{ __('auth.forgot_password') }}">
    <x-site-header />
    <main class="flex justify-center py-16">
        <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h1 class="mb-2 text-2xl font-semibold text-zinc-900">{{ __('auth.forgot_password') }}</h1>

            <p class="mb-6 text-sm text-zinc-600">{{ __('auth.forgot_password_subtitle') }}</p>

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <form class="space-y-4" action="{{ route('password.email') }}" method="POST">
                @csrf

                <flux:input id="email" name="email" type="email" value="{{ old('email') }}" label="{{ __('auth.email') }}" required />

                <flux:button class="mt-4 w-full" type="submit" variant="primary">
                    {{ __('auth.send_reset_link') }}
                </flux:button>
            </form>

            <p class="mt-4 text-sm text-zinc-600">
                {{ __('auth.remember_password') }}
                <a class="font-medium text-zinc-900 underline" href="{{ route('login') }}">{{ __('auth.sign_in') }}</a>
            </p>
        </div>
    </main>
</x-layout>
