@props(['filename' => null])

<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <flux:icon.table-cells class="size-7 text-zinc-500" />
        <flux:heading size="xl">{{ config('app.name') }}</flux:heading>
        @if ($filename)
            <flux:badge class="text-xs" variant="outline">{{ $filename }}</flux:badge>
        @endif
    </div>

    <div class="flex items-center gap-2">
        {{-- Language switcher - available to both guests and authenticated users --}}
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="ghost" icon:trailing="chevron-down">
                {{ strtoupper(app()->getLocale()) }}
            </flux:button>
            <flux:navmenu>
                <flux:navmenu.item href="{{ route('locale.update', 'fr') }}">
                    {{ __('csv_editor.language_french') }}
                </flux:navmenu.item>
                <flux:navmenu.item href="{{ route('locale.update', 'en') }}">
                    {{ __('csv_editor.language_english', [], 'en') }}
                </flux:navmenu.item>
            </flux:navmenu>
        </flux:dropdown>

        {{-- Profile/logout shown only to authenticated users --}}
        @auth
            <flux:dropdown position="bottom" align="end">
                <flux:profile :avatar="$avatarUrl" :name="$displayName" />
                <flux:navmenu>
                    <flux:navmenu.item href="#" icon="arrow-right-start-on-rectangle" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        {{ __('csv_editor.logout') }}
                    </flux:navmenu.item>
                </flux:navmenu>
            </flux:dropdown>

            <form class="hidden" id="logout-form" action="{{ route('logout') }}" method="POST">
                @csrf
            </form>
        @endauth
    </div>
</div>
