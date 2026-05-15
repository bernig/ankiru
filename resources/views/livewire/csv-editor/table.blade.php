<flux:card class="flex flex-col space-y-6">

    {{-- ── Filter toggles + active chips ── --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-zinc-100 pb-3 dark:border-zinc-700/50">

        {{-- Toggle buttons - always visible --}}
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-400 dark:text-zinc-500">
            <button class="{{ $filterAccentNeeded ? 'bg-amber-50 ring-1 ring-amber-300 text-amber-700 dark:bg-amber-900/30 dark:ring-amber-700 dark:text-amber-300' : '' }} flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-0.5 transition-colors hover:bg-amber-50 dark:hover:bg-amber-900/20" title="{{ __('csv_editor.filter_click_to_activate') }}" wire:click="$toggle('filterAccentNeeded')">
                <span class="inline-block size-3 shrink-0 rounded-sm bg-amber-200 dark:bg-amber-900/50"></span>
                <span>{{ __('csv_editor.legend_accent_needed') }}</span>
                @if ($filterAccentNeeded)
                    <flux:icon.x-mark class="size-4 text-amber-700" />
                @endif
            </button>
            <button class="{{ $filterNoAudio ? 'bg-blue-50 ring-1 ring-blue-300 text-blue-700 dark:bg-blue-900/30 dark:ring-blue-700 dark:text-blue-300' : '' }} flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-0.5 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/20" title="{{ __('csv_editor.filter_click_to_activate') }}" wire:click="$toggle('filterNoAudio')">
                <span class="inline-block size-3 shrink-0 rounded-sm bg-blue-200 dark:bg-blue-900/30"></span>
                <span>{{ __('csv_editor.legend_audio_missing') }}</span>
                @if ($filterNoAudio)
                    <flux:icon.x-mark class="size-4 text-blue-700" />
                @endif
            </button>
        </div>

    </div>

    {{-- Skeleton shown while table data is refreshing --}}
    <div wire:loading wire:target="gotoPage,previousPage,nextPage,setPage,perPage,filterAccentNeeded,filterNoAudio,searchQuery,switchToDraft">
        <flux:skeleton.group class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-700/50" animate="shimmer">
            @foreach (range(1, $this->paginatedRows->count()) as $_)
                <div class="hidden h-12 items-center sm:flex">
                    <flux:skeleton.line />
                </div>
                <div class="flex flex-col items-center py-4 sm:hidden">
                    <flux:skeleton class="h-29 w-full rounded-lg" animate="shimmer" />
                </div>
            @endforeach
        </flux:skeleton.group>
    </div>

    {{-- Real table: hidden before Alpine initializes and during loading --}}
    <div x-cloak wire:loading.remove wire:target="gotoPage,previousPage,nextPage,setPage,perPage,filterAccentNeeded,filterNoAudio,searchQuery,switchToDraft">
        <flux:table class="max-sm:block max-sm:min-w-0" container:class="w-full">
            @if ($this->paginatedRows->isNotEmpty())
                <flux:table.columns class="max-sm:hidden" sticky>
                    <flux:table.column class="p-2! first:ps-2 last:pe-2" colspan="2">{{ __('csv_editor.source_column') }}</flux:table.column>
                    <flux:table.column class="p-2! first:ps-2 last:pe-2" colspan="2">{{ __('csv_editor.russian_column') }}</flux:table.column>
                </flux:table.columns>
            @endif

            <flux:table.rows class="max-sm:block">
                @forelse ($this->paginatedRows as $rowIndex => $row)
                    @php
                        $rowHasAudio = $this->audioExistenceByRowIndex[$rowIndex] ?? false;
                    @endphp

                    @include('livewire.csv-editor.table-row')
                @empty
                    <flux:table.row class="max-sm:block">
                        <flux:table.cell class="text-center" class="text-center max-sm:block max-sm:w-full" colspan="4">
                            {{ $searchQuery !== '' || $filterAccentNeeded || $filterNoAudio ? __('csv_editor.no_search_results') : __('csv_editor.no_rows_yet') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Error banners: translation/stress correction and TTS --}}
    @foreach ([$translationError, $ttsError] as $errorMessage)
        @if ($errorMessage)
            <div class="mb-3 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
                <flux:icon.exclamation-triangle class="size-4 shrink-0" />
                <span>{{ $errorMessage }}</span>
            </div>
        @endif
    @endforeach

    {{-- ── Footer toolbar ── --}}
    <div class="mx-auto mb-0 mt-4 flex w-full flex-col items-center gap-3" x-show="!$store.csvSearch.active">

        <div class="mb-4 flex flex-col items-center gap-2 sm:flex-row sm:gap-3">
            <flux:button class="rounded-full!" wire:click="addRow" icon="plus" variant="primary">
                {{ __('csv_editor.add_row') }}
            </flux:button>

            <span class="text-sm text-zinc-400">{{ __('csv_editor.or') }}</span>

            <flux:button class="rounded-full!" icon="sparkles" icon:variant="outline" wire:click="openGenerateRowsModal">
                {{ __('csv_editor.generate_rows_button') }}
            </flux:button>
        </div>

        {{-- Flux pagination (shown only when there is more than one page) --}}
        @if ($this->totalPages > 1)
            <flux:pagination class="w-full flex-wrap" :paginator="$this->paginatedRows" />
        @endif

        <div class="flex w-full justify-end">
            <div class="flex items-center gap-2 whitespace-nowrap text-xs font-medium text-zinc-500">
                <span>{{ __('csv_editor.per_page') }}</span>
                <flux:select wire:model.live="perPage" size="xs">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>
            </div>
        </div>

    </div>

    {{-- ── Mobile row-edit modal ── --}}
    {{-- One shared modal driven by $store.mobileEdit.rowIndex, only visible on small screens. --}}
    <flux:modal name="mobile-edit" flyout position="left">
        <div class="flex flex-col gap-5">
            <flux:field>
                <flux:label>{{ __('csv_editor.source_column') }}</flux:label>
                <textarea class="block w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-700 placeholder-zinc-400 outline-none transition-colors focus:border-zinc-400 dark:border-white/10 dark:bg-white/10 dark:text-zinc-300" rows="3" :value="$store.mobileEdit.rowIndex >= 0 ? ($wire.csvRows[$store.mobileEdit.rowIndex]?.[0] ?? '') : ''" x-on:blur="if ($store.mobileEdit.rowIndex >= 0) $wire.updateCell($store.mobileEdit.rowIndex, 0, $event.target.value)">
                </textarea>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('csv_editor.russian_column') }}</flux:label>
                <textarea class="block w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-700 placeholder-zinc-400 outline-none transition-colors focus:border-zinc-400 dark:border-white/10 dark:bg-white/10 dark:text-zinc-300" rows="3" :value="$store.mobileEdit.rowIndex >= 0 ? ($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '') : ''" x-on:blur="if ($store.mobileEdit.rowIndex >= 0) $wire.updateCell($store.mobileEdit.rowIndex, 1, $event.target.value)">
                </textarea>
            </flux:field>

            @php
                $hasAnyRussianText = collect($csvRows)->some(fn($row) => !empty(trim($row[1] ?? '')));
            @endphp
            <flux:button.group class="justify-end">
                @if ($hasAnyRussianText)
                    <flux:button variant="ghost" x-bind:title="($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim() ?
                        '{{ __('csv_editor.retranslate_with_chatgpt') }}' :
                        '{{ __('csv_editor.translate_with_chatgpt') }}'" x-show="($wire.csvRows[$store.mobileEdit.rowIndex]?.[0] ?? '').trim()" wire:loading.attr="disabled" wire:target="translateWithChatGpt" @click="$wire.translateWithChatGpt($store.mobileEdit.rowIndex)">

                        <flux:icon.loading class="size-4" wire:loading wire:target="translateWithChatGpt" />
                        <flux:icon.arrow-path class="size-4" wire:loading.remove wire:target="translateWithChatGpt" x-show="($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" />
                        <flux:icon.sparkles class="size-4" wire:loading.remove wire:target="translateWithChatGpt" x-show="!($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" />
                    </flux:button>
                @else
                    <flux:button title="{{ __('csv_editor.translate_with_chatgpt') }}" variant="ghost" x-show="($wire.csvRows[$store.mobileEdit.rowIndex]?.[0] ?? '').trim()" wire:loading.attr="disabled" wire:target="translateWithChatGpt" @click="$wire.translateWithChatGpt($store.mobileEdit.rowIndex)">

                        <flux:icon.loading class="size-4" wire:loading wire:target="translateWithChatGpt" />
                        <flux:icon.sparkles class="size-4" wire:loading.remove wire:target="translateWithChatGpt" />
                    </flux:button>
                @endif
                <flux:button title="{{ __('csv_editor.fix_stress_marks') }}" variant="ghost" x-show="($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" :disabled="$this->isStressBatchRunning" wire:loading.attr="disabled" wire:target="correctStressMarks" @click="$wire.correctStressMarks($store.mobileEdit.rowIndex)">
                    <flux:icon.loading class="size-4" wire:loading wire:target="correctStressMarks" />
                    <flux:icon.exclamation-circle class="size-4" wire:loading.remove wire:target="correctStressMarks" />
                </flux:button>
                <flux:button title="{{ __('csv_editor.open_audio_player') }}" variant="ghost" x-show="($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" :disabled="$this->isTtsBatchRunning" wire:loading.attr="disabled" wire:target="openTtsModal" @click="$wire.openTtsModal($store.mobileEdit.rowIndex)">
                    <flux:icon.loading class="size-4" wire:loading wire:target="openTtsModal" />
                    <flux:icon.speaker-wave class="size-4" wire:loading.remove wire:target="openTtsModal" />
                </flux:button>
            </flux:button.group>

            <div class="flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-700">
                <flux:button square icon="trash" icon:variant="outline" variant="danger" @click="($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim() ? $dispatch('request-delete-row', { rowIndex: $store.mobileEdit.rowIndex }) : ($wire.deleteRow($store.mobileEdit.rowIndex), $flux.modal('mobile-edit').close())" />
                <flux:modal.close>
                    <flux:button variant="filled" icon="check">{{ __('csv_editor.close') }}</flux:button>
                </flux:modal.close>
            </div>

        </div>
    </flux:modal>

    {{-- ── Delete row confirmation modal ── --}}
    <div x-data="{ pendingDeleteRowIndex: -1 }" x-on:request-delete-row.window="pendingDeleteRowIndex = $event.detail.rowIndex; $nextTick(() => $flux.modal('delete-row-confirm').show())">
        <flux:modal class="md:w-sm" name="delete-row-confirm">
            <div class="flex flex-col gap-5">
                <flux:heading size="lg">{{ __('csv_editor.delete_row') }}</flux:heading>
                <flux:text>{{ __('csv_editor.delete_row_confirm') }}</flux:text>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button class="rounded-full!" variant="subtle">{{ __('csv_editor.close') }}</flux:button>
                    </flux:modal.close>
                    <flux:button class="rounded-full!" variant="danger" wire:loading.attr="disabled" wire:target="deleteRow" @click="$wire.deleteRow(pendingDeleteRowIndex).then(() => { $flux.modal('mobile-edit').close(); $flux.modal('delete-row-confirm').close(); })">
                        <span wire:loading wire:target="deleteRow"><flux:icon.arrow-path class="size-4 animate-spin" /></span>
                        <span wire:loading.remove wire:target="deleteRow">
                            <flux:icon.trash class="size-4" />
                        </span>
                        {{ __('csv_editor.delete_row') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    </div>

</flux:card>
