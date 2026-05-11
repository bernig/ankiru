@php
    $localeLabels = [
        'fr' => __('csv_editor.language_french', [], 'fr'),
        'en' => __('csv_editor.language_english', [], 'en'),
        'ru' => __('csv_editor.language_russian', [], 'ru'),
    ];
@endphp

<div x-data="{ open: false }">
    <div class="flex items-center justify-between gap-3 pb-3">
        <a class="group flex items-center gap-3" href="{{ route('csv-editor') }}">
            <img class="size-7" src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" />
            <flux:heading class="text-zinc-700 group-hover:text-zinc-900" size="xl">{{ config('app.name') }}</flux:heading>
        </a>

        {{-- Desktop nav --}}
        <div class="hidden items-center gap-2 sm:flex">
            <flux:dropdown position="bottom" align="end">
                <flux:button class="rounded-full!" variant="ghost" icon:trailing="chevron-down">
                    {{ strtoupper(app()->getLocale()) }}
                </flux:button>
                <flux:navmenu>
                    @foreach (config('app.supported_locales') as $supportedLocale)
                        @continue(!array_key_exists($supportedLocale, $localeLabels))

                        <flux:navmenu.item href="{{ route('locale.update', $supportedLocale) }}">
                            {{ $localeLabels[$supportedLocale] }}
                        </flux:navmenu.item>
                    @endforeach
                </flux:navmenu>
            </flux:dropdown>

            @guest
                <flux:button class="rounded-full!" href="{{ route('login') }}" variant="ghost">
                    {{ __('auth.login') }}
                </flux:button>
                <flux:button class="rounded-full!" href="{{ route('register') }}" variant="primary">
                    {{ __('auth.register') }}
                </flux:button>
            @endguest

            @auth
                <flux:dropdown position="bottom" align="end">
                    <flux:profile circle :avatar="$avatarUrl" :name="$displayName" />
                    <flux:navmenu>
                        <flux:navmenu.item href="{{ route('profile') }}" icon="user-circle" icon:variant="outline">
                            {{ __('profile.title') }}
                        </flux:navmenu.item>
                        <flux:navmenu.item href="#" icon="arrow-right-start-on-rectangle" icon:variant="outline" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" variant="danger">
                            {{ __('csv_editor.logout') }}
                        </flux:navmenu.item>
                    </flux:navmenu>
                </flux:dropdown>

                <form class="hidden" id="logout-form" action="{{ route('logout') }}" method="POST">
                    @csrf
                </form>
            @endauth
        </div>

        {{-- Mobile hamburger --}}
        <flux:button class="sm:hidden" square variant="ghost" @click="open = !open">
            <flux:icon name="bars-3" x-show="!open" />
            <flux:icon name="x-mark" x-show="open" />
        </flux:button>
    </div>

    {{-- Mobile menu --}}
    <div class="border-t border-zinc-200 pb-3 pt-2 sm:hidden" x-show="open" x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
        <flux:navlist>
            @guest
                <flux:navlist.item href="{{ route('login') }}" icon="arrow-right-end-on-rectangle">
                    {{ __('auth.login') }}
                </flux:navlist.item>
                <flux:navlist.item href="{{ route('register') }}" icon="user-plus">
                    {{ __('auth.register') }}
                </flux:navlist.item>
            @endguest

            @auth
                <div class="mb-2 flex items-center gap-3 px-2 py-1">
                    <flux:avatar circle :src="$avatarUrl" size="sm" />
                    <span class="text-sm font-medium text-zinc-700">{{ $displayName }}</span>
                </div>
                <flux:navlist.item href="{{ route('profile') }}" icon="user-circle">
                    {{ __('profile.title') }}
                </flux:navlist.item>
                <flux:navlist.item href="#" icon="arrow-right-start-on-rectangle" onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();" variant="danger">
                    {{ __('csv_editor.logout') }}
                </flux:navlist.item>
                <form class="hidden" id="logout-form-mobile" action="{{ route('logout') }}" method="POST">
                    @csrf
                </form>
            @endauth

            <flux:separator class="my-2" />

            <flux:dropdown>
                <flux:navlist.item icon="language" icon:trailing="chevron-down">
                    {{ strtoupper(app()->getLocale()) }}
                </flux:navlist.item>
                <flux:navmenu>
                    @foreach (config('app.supported_locales') as $supportedLocale)
                        @continue(!array_key_exists($supportedLocale, $localeLabels))

                        <flux:navmenu.item href="{{ route('locale.update', $supportedLocale) }}">
                            {{ $localeLabels[$supportedLocale] }}
                        </flux:navmenu.item>
                    @endforeach
                </flux:navmenu>
            </flux:dropdown>
        </flux:navlist>
    </div>

    <flux:separator />
</div>
