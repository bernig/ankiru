<flux:card class="flex flex-col space-y-6">
    <flux:table container:class="w-full ">
        @if ($this->paginatedRows->isNotEmpty())
            <flux:table.columns class="" sticky>
                <flux:table.column class="py-2! px-2 first:ps-2 last:pe-2" colspan="2">{{ __('csv_editor.source_column') }}</flux:table.column>
                <flux:table.column class="py-2! px-2 first:ps-2 last:pe-2" colspan="2">{{ __('csv_editor.russian_column') }}</flux:table.column>
            </flux:table.columns>
        @endif

        <flux:table.rows>
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
</flux:card>
