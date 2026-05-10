<div class="mx-auto mb-4 flex max-w-full flex-col gap-4">
    <x-site-header />

    @if ($hasCsvLoaded)
        <div class="flex flex-col items-center justify-start gap-2 md:flex-row">

            {{-- File selector / rename input --}}
            @if ($isRenamingFile)
                <div class="flex w-full items-center gap-1 md:w-auto">
                    <flux:input.group>
                        <flux:input wire:model="renameInput" wire:keydown.enter="confirmRenameDraft" wire:keydown.escape="cancelRenameDraft" autofocus />
                        <flux:button wire:click="confirmRenameDraft" icon="check" />
                        <flux:button wire:click="cancelRenameDraft" icon="x-mark" />
                    </flux:input.group>
                </div>
            @else
                <flux:dropdown>
                    <flux:button class="rounded-full!" icon:trailing="chevron-down" variant="ghost" size="sm">
                        {{ pathinfo($originalFileName, PATHINFO_FILENAME) }}
                    </flux:button>

                    <flux:menu>
                        @foreach ($allDraftsMeta as $draft)
                            <flux:menu.item wire:click="switchToDraft({{ $draft['id'] }})" :icon="$draft['id'] === $activeDraftId ? 'check' : null">
                                {{ pathinfo($draft['original_file_name'], PATHINFO_FILENAME) }}
                            </flux:menu.item>
                        @endforeach

                        <flux:menu.separator />

                        <flux:menu.item icon:variant="outline" icon="pencil" wire:click="startRenameDraft">
                            {{ __('csv_editor.rename_file') }}
                        </flux:menu.item>

                        <flux:menu.item icon:variant="outline" icon="document-plus" wire:click="createNewFile">
                            {{ __('csv_editor.create_new_file') }}
                        </flux:menu.item>

                        <flux:menu.item icon="arrow-up-tray" x-on:click="$refs.addFileInput.click()">
                            {{ __('csv_editor.add_file') }}
                        </flux:menu.item>

                        <flux:menu.separator />

                        <flux:menu.item icon:variant="outline" icon="swatch" x-on:click="$flux.modal('accent-style').show()">
                            {{ __('csv_editor.accent_style') }}
                        </flux:menu.item>

                        <flux:menu.separator />

                        <flux:menu.item icon:variant="outline" icon="trash" variant="danger" wire:click="resetEditor" wire:confirm="{{ __('csv_editor.delete_file_confirm') }}">
                            {{ __('csv_editor.delete_file') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif

            {{-- Hidden file input for "Add file" --}}
            <input class="sr-only" type="file" x-ref="addFileInput" wire:model="uploadedCsvFile" accept=".csv,text/csv" />

            {{-- Search icon / input --}}
            <div class="flex items-center" x-data>
                <flux:button class="rounded-full!" title="{{ __('csv_editor.search_placeholder') }}" size="sm" icon="magnifying-glass" variant="subtle" x-show="!$store.csvSearch.active" @click="$store.csvSearch.active = true; $nextTick(() => $refs.csvSearchInput?.focus())" />

                <div x-show="$store.csvSearch.active">
                    <flux:input class="w-48" type="search" size="sm" x-ref="csvSearchInput" wire:model.live.debounce.300ms="searchQuery" placeholder="{{ __('csv_editor.search_placeholder') }}" @keydown.escape="$wire.set('searchQuery', ''); $store.csvSearch.active = false">
                        <x-slot name="iconTrailing">
                            <flux:button class="-mr-1" size="xs" icon="x-mark" @click="$wire.set('searchQuery', ''); $store.csvSearch.active = false" variant="ghost" />
                        </x-slot>
                    </flux:input>
                </div>
            </div>

            <flux:spacer />

            <div class="flex flex-wrap justify-center gap-2 md:justify-end">
                <flux:modal.trigger name="bulk-actions">
                    <flux:button class="rounded-full!" icon:variant="outline" icon="sparkles" variant="ghost" wire:click="openBulkActionsModal">
                        {{ __('csv_editor.bulk_actions') }}
                    </flux:button>
                </flux:modal.trigger>

                <flux:dropdown position="bottom" align="end">
                    <flux:button class="rounded-full!" variant="primary" wire:loading.attr="disabled" wire:target="downloadAnkiPackage,downloadColpkg">
                        <flux:icon.arrow-down-tray class="size-4" wire:loading.remove wire:target="downloadCsv,downloadAnkiPackage,downloadColpkg" />
                        <flux:icon.loading class="size-4" wire:loading wire:target="downloadCsv,downloadAnkiPackage,downloadColpkg" variant="outline" />
                        {{ __('csv_editor.export') }}
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="downloadCsv" icon:variant="outline" icon="document-text">
                            {{ __('csv_editor.export_csv') }}
                        </flux:menu.item>
                        <flux:menu.item wire:click="downloadAnkiPackage" icon:variant="outline" icon="archive-box-arrow-down">
                            {{ __('csv_editor.export_anki_package') }}
                        </flux:menu.item>
                        @if (count($allDraftsMeta) >= 2)
                            <flux:menu.separator />
                            <flux:menu.item x-on:click="$flux.modal('collection-export').show()" icon:variant="outline" icon="rectangle-stack">
                                {{ __('csv_editor.export_collection_package') }}
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    @endif
</div>
