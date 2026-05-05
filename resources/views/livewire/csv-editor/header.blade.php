<div class="mx-auto mb-6 flex max-w-full items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <flux:icon.table-cells class="size-7 text-zinc-500" />
        <flux:heading size="xl">{{ $title ?? config('app.name') }}</flux:heading>
        @if ($hasCsvLoaded && $originalFileName)
            <flux:badge class="text-xs" variant="outline">{{ $originalFileName }}</flux:badge>
        @endif
    </div>

    @if ($hasCsvLoaded)
        <div class="flex items-center gap-2">

            {{-- Export dropdown: plain CSV or full Anki package with TTS audio --}}
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
        </div>
    @endif
</div>
