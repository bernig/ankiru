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

                {{-- Champs optionnels --}}
                <div class="relative my-2">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-zinc-200"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-xs font-medium uppercase tracking-wider text-zinc-400">{{ __('auth.optional_section') }}</span>
                    </div>
                </div>

                <p class="text-sm text-zinc-500">{{ __('auth.optional_section_description') }}</p>

                <flux:field>
                    <flux:label class="flex items-center gap-2">
                        {{ __('auth.openai_api_key') }}
                        <flux:badge size="sm" color="zinc">{{ __('auth.optional') }}</flux:badge>
                    </flux:label>
                    <flux:input id="openai_api_key" name="openai_api_key" type="password" value="{{ old('openai_api_key') }}" viewable placeholder="sk-..." />
                    <flux:error name="openai_api_key" />
                    <flux:description>{{ __('auth.openai_api_key_hint') }}</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label class="flex items-center gap-2">
                        {{ __('auth.learning_context') }}
                        <flux:badge size="sm" color="zinc">{{ __('auth.optional') }}</flux:badge>
                    </flux:label>
                    <flux:textarea id="learning_context" name="learning_context" rows="4" :placeholder="__('auth.learning_context_placeholder')">{{ old('learning_context') }}</flux:textarea>
                    <flux:error name="learning_context" />
                    <flux:description>{{ __('auth.learning_context_hint') }}</flux:description>
                </flux:field>

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
