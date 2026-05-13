<div class="mx-auto mb-4 flex max-w-full flex-col gap-4">
    <x-site-header />

    @if ($hasCsvLoaded)
        <div class="flex flex-col justify-start gap-2 sm:flex-row sm:items-center">
            <div class="flex gap-2">
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
                        <flux:button class="rounded-full!" icon:trailing="chevron-down" variant="ghost">
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
                <div class="hidden items-center sm:flex" x-data>
                    <flux:button class="rounded-full!" title="{{ __('csv_editor.search_placeholder') }}" icon="magnifying-glass" variant="subtle" x-show="!$store.csvSearch.active" @click="$store.csvSearch.active = true; $nextTick(() => $refs.csvSearchInput?.focus())" />

                    <div x-show="$store.csvSearch.active">
                        <flux:input class="w-48" type="search" x-ref="csvSearchInput" wire:model.live.debounce.300ms="searchQuery" placeholder="{{ __('csv_editor.search_placeholder') }}" @keydown.escape="$wire.set('searchQuery', ''); $store.csvSearch.active = false">
                            <x-slot name="iconTrailing">
                                <flux:button class="-mr-1" size="sm" icon="x-mark" @click="$wire.set('searchQuery', ''); $store.csvSearch.active = false" variant="ghost" />
                            </x-slot>
                        </flux:input>
                    </div>
                </div>
            </div>

            <flux:spacer class="hidden sm:flex" />

            <div class="flex gap-2">

                {{-- Search icon / input --}}
                <div class="flex w-full flex-1 items-center justify-end sm:hidden" x-data>
                    <flux:button class="rounded-full!" title="{{ __('csv_editor.search_placeholder') }}" icon="magnifying-glass" x-show="!$store.csvSearch.active" @click="$store.csvSearch.active = true; $nextTick(() => $refs.csvSearchInput?.focus())" />

                    <div class="flex-1" x-show="$store.csvSearch.active">
                        <flux:input class="w-fullzz" type="search" x-ref="csvSearchInput" wire:model.live.debounce.300ms="searchQuery" placeholder="{{ __('csv_editor.search_placeholder') }}" @keydown.escape="$wire.set('searchQuery', ''); $store.csvSearch.active = false">
                            <x-slot name="iconTrailing">
                                <flux:button class="-mr-1" size="sm" icon="x-mark" @click="$wire.set('searchQuery', ''); $store.csvSearch.active = false" variant="ghost" />
                            </x-slot>
                        </flux:input>
                    </div>
                </div>

                <flux:modal.trigger name="bulk-actions">
                    <flux:button class="rounded-full! size-10 sm:hidden" wire:click="openBulkActionsModal">
                        <flux:icon.sparkles class="size-4" icon:variant="outline" />
                    </flux:button>

                    <flux:button class="hidden! sm:block! rounded-full!" variant="ghost" wire:click="openBulkActionsModal">
                        <span class="flex items-center gap-2">
                            <flux:icon.sparkles class="size-4" icon:variant="outline" />
                            <span class="hidden sm:inline">{{ __('csv_editor.bulk_actions') }}</span>
                        </span>
                    </flux:button>
                </flux:modal.trigger>

                <flux:dropdown position="bottom" align="end">
                    <flux:button class="rounded-full! size-10 sm:size-auto sm:h-10" variant="primary" wire:loading.attr="disabled" wire:target="downloadAnkiPackage,downloadColpkg">
                        <flux:icon.arrow-down-tray class="size-4" wire:loading.remove wire:target="downloadCsv,downloadAnkiPackage,downloadColpkg" />
                        <flux:icon.loading class="size-4" wire:loading wire:target="downloadCsv,downloadAnkiPackage,downloadColpkg" variant="outline" />
                        <span class="hidden sm:inline">{{ __('csv_editor.export') }}</span>
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
