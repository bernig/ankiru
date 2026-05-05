{{--
    Variables available via @@include scope:
      $rowIndex    (int)   — absolute index in $csvRows
      $row         (array) — ['french text', 'russian text']
      $rowHasAudio (bool)  — whether a cached TTS file exists for this row
--}}
<flux:table.row class="group hover:bg-zinc-50" wire:key="row-{{ $rowIndex }}" x-data="{ rowIndex: {{ $rowIndex }}, rowHasAudio: {{ $rowHasAudio ? 'true' : 'false' }} }" x-on:tts-audio-generated.window="if ($event.detail.rowIndex === rowIndex) { rowHasAudio = true; }" x-on:tts-audio-deleted.window="if ($event.detail.rowIndex === rowIndex) { rowHasAudio = false; }" x-bind:class="{
    'bg-amber-50': ($store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1) && window.csvAccentMode.cellNeedsAccent($wire.csvRows[rowIndex]?.[1] ?? ''),
    '': ($store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1) && !window.csvAccentMode.cellNeedsAccent($wire.csvRows[rowIndex]?.[1] ?? '') && ($wire.csvRows[rowIndex]?.[1] ?? '').trim() !== '' && rowHasAudio,
    'bg-blue-50/30': ($store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1) && !window.csvAccentMode.cellNeedsAccent($wire.csvRows[rowIndex]?.[1] ?? '') && ($wire.csvRows[rowIndex]?.[1] ?? '').trim() !== '' && !rowHasAudio,
}">

    {{-- ── Column 0: French text ── --}}
    <flux:table.cell class="py-2! w-1/2 whitespace-normal px-2 first:ps-2 last:pe-2">
        {{-- Plain text display; clicking opens the edit input --}}
        <div class="cursor-default" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 0" x-html="$wire.csvRows[rowIndex][0] !== undefined && $wire.csvRows[rowIndex][0] !== ''
                     ? $wire.csvRows[rowIndex][0]
                     : '—'"></div>

        <input class="w-full rounded border border-violet-500 bg-white p-1.5 outline-none transition-colors" x-show="$store.csvEditing.rowIndex === rowIndex && $store.csvEditing.columnIndex === 0" x-ref="input_0" :value="$wire.csvRows[rowIndex][0]" @blur="$wire.updateCell(rowIndex, 0, $event.target.value); $store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @keydown.enter="$el.blur()" @keydown.escape="$store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @click.stop placeholder="—" />
    </flux:table.cell>

    {{-- ── Pencil column: edit French text ── --}}
    <flux:table.cell class="py-2! whitespace-nowrap px-2 first:ps-2 last:pe-2">
        <div class="flex items-center whitespace-nowrap rounded-full border border-zinc-200 bg-white opacity-0 transition-opacity group-hover:opacity-100" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 0">
            <button class="cursor-pointer rounded text-zinc-400 hover:text-blue-500" title="{{ __('csv_editor.edit_french_text') }}" @click="$store.csvEditing.rowIndex = rowIndex; $store.csvEditing.columnIndex = 0; $nextTick(() => $refs.input_0?.focus())">
                <flux:icon.pencil class="m-2 size-4" />
            </button>
        </div>
    </flux:table.cell>

    {{-- ── Column 1: Russian text (accent mode) ── --}}
    <flux:table.cell class="py-2! w-1/2 whitespace-normal px-2 first:ps-2 last:pe-2">
        {{-- Accent HTML display — vowels are clickable spans --}}
        <div class="cursor-default" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1" x-html="window.csvAccentMode.buildHtml($wire.csvRows[rowIndex]?.[1] ?? '')" @click="window.csvAccentMode.invalidateCache($wire.csvRows[rowIndex]?.[1] ?? ''); window.csvAccentMode.handleClick($event, $wire, 'cell', rowIndex, 1)"></div>

        <input class="w-full rounded border border-violet-500 bg-white p-1.5 outline-none transition-colors" x-show="$store.csvEditing.rowIndex === rowIndex && $store.csvEditing.columnIndex === 1" x-ref="input_1" :value="$wire.csvRows[rowIndex][1]" @blur="window.csvAccentMode.invalidateCache($wire.csvRows[rowIndex]?.[1] ?? ''); $wire.updateCell(rowIndex, 1, $event.target.value); $store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @keydown.enter="$el.blur()" @keydown.escape="$store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @click.stop placeholder="—" />
    </flux:table.cell>

    {{-- ── Row actions ── --}}
    <flux:table.cell class="py-2! px-2 first:ps-2 last:pe-2">
        <div class="flex justify-end gap-2">
            @if (!empty(trim($row[0] ?? '')) || !empty(trim($row[1] ?? '')))
                <div class="flex items-center whitespace-nowrap rounded-full border border-zinc-200 bg-white px-1 opacity-0 transition-opacity group-hover:opacity-100">
                    {{--
                        Russian pencil: enters edit mode for column 1.
                        Hidden while the edit input for that column is active.
                    --}}
                    @if (!empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-blue-500 group-hover:opacity-100" title="{{ __('csv_editor.edit_russian_text') }}" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1" @click="$store.csvEditing.rowIndex = rowIndex; $store.csvEditing.columnIndex = 1; $nextTick(() => $refs.input_1?.focus())">
                            <flux:icon.pencil class="mx-1 my-2 size-4" />
                        </button>
                    @endif

                    {{-- TTS button: always visible when Russian text exists; opens the audio player modal --}}
                    @if (!empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-sky-700 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100" title="{{ __('csv_editor.open_audio_player') }}" wire:click="openTtsModal({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="openTtsModal({{ $rowIndex }})">
                            <span wire:loading.attr="disabled" wire:target="openTtsModal({{ $rowIndex }})">
                                <flux:icon.speaker-wave class="mx-1 my-2 size-4" />
                            </span>
                        </button>
                    @endif

                    {{-- Translate button: visible when French has text and Russian is empty --}}
                    @if (!empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded px-0 text-zinc-400 opacity-0 transition-opacity hover:text-violet-500 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100" title="{{ __('csv_editor.translate_with_chatgpt') }}" wire:click="translateWithChatGpt({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="translateWithChatGpt({{ $rowIndex }})">
                            <span wire:loading wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.arrow-path class="mx-1 my-2 size-4 animate-spin" />
                            </span>
                            <span wire:loading.remove wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.sparkles class="mx-1 my-2 size-4" />
                            </span>
                        </button>
                    @endif

                    {{-- Re-translate button: visible when both French and Russian text exist --}}
                    @if (!empty(trim($row[0] ?? '')) && !empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-violet-500 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100" title="{{ __('csv_editor.retranslate_with_chatgpt') }}" wire:click="translateWithChatGpt({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="translateWithChatGpt({{ $rowIndex }})">
                            <span wire:loading wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.arrow-path class="mx-1 my-2 size-4 animate-spin" />
                            </span>
                            <span wire:loading.remove wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.arrow-path class="mx-1 my-2 size-4" />
                            </span>
                        </button>
                    @endif

                    {{-- Correct-stress button: visible when right column has text --}}
                    @if (!empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-orange-500 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100" title="{{ __('csv_editor.fix_stress_marks') }}" wire:click="correctStressMarks({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="correctStressMarks({{ $rowIndex }})">
                            <span wire:loading wire:target="correctStressMarks({{ $rowIndex }})">
                                <flux:icon.arrow-path class="mx-1 my-2 size-4 animate-spin" />
                            </span>
                            <span wire:loading.remove wire:target="correctStressMarks({{ $rowIndex }})">
                                <flux:icon.exclamation-circle class="mx-1 my-2 size-4" />
                            </span>
                        </button>
                    @endif
                </div>

                <flux:separator class="my-1 opacity-0 transition-opacity group-hover:opacity-75" vertical />
            @endif

            <div class="flex items-center whitespace-nowrap rounded-full border border-zinc-200 bg-white opacity-0 transition-opacity group-hover:opacity-100">
                <button class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-red-500 group-hover:opacity-100" title="{{ __('csv_editor.delete_row') }}" wire:click="deleteRow({{ $rowIndex }})">
                    <flux:icon.trash class="m-2 size-4" />
                </button>
            </div>
        </div>
    </flux:table.cell>
</flux:table.row>
