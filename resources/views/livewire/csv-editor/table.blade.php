<flux:card class="flex flex-col space-y-6">
    <flux:table container:class="w-full ">
        <flux:table.columns class="" sticky>
            <flux:table.column class="py-2! px-2 first:ps-2 last:pe-2" colspan="2">{{ __('csv_editor.french_column') }}</flux:table.column>
            <flux:table.column class="py-2! px-2 first:ps-2 last:pe-2" colspan="2">{{ __('csv_editor.russian_column') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->paginatedRows as $rowIndex => $row)
                @php
                    $rowHasAudio = !empty(trim($row[1] ?? '')) && $this->ttsAudioExistsForRow($rowIndex);
                @endphp

                @include('livewire.csv-editor.table-row')
            @empty
                <flux:table.row>
                    <flux:table.cell class="py-2! px-2 text-center first:ps-2 last:pe-2">
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
</flux:card>
