<flux:card class="flex flex-col space-y-6">

    {{-- ── Filter toggles + active chips ── --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-zinc-100 pb-3">

        {{-- Toggle buttons - always visible --}}
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-400" data-tour="legend">
            <button class="{{ $filterAccentNeeded ? 'bg-amber-50 ring-1 ring-amber-300 text-amber-700' : '' }} flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-0.5 transition-colors hover:bg-amber-50" title="{{ __('csv_editor.filter_click_to_activate') }}" wire:click="$toggle('filterAccentNeeded')">
                <span class="inline-block size-3 shrink-0 rounded-sm bg-amber-200"></span>
                <span>{{ __('csv_editor.legend_accent_needed') }}</span>
                @if ($filterAccentNeeded)
                    <flux:icon.x-mark class="size-4 text-amber-700" />
                @endif
            </button>
            <button class="{{ $filterNoAudio ? 'bg-blue-50 ring-1 ring-blue-300 text-blue-700' : '' }} flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-0.5 transition-colors hover:bg-blue-50" title="{{ __('csv_editor.filter_click_to_activate') }}" wire:click="$toggle('filterNoAudio')">
                <span class="inline-block size-3 shrink-0 rounded-sm bg-blue-200"></span>
                <span>{{ __('csv_editor.legend_audio_missing') }}</span>
                @if ($filterNoAudio)
                    <flux:icon.x-mark class="size-4 text-blue-700" />
                @endif
            </button>
        </div>

        <flux:spacer />

        <flux:button class="rounded-full!" data-tour="practice-mode" size="sm" icon="play" variant="primary" color="purple" wire:click="openTestMode" wire:loading.attr="disabled" wire:target="openTestMode">
            <flux:icon.loading class="size-4" wire:loading wire:target="openTestMode" />
            <span wire:loading.remove wire:target="openTestMode">{{ __('csv_editor.practice_mode') }}</span>
        </flux:button>
    </div>

    {{-- Skeleton shown while table data is refreshing --}}
    <div wire:loading wire:target="gotoPage,previousPage,nextPage,setPage,perPage,filterAccentNeeded,filterNoAudio,searchQuery,switchToDraft">
        <flux:skeleton.group class="flex flex-col divide-y divide-zinc-100" animate="shimmer">
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
                    @include('livewire.csv-editor.table-row')
                @empty
                    <flux:table.row class="max-sm:block" wire:key="empty-state-row">
                        <flux:table.cell class="text-center" class="text-center max-sm:block max-sm:w-full" colspan="4">
                            {{ $searchQuery !== '' || $filterAccentNeeded || $filterNoAudio ? __('csv_editor.no_search_results') : __('csv_editor.no_rows_yet') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Error banner: translation/stress correction --}}
    @if ($translationError)
        <div class="mb-3 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
            <flux:icon.exclamation-triangle class="size-4 shrink-0" />
            <span>{{ $translationError }}</span>
        </div>
    @endif

    {{-- ── Footer toolbar ── --}}
    <div class="mx-auto mb-0 mt-4 flex w-full flex-col items-center gap-3" x-show="!$store.csvSearch.active">

        <div class="mb-4 flex flex-col items-center gap-2 sm:flex-row sm:gap-3" data-tour="add-row">
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
                <textarea class="block w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-700 placeholder-zinc-400 outline-none transition-colors focus:border-zinc-400" rows="3" :value="$store.mobileEdit.rowIndex >= 0 ? ($wire.pageRows[$store.mobileEdit.rowIndex]?.[0] ?? '') : ''" x-on:blur="if ($store.mobileEdit.rowIndex >= 0) $wire.updateCell($store.mobileEdit.rowIndex, 0, $event.target.value)">
                </textarea>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('csv_editor.russian_column') }}</flux:label>
                <textarea class="block w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-700 placeholder-zinc-400 outline-none transition-colors focus:border-zinc-400" rows="3" :value="$store.mobileEdit.rowIndex >= 0 ? ($wire.pageRows[$store.mobileEdit.rowIndex]?.[1] ?? '') : ''" x-on:blur="if ($store.mobileEdit.rowIndex >= 0) $wire.updateCell($store.mobileEdit.rowIndex, 1, $event.target.value)">
                </textarea>
            </flux:field>

            <flux:button.group class="w-full flex-col">
                @if ($this->hasAnyRussianText)
                    <flux:button variant="ghost" x-show="($wire.pageRows[$store.mobileEdit.rowIndex]?.[0] ?? '').trim()" wire:loading.attr="disabled" wire:target="translateWithChatGpt" @click="$wire.translateWithChatGpt($store.mobileEdit.rowIndex)">
                        <flux:icon.loading class="size-4" wire:loading wire:target="translateWithChatGpt" />
                        <flux:icon.arrow-path class="size-4" wire:loading.remove wire:target="translateWithChatGpt" x-show="($wire.pageRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" />
                        <flux:icon.sparkles class="size-4" wire:loading.remove wire:target="translateWithChatGpt" x-show="!($wire.pageRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" />
                        <span x-text="($wire.pageRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()
                            ? '{{ __('csv_editor.retranslate_with_chatgpt') }}'
                            : '{{ __('csv_editor.translate_with_chatgpt') }}'"></span>
                    </flux:button>
                @else
                    <flux:button variant="ghost" x-show="($wire.pageRows[$store.mobileEdit.rowIndex]?.[0] ?? '').trim()" wire:loading.attr="disabled" wire:target="translateWithChatGpt" @click="$wire.translateWithChatGpt($store.mobileEdit.rowIndex)">
                        <flux:icon.loading class="size-4" wire:loading wire:target="translateWithChatGpt" />
                        <flux:icon.sparkles class="size-4" wire:loading.remove wire:target="translateWithChatGpt" />
                        {{ __('csv_editor.translate_with_chatgpt') }}
                    </flux:button>
                @endif
                <flux:button variant="ghost" x-show="($wire.pageRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" :disabled="$this->isStressBatchRunning" wire:loading.attr="disabled" wire:target="correctStressMarks" @click="$wire.correctStressMarks($store.mobileEdit.rowIndex)">
                    <flux:icon.loading class="size-4" wire:loading wire:target="correctStressMarks" />
                    <flux:icon.exclamation-circle class="size-4" wire:loading.remove wire:target="correctStressMarks" />
                    {{ __('csv_editor.fix_stress_marks') }}
                </flux:button>
                <flux:button variant="ghost" x-show="($wire.pageRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" :disabled="$this->isTtsBatchRunning" wire:loading.attr="disabled" wire:target="openTtsModal" @click="$wire.openTtsModal($store.mobileEdit.rowIndex)">
                    <flux:icon.loading class="size-4" wire:loading wire:target="openTtsModal" />
                    <flux:icon.speaker-wave class="size-4" wire:loading.remove wire:target="openTtsModal" />
                    {{ __('csv_editor.open_audio_player') }}
                </flux:button>
            </flux:button.group>

            <div class="flex items-center justify-between border-t border-zinc-100 pt-3">
                <flux:button square icon="trash" icon:variant="outline" variant="danger" title="{{ __('csv_editor.delete_row') }}" @click="$wire.deleteRow($store.mobileEdit.rowIndex); $flux.modal('mobile-edit').close()" />
                <flux:modal.close>
                    <flux:button variant="filled" icon="check">{{ __('csv_editor.close') }}</flux:button>
                </flux:modal.close>
            </div>

        </div>
    </flux:modal>

    {{-- ── Undo toast shown after a row deletion ── --}}
    <div class="fixed inset-x-4 z-[60] mx-auto flex max-w-sm items-center justify-between gap-3 rounded-xl bg-zinc-800 px-4 py-3 text-sm text-white shadow-xl" role="status" :class="$wire.stressBatchStatus !== 'idle' || $wire.ttsBatchStatus !== 'idle' ? 'top-4' : 'bottom-4'" x-data="{ visible: false, timer: null, hideLater() { clearTimeout(this.timer); this.timer = setTimeout(() => this.visible = false, 8000); } }" x-on:row-deleted.window="visible = true; hideLater()" x-on:mouseenter="clearTimeout(timer)" x-on:mouseleave="hideLater()" x-on:focusin="clearTimeout(timer)" x-on:focusout="hideLater()" x-on:csv-file-switched.window="visible = false" x-show="visible" x-transition x-cloak>
        <span>{{ __('csv_editor.row_deleted') }}</span>
        <button class="cursor-pointer rounded font-semibold text-yellow-300 hover:text-yellow-200" type="button" wire:click="undoDeleteRow" x-on:click="visible = false">
            {{ __('csv_editor.undo') }}
        </button>
    </div>

</flux:card>
