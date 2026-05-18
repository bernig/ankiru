<x-layout :title="__('credits.page_title')">
    <x-site-header />

    <div class="mx-auto max-w-5xl py-8">

        {{-- Page header --}}
        <div class="mx-auto max-w-lg mb-10 space-y-2">
            <flux:heading size="xl">{{ __('credits.page_title') }}</flux:heading>
            <flux:text class="max-w-xl text-base text-zinc-500">{{ __('credits.page_intro') }}</flux:text>
        </div>

        <div class="flex flex-col gap-0 lg:flex-row lg:items-stretch">

            {{-- Left column: API key --}}
            <div class="flex min-w-0 flex-1 flex-col">

                {{-- Option header --}}
                <div class="mb-4 flex items-start gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon class="size-5 text-zinc-600 dark:text-zinc-400" name="key" variant="outline" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="lg">{{ __('credits.option_key_title') }}</flux:heading>
                            <flux:badge color="green" size="sm">{{ __('credits.option_key_badge') }}</flux:badge>
                        </div>
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('credits.option_key_desc') }}</flux:text>
                    </div>
                </div>

                <flux:card class="flex-1">
                    <livewire:credits.api-key-form />
                </flux:card>
            </div>

            {{-- Divider --}}
            <div class="flex items-center justify-center">
                <div class="my-8 flex items-center gap-3 lg:mx-8 lg:my-0 lg:flex-col">
                    <div class="h-px flex-1 bg-zinc-200 lg:h-full lg:w-px dark:bg-zinc-700"></div>
                    <span class="shrink-0 rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-400 dark:border-zinc-700 dark:bg-zinc-900">{{ __('credits.or') }}</span>
                    <div class="h-px flex-1 bg-zinc-200 lg:h-full lg:w-px dark:bg-zinc-700"></div>
                </div>
            </div>

            {{-- Right column: credits --}}
            <div class="flex min-w-0 flex-1 flex-col">

                {{-- Option header --}}
                <div class="mb-4 flex items-start gap-4">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-950">
                        <flux:icon class="size-5 text-blue-600 dark:text-blue-400" name="credit-card" variant="outline" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="lg">{{ __('credits.option_credits_title') }}</flux:heading>
                            <flux:badge color="yellow" size="sm">{{ __('credits.coming_soon_title') }}</flux:badge>
                        </div>
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('credits.option_credits_desc') }}</flux:text>
                    </div>
                </div>

                <livewire:credits.shop />
            </div>

        </div>
    </div>
</x-layout>
