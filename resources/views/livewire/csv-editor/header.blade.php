<div class="mx-auto mb-6 flex max-w-full flex-col gap-4">
    <x-site-header />

    @if ($hasCsvLoaded)
        <div class="flex flex-wrap items-center gap-2">

            {{-- File selector / rename input --}}
            @if ($isRenamingFile)
                <div class="flex items-center gap-1">
                    <flux:input class="w-44" wire:model="renameInput" wire:keydown.enter="confirmRenameDraft" wire:keydown.escape="cancelRenameDraft" size="sm" autofocus />
                    <flux:button wire:click="confirmRenameDraft" icon="check" size="sm" variant="ghost" />
                    <flux:button wire:click="cancelRenameDraft" icon="x-mark" size="sm" variant="ghost" />
                </div>
            @else
                <flux:dropdown>
                    <flux:button icon:trailing="chevron-down" variant="ghost" size="sm">
                        {{ pathinfo($originalFileName, PATHINFO_FILENAME) }}
                    </flux:button>

                    <flux:menu>
                        @foreach ($allDraftsMeta as $draft)
                            <flux:menu.item wire:click="switchToDraft({{ $draft['id'] }})" :icon="$draft['id'] === $activeDraftId ? 'check' : null">
                                {{ pathinfo($draft['original_file_name'], PATHINFO_FILENAME) }}
                            </flux:menu.item>
                        @endforeach

                        <flux:menu.separator />

                        <flux:menu.item icon="pencil" wire:click="startRenameDraft">
                            {{ __('csv_editor.rename_file') }}
                        </flux:menu.item>

                        <flux:menu.item icon="document-plus" wire:click="createNewFile">
                            {{ __('csv_editor.create_new_file') }}
                        </flux:menu.item>

                        <flux:menu.item icon="arrow-up-tray" x-on:click="$refs.addFileInput.click()">
                            {{ __('csv_editor.add_file') }}
                        </flux:menu.item>

                        <flux:menu.separator />

                        <flux:menu.item icon="trash" variant="danger" wire:click="resetEditor" wire:confirm="{{ __('csv_editor.delete_file_confirm') }}">
                            {{ __('csv_editor.delete_file') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif

            {{-- Hidden file input for "Add file" --}}
            <input class="sr-only" type="file" x-ref="addFileInput" wire:model="uploadedCsvFile" accept=".csv,text/csv" />

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
        </div>
    @endif
</div>
