<div class="mx-auto mb-6 flex max-w-full flex-col gap-4">
    @php
        $authenticatedUser = auth()->user();
        $displayName = $authenticatedUser?->name ?: ($authenticatedUser?->email ?: 'User');
        $avatarSeed = $authenticatedUser?->email ?: $displayName;
        $avatarUrl = 'https://api.dicebear.com/9.x/initials/svg?seed=' . rawurlencode($avatarSeed);
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <flux:icon.table-cells class="size-7 text-zinc-500" />
            <flux:heading size="xl">{{ $title ?? config('app.name') }}</flux:heading>
            @if ($hasCsvLoaded && $originalFileName)
                <flux:badge class="text-xs" variant="outline">{{ $originalFileName }}</flux:badge>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" icon:trailing="chevron-down">
                    {{ strtoupper(app()->getLocale()) }}
                </flux:button>
                <flux:menu>
                    <flux:menu.item href="{{ route('locale.update', 'fr') }}">
                        {{ __('csv_editor.language_french') }}
                    </flux:menu.item>
                    <flux:menu.item href="{{ route('locale.update', 'en') }}">
                        {{ __('csv_editor.language_english', [], 'en') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <flux:dropdown position="bottom" align="end">
                <flux:profile :avatar="$avatarUrl" :name="$displayName" />
                <flux:navmenu>
                    <flux:navmenu.item href="#" icon="arrow-right-start-on-rectangle" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        {{ __('csv_editor.logout') }}
                    </flux:navmenu.item>
                </flux:navmenu>
            </flux:dropdown>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        @if ($hasCsvLoaded)
            <flux:modal.trigger name="bulk-actions">
                <flux:button icon="sparkles" variant="ghost" wire:click="openBulkActionsModal">
                    {{ __('csv_editor.bulk_actions') }}
                </flux:button>
            </flux:modal.trigger>

            <flux:dropdown position="bottom" align="end">
                <flux:button icon="arrow-down-tray" icon:trailing="chevron-down" variant="primary">
                    {{ __('csv_editor.export') }}
                </flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="downloadCsv" icon="document-text">
                        {{ __('csv_editor.export_csv') }}
                    </flux:menu.item>
                    <flux:menu.item wire:click="downloadAnkiPackage" icon="archive-box-arrow-down">
                        {{ __('csv_editor.export_anki_package') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <flux:button wire:click="resetEditor" icon="arrow-up-tray" variant="ghost" wire:confirm="{{ __('csv_editor.load_new_file_confirm') }}">
                {{ __('csv_editor.load_new_file') }}
            </flux:button>
        @endif
    </div>

    <form class="hidden" id="logout-form" action="{{ route('logout') }}" method="POST">
        @csrf
    </form>
</div>
