@php
    /** @var int $status */

    $icons = [
        403 => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
        404 => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
        419 => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        429 => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
        500 => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        503 => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.546-4.175 3.708-3.708a.75.75 0 0 1 1.06 0l1.06 1.06a.75.75 0 0 1 0 1.06l-3.708 3.708',
    ];

    $isRetryError = in_array($status, [419, 503]);
    $iconPath = $icons[$status] ?? $icons[500];
    $locale = app()->getLocale();
@endphp

<div class="text-center">
    <p class="text-8xl font-bold tracking-tight text-amber-400 sm:text-[10rem]">{{ $status }}</p>

    <div class="-mt-4 sm:-mt-8">
        <div class="mb-4 inline-flex items-center justify-center rounded-full bg-amber-100 p-3">
            <svg class="size-7 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
            </svg>
        </div>

        <h1 class="mb-3 text-2xl font-semibold text-zinc-900 sm:text-3xl">
            {{ __("errors.{$status}_title", [], $locale) }}
        </h1>
        <p class="mx-auto mb-8 max-w-md text-base text-zinc-500">
            {{ __("errors.{$status}_description", [], $locale) }}
        </p>

        @if ($isRetryError)
            <flux:button class="rounded-full!" href="{{ url('/') }}" variant="primary" icon="arrow-path">
                {{ __('errors.try_again', [], $locale) }}
            </flux:button>
        @else
            <flux:button class="rounded-full!" href="{{ url('/') }}" variant="primary" icon="arrow-left">
                {{ __('errors.back_home', [], $locale) }}
            </flux:button>
        @endif
    </div>
</div>
