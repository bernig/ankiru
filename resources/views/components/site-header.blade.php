@php
    $localeLabels = [
        'fr' => __('csv_editor.language_french', [], 'fr'),
        'en' => __('csv_editor.language_english', [], 'en'),
        'ru' => __('csv_editor.language_russian', [], 'ru'),
    ];
@endphp

<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <a class="group flex items-center gap-3" href="{{ route('csv-editor') }}">
            <img class="size-7" src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" />
            <flux:heading class="text-zinc-700 group-hover:text-zinc-900" size="xl">{{ config('app.name') }}</flux:heading>
        </a>
    </div>

    <div class="flex items-center gap-2">
        {{-- Language switcher - available to both guests and authenticated users --}}
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

        {{-- Login/register shown only to guests --}}
        @guest
            <flux:button class="rounded-full!" href="{{ route('login') }}" variant="ghost">
                {{ __('auth.login') }}
            </flux:button>
            <flux:button class="rounded-full!" href="{{ route('register') }}" variant="primary">
                {{ __('auth.register') }}
            </flux:button>
        @endguest

        {{-- Profile/logout shown only to authenticated users --}}
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

    <flux:separator />
</div>
