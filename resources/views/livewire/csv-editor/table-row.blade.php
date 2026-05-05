{{--
    Variables available via @@include scope:
      $rowIndex    (int)   — absolute index in $csvRows
      $row         (array) — ['french text', 'russian text']
      $rowHasAudio (bool)  — whether a cached TTS file exists for this row
--}}
<flux:table.row class="group border-b border-zinc-100 hover:bg-zinc-50" wire:key="row-{{ $rowIndex }}" x-data="{ rowIndex: {{ $rowIndex }}, rowHasAudio: {{ $rowHasAudio ? 'true' : 'false' }} }" x-bind:class="{
    'bg-amber-50': ($store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1) && window.csvAccentMode.cellNeedsAccent($wire.csvRows[rowIndex]?.[1] ?? ''),
    '': ($store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1) && !window.csvAccentMode.cellNeedsAccent($wire.csvRows[rowIndex]?.[1] ?? '') && ($wire.csvRows[rowIndex]?.[1] ?? '').trim() !== '' && rowHasAudio,
    'bg-blue-50/30': ($store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1) && !window.csvAccentMode.cellNeedsAccent($wire.csvRows[rowIndex]?.[1] ?? '') && ($wire.csvRows[rowIndex]?.[1] ?? '').trim() !== '' && !rowHasAudio,
}">

    {{-- ── Column 0: French text ── --}}
    <flux:table.cell class="p-1! w-1/2 whitespace-normal">
        {{-- Plain text display; clicking opens the edit input --}}
        <div class="cursor-default px-2 text-zinc-800" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 0" x-html="$wire.csvRows[rowIndex][0] !== undefined && $wire.csvRows[rowIndex][0] !== ''
                     ? $wire.csvRows[rowIndex][0]
                     : '<span class=\'text-zinc-400\'>—</span>'"></div>

        <input class="mx-1 my-1 w-full rounded border border-blue-500 bg-white px-2 py-1 text-zinc-800 outline-none transition-colors" x-show="$store.csvEditing.rowIndex === rowIndex && $store.csvEditing.columnIndex === 0" x-ref="input_0" :value="$wire.csvRows[rowIndex][0]" @blur="$wire.updateCell(rowIndex, 0, $event.target.value); $store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @keydown.enter="$el.blur()" @keydown.escape="$store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @click.stop placeholder="—" />
    </flux:table.cell>

    {{-- ── Pencil column: edit French text ── --}}
    <flux:table.cell class="p-1! whitespace-nowrap">
        <div class="flex justify-end gap-1">
            <div class="m-0.5 mr-1.5 whitespace-nowrap rounded-full border border-zinc-200 bg-white px-1.5 py-1 opacity-0 transition-opacity group-hover:opacity-100" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 0">
                <button class="cursor-pointer rounded px-0.5 py-1 text-zinc-400 hover:text-blue-500" title="Edit French text" @click="$store.csvEditing.rowIndex = rowIndex; $store.csvEditing.columnIndex = 0; $nextTick(() => $refs.input_0?.focus())">
                    <flux:icon.pencil class="size-4" />
                </button>
            </div>
        </div>
    </flux:table.cell>

    {{-- ── Column 1: Russian text (accent mode) ── --}}
    <flux:table.cell class="p-1! w-1/2 whitespace-normal">
        {{-- Accent HTML display — vowels are clickable spans --}}
        <div class="cursor-default px-2 text-zinc-800" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1" x-html="window.csvAccentMode.buildHtml($wire.csvRows[rowIndex]?.[1] ?? '')" @click="window.csvAccentMode.invalidateCache($wire.csvRows[rowIndex]?.[1] ?? ''); window.csvAccentMode.handleClick($event, $wire, 'cell', rowIndex, 1)"></div>

        <input class="mx-1 w-full rounded border border-blue-500 bg-white px-2 py-1 text-zinc-800 outline-none transition-colors" x-show="$store.csvEditing.rowIndex === rowIndex && $store.csvEditing.columnIndex === 1" x-ref="input_1" :value="$wire.csvRows[rowIndex][1]" @blur="window.csvAccentMode.invalidateCache($wire.csvRows[rowIndex]?.[1] ?? ''); $wire.updateCell(rowIndex, 1, $event.target.value); $store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @keydown.enter="$el.blur()" @keydown.escape="$store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @click.stop placeholder="—" />
    </flux:table.cell>

    {{-- ── Row actions ── --}}
    <flux:table.cell class="p-0.5!">
        <div class="flex justify-end gap-1">
            @if (!empty(trim($row[0] ?? '')) || !empty(trim($row[1] ?? '')))
                <div class="m-0.5 whitespace-nowrap rounded-full border border-zinc-200 bg-white px-1.5 py-1 opacity-0 transition-opacity group-hover:opacity-100">
                    {{--
                        Russian pencil: enters edit mode for column 1.
                        Hidden while the edit input for that column is active.
                    --}}
                    @if (!empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded p-1 text-zinc-400 opacity-0 transition-opacity hover:text-blue-500 group-hover:opacity-100" title="Edit Russian text" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1" @click="$store.csvEditing.rowIndex = rowIndex; $store.csvEditing.columnIndex = 1; $nextTick(() => $refs.input_1?.focus())">
                            <flux:icon.pencil class="size-4" />
                        </button>
                    @endif

                    {{-- TTS button: always visible when Russian text exists; opens the audio player modal --}}
                    @if (!empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded p-1 text-zinc-400 opacity-0 transition-opacity hover:text-sky-700 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100" title="Open audio player" wire:click="openTtsModal({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="openTtsModal({{ $rowIndex }})">
                            <span wire:loading.attr="disabled" wire:target="openTtsModal({{ $rowIndex }})">
                                <flux:icon.speaker-wave class="size-4" />
                            </span>
                        </button>
                    @endif

                    {{-- Translate button: visible when French has text and Russian is empty --}}
                    @if (!empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? '')))
                        <button class="m-0.5 cursor-pointer rounded px-0 py-0.5 text-zinc-400 opacity-0 transition-opacity hover:text-violet-500 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100" title="Translate with ChatGPT" wire:click="translateWithChatGpt({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="translateWithChatGpt({{ $rowIndex }})">
                            <span wire:loading wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.arrow-path class="size-4 animate-spin" />
                            </span>
                            <span wire:loading.remove wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.sparkles class="size-4" />
                            </span>
                        </button>
                    @endif

                    {{-- Re-translate button: visible when both French and Russian text exist --}}
                    @if (!empty(trim($row[0] ?? '')) && !empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded p-1 text-zinc-400 opacity-0 transition-opacity hover:text-violet-500 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100" title="Regenerate translation with ChatGPT" wire:click="translateWithChatGpt({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="translateWithChatGpt({{ $rowIndex }})">
                            <span wire:loading wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.arrow-path class="size-4 animate-spin" />
                            </span>
                            <span wire:loading.remove wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.arrow-path class="size-4" />
                            </span>
                        </button>
                    @endif

                    {{-- Correct-stress button: visible when right column has text --}}
                    @if (!empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded p-1 text-zinc-400 opacity-0 transition-opacity hover:text-orange-500 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100" title="Fix stress marks with ChatGPT" wire:click="correctStressMarks({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="correctStressMarks({{ $rowIndex }})">
                            <span wire:loading wire:target="correctStressMarks({{ $rowIndex }})">
                                <flux:icon.arrow-path class="size-4 animate-spin" />
                            </span>
                            <span wire:loading.remove wire:target="correctStressMarks({{ $rowIndex }})">
                                <flux:icon.exclamation-circle class="size-4" />
                            </span>
                        </button>
                    @endif
                </div>

                <flux:separator class="my-1.5 opacity-0 transition-opacity group-hover:opacity-75" vertical />

                <div class="m-0.5 mr-1.5 whitespace-nowrap rounded-full border border-zinc-200 bg-white px-1.5 py-1 opacity-0 transition-opacity group-hover:opacity-100">
                    <button class="cursor-pointer rounded px-0.5 py-1 text-zinc-400 opacity-0 transition-opacity hover:text-red-500 group-hover:opacity-100" title="Delete row" wire:click="deleteRow({{ $rowIndex }})">
                        <flux:icon.trash class="size-4" />
                    </button>
                </div>
            @endif
        </div>
    </flux:table.cell>
</flux:table.row>
