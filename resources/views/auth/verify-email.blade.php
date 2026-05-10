<x-layout title="{{ config('app.name') }} - {{ __('auth.verify_email') }}">
    <x-site-header />
    <main class="flex justify-center py-16">
        <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h1 class="mb-2 text-2xl font-semibold text-zinc-900">{{ __('auth.verify_email') }}</h1>

            <p class="mb-6 text-sm text-zinc-600">{{ __('auth.verify_email_subtitle') }}</p>

            @if (session('status') === 'verification-link-sent')
                <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">
                    {{ __('auth.verification_link_sent') }}
                </div>
            @endif

            <form action="{{ route('verification.send') }}" method="POST">
                @csrf
                <flux:button class="w-full" type="submit" variant="primary">
                    {{ __('auth.resend_verification') }}
                </flux:button>
            </form>

            <form class="mt-3" action="{{ route('logout') }}" method="POST">
                @csrf
                <flux:button class="w-full" type="submit" variant="ghost">
                    {{ __('auth.sign_out') }}
                </flux:button>
            </form>
        </div>
    </main>
</x-layout>
