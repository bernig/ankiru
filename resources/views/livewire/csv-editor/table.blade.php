<div class="flex flex-col">
    <div class="mx-auto mb-4 flex w-full flex-col">
        {{-- Flux pagination (shown only when there is more than one page) --}}
        @if ($this->totalPages > 1)
            <flux:pagination class="w-full" :paginator="$this->paginatedRows" scroll-to="html" />
        @endif
    </div>

    <flux:table container:class="w-full rounded border border-zinc-200 bg-white shadow-sm text-sm">
        <flux:table.rows>

            <flux:table.row wire:key="row-header" class=" bg-zinc-200  text-zinc-500 border-b border-zinc-100 hover:bg-zinc-50">
                <flux:table.cell colspan="2" class=" px-4! py-4 font-medium text-zinc-700">
                    {{ __('csv_editor.french_column') }}
                </flux:table.cell>
                <flux:table.cell colspan="2" class="px-2 py-4 font-medium text-zinc-700">
                    {{ __('csv_editor.russian_column') }}
                </flux:table.cell>
            </flux:table.row>

            @forelse ($this->paginatedRows as $rowIndex => $row)
                @php
                    $rowHasAudio = !empty(trim($row[1] ?? '')) && $this->ttsAudioExistsForRow($rowIndex);
                @endphp

                @include('livewire.csv-editor.table-row')
            @empty
                <flux:table.row>
                    <flux:table.cell class="px-4 py-8 text-center text-zinc-400">
                        {{ __('csv_editor.no_rows_yet') }}
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
    <div class="mx-auto mt-4 flex w-full flex-col items-center gap-3">
        {{-- Flux pagination (shown only when there is more than one page) --}}
        @if ($this->totalPages > 1)
            <flux:pagination class="w-full" :paginator="$this->paginatedRows" scroll-to="html" />
        @endif

        <flux:button wire:click="addRow" icon="plus" variant="primary">
            {{ __('csv_editor.add_row') }}
        </flux:button>
    </div>
</div>
