<div class="mx-auto mb-6 flex max-w-full flex-col gap-4">
    <x-site-header />

    <div class="flex flex-wrap items-center justify-end gap-2">
        @if ($hasCsvLoaded)
            @if ($originalFileName)
                <flux:badge class="text-xs" variant="outline">{{ $originalFileName }}</flux:badge>
            @endif

            {{-- @todo: add a csv selector, for those already uploaded/created --}}

            <flux:spacer />

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
</div>
