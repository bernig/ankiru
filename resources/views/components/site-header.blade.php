@php
    $localeLabels = [
        'fr' => __('csv_editor.language_french', [], 'fr'),
        'en' => __('csv_editor.language_english', [], 'en'),
        'ru' => __('csv_editor.language_russian', [], 'ru'),
    ];
@endphp

<div>
    <div class="flex items-center justify-between gap-3 pb-3">
        <a class="group flex items-center gap-3" href="{{ route('csv-editor') }}" wire:navigate>
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
                <flux:button class="rounded-full!" href="{{ route('login') }}" variant="ghost" wire:navigate>
                    {{ __('auth.login') }}
                </flux:button>
                <flux:button class="rounded-full!" href="{{ route('register') }}" variant="primary" wire:navigate>
                    {{ __('auth.register') }}
                </flux:button>
            @endguest

            @auth
                <flux:dropdown position="bottom" align="end">
                    <flux:profile circle :avatar="$avatarUrl" :name="$displayName" />
                    <flux:navmenu>
                        <flux:navmenu.item href="{{ route('profile') }}" icon="user-circle" icon:variant="outline" wire:navigate>
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
        <flux:modal.trigger class="sm:hidden" name="mobile-menu">
            <flux:button square variant="ghost">
                <flux:icon name="bars-3" />
            </flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Mobile flyout menu --}}
    <flux:modal name="mobile-menu" flyout>
        <flux:navlist class="space-y-6">
            @guest
                <flux:navlist.item href="{{ route('login') }}" icon="arrow-right-end-on-rectangle" wire:navigate>
                    {{ __('auth.login') }}
                </flux:navlist.item>
                <flux:navlist.item href="{{ route('register') }}" icon="user-plus" wire:navigate>
                    {{ __('auth.register') }}
                </flux:navlist.item>
            @endguest

            @auth
                <div class="mb-2 flex items-center gap-3 px-2 py-1">
                    <flux:avatar name="{{ $displayName }}" circle :src="$avatarUrl" size="sm" />
                    <div class="flex flex-col">
                        <flux:heading>{{ $displayName }}</flux:heading>
                        <flux:text>{{ $email }}</flux:text>
                    </div>
                </div>
                <flux:navlist.item href="{{ route('profile') }}" icon="user-circle" icon:variant="outline" wire:navigate>
                    {{ __('profile.title') }}
                </flux:navlist.item>
                <flux:navlist.item href="#" icon="arrow-right-start-on-rectangle" onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();" variant="danger" icon:variant="outline">
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
    </flux:modal>

    <flux:separator />
</div>
