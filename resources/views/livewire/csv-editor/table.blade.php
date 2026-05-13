<flux:card class="flex flex-col space-y-6">
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
                <flux:table.row>
                    <flux:table.cell class="text-center" colspan="4">
                        {{ $searchQuery !== '' ? __('csv_editor.no_search_results') : __('csv_editor.no_rows_yet') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Translation error banner --}}
    @if ($translationError)
        <div class="mb-3 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
            <flux:icon.exclamation-triangle class="size-4 shrink-0" />
            <span>{{ $translationError }}</span>
        </div>
    @endif

    {{-- TTS error banner --}}
    @if ($ttsError)
        <div class="mb-3 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
            <flux:icon.exclamation-triangle class="size-4 shrink-0" />
            <span>{{ $ttsError }}</span>
        </div>
    @endif

    {{-- ── Footer toolbar ── --}}
    <div class="mx-auto mt-4 flex w-full flex-col items-center gap-3" x-show="!$store.csvSearch.active">

        <flux:button class="rounded-full! mb-4" wire:click="addRow" icon="plus" variant="primary">
            {{ __('csv_editor.add_row') }}
        </flux:button>

        {{-- Flux pagination (shown only when there is more than one page) --}}
        @if ($this->totalPages > 1)
            <flux:pagination class="w-full flex-wrap" :paginator="$this->paginatedRows" scroll-to="html" />
        @endif

        <div class="flex w-full flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 whitespace-nowrap text-xs font-medium text-zinc-500">
                <span>{{ __('csv_editor.per_page') }}</span>
                <flux:select wire:model.live="perPage" size="xs">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>
            </div>

            {{-- Color legend --}}
            @if ($this->paginatedRows->isNotEmpty())
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-400 dark:text-zinc-500">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block size-3 rounded-sm bg-amber-200 dark:bg-amber-900/50"></span>
                        <span>{{ __('csv_editor.legend_accent_needed') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block size-3 rounded-sm bg-blue-200 dark:bg-blue-900/30"></span>
                        <span>{{ __('csv_editor.legend_audio_missing') }}</span>
                    </div>
                </div>
            @endif
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

            <flux:button.group class="justify-end">
                <flux:button variant="ghost" x-bind:title="($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim() ?
                    '{{ __('csv_editor.retranslate_with_chatgpt') }}' :
                    '{{ __('csv_editor.translate_with_chatgpt') }}'" x-show="($wire.csvRows[$store.mobileEdit.rowIndex]?.[0] ?? '').trim()" wire:loading.attr="disabled" wire:target="translateWithChatGpt" @click="$wire.translateWithChatGpt($store.mobileEdit.rowIndex)">

                    <flux:icon.loading class="size-4" wire:loading wire:target="translateWithChatGpt" />
                    <flux:icon.arrow-path class="size-4" wire:loading.remove wire:target="translateWithChatGpt" x-show="($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" />
                    <flux:icon.sparkles class="size-4" wire:loading.remove wire:target="translateWithChatGpt" x-show="!($wire.csvRows[$store.mobileEdit.rowIndex]?.[1] ?? '').trim()" />
                </flux:button>
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
                <flux:button square icon="trash" icon:variant="outline" variant="danger" @click="$wire.deleteRow($store.mobileEdit.rowIndex); $flux.modal('mobile-edit').close()" />
                <flux:modal.close>
                    <flux:button variant="filled" icon="check">{{ __('csv_editor.close') }}</flux:button>
                </flux:modal.close>
            </div>

        </div>
    </flux:modal>

</flux:card>
