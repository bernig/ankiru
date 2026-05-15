{{--
    Variables available via @@include scope:
      $rowIndex    (int)   - absolute index in $csvRows
      $row         (array) - ['source text', 'russian text']
      $rowHasAudio (bool)  - whether a cached TTS file exists for this row
--}}
<flux:table.row class="hover:bg-accent/2 max-sm:shadow-xs max-sm:border-taupe-200 group max-sm:mb-3 max-sm:block max-sm:overflow-hidden max-sm:rounded-lg max-sm:border max-sm:bg-white max-sm:p-2 max-sm:dark:bg-zinc-700" wire:key="row-{{ $rowIndex }}" x-data="{ rowIndex: {{ $rowIndex }}, rowHasAudio: {{ $rowHasAudio ? 'true' : 'false' }} }" x-on:tts-audio-generated.window="if ($event.detail.rowIndex === rowIndex) { rowHasAudio = true; }" x-on:tts-audio-deleted.window="if ($event.detail.rowIndex === rowIndex) { rowHasAudio = false; }" x-on:csv-row-added.window="if ($event.detail.rowIndex === rowIndex) { $store.csvEditing.rowIndex = rowIndex; $store.csvEditing.columnIndex = 0; $nextTick(() => $refs.input_0?.focus()) }" x-bind:class="(function() {
    var notEditing = $store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1;
    var text = $wire.csvRows[rowIndex]?.[1] ?? '';
    if (notEditing && window.csvAccentMode.cellNeedsAccent(text)) return 'bg-amber-50';
    if (notEditing && text.trim() !== '' && !rowHasAudio) return 'bg-blue-50/30';
    return '';
})()">

    {{-- ── Column 0: Source text ── --}}
    <flux:table.cell class="py-2! max-sm:border-t-0! px-2! w-1/2 whitespace-normal first:ps-2 last:pe-2 max-sm:block max-sm:w-full max-sm:pb-0">
        <div class="cursor-default" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 0" x-html="$wire.csvRows[rowIndex][0] !== undefined && $wire.csvRows[rowIndex][0] !== ''
                     ? $wire.csvRows[rowIndex][0]
                     : '-'"></div>

        <input class="border-accent w-full rounded border bg-white p-1.5 outline-none transition-colors" x-show="$store.csvEditing.rowIndex === rowIndex && $store.csvEditing.columnIndex === 0" x-ref="input_0" :value="$wire.csvRows[rowIndex][0]" @blur="$wire.updateCell(rowIndex, 0, $event.target.value); $store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @keydown.enter="$el.blur()" @keydown.escape="$store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @click.stop placeholder="-" />
    </flux:table.cell>

    {{-- ── Pencil column: edit source text (hidden on mobile, modal handles editing) ── --}}
    <flux:table.cell class="py-2! whitespace-nowrap px-2 first:ps-2 last:pe-2 max-sm:hidden">
        <div class="flex items-center whitespace-nowrap rounded-full border border-zinc-200 bg-white opacity-0 transition-opacity group-hover:opacity-100 [@media(hover:none)]:opacity-100" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 0">
            <button class="cursor-pointer rounded text-zinc-400 hover:text-blue-500" title="{{ __('csv_editor.edit_source_text') }}" @click.stop="$store.csvEditing.rowIndex = rowIndex; $store.csvEditing.columnIndex = 0; $nextTick(() => $refs.input_0?.focus())">
                <flux:icon.pencil class="m-2 size-4" />
            </button>
        </div>
    </flux:table.cell>

    {{-- ── Column 1: Russian text (accent mode) ── --}}
    <flux:table.cell class="py-2! max-sm:border-t-0! px-2! w-1/2 whitespace-normal first:ps-2 last:pe-2 max-sm:block max-sm:w-full max-sm:pb-0 max-sm:pt-1">
        <div class="cursor-default" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1" x-html="window.csvAccentMode.buildHtml($wire.csvRows[rowIndex]?.[1] ?? '')" @click="window.csvAccentMode.invalidateCache($wire.csvRows[rowIndex]?.[1] ?? ''); window.csvAccentMode.handleClick($event, $wire, 'cell', rowIndex, 1)"></div>

        <input class="border-accent w-full rounded border bg-white p-1.5 outline-none transition-colors" x-show="$store.csvEditing.rowIndex === rowIndex && $store.csvEditing.columnIndex === 1" x-ref="input_1" :value="$wire.csvRows[rowIndex][1]" @blur="window.csvAccentMode.invalidateCache($wire.csvRows[rowIndex]?.[1] ?? ''); $wire.updateCell(rowIndex, 1, $event.target.value); $store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @keydown.enter="$el.blur()" @keydown.escape="$store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @click.stop placeholder="-" />
    </flux:table.cell>

    {{-- ── Row actions ── --}}
    <flux:table.cell class="py-2! max-sm:border-t-0! px-2 first:ps-2 last:pe-2 max-sm:block max-sm:w-full max-sm:pb-2 max-sm:pt-0">

        {{-- Mobile: single "Open" button ── --}}
        <div class="flex justify-end sm:hidden">
            <flux:button size="sm" variant="subtle" icon="arrow-right" @click="$store.mobileEdit.rowIndex = rowIndex; $nextTick(() => $flux.modal('mobile-edit').show())">
                {{ __('csv_editor.open_row') }}
            </flux:button>
        </div>

        {{-- Desktop: hover-reveal action buttons ── --}}
        <div class="flex justify-end gap-2 max-sm:hidden">
            @if (!empty(trim($row[0] ?? '')) || !empty(trim($row[1] ?? '')))
                <div class="flex items-center whitespace-nowrap rounded-full border border-zinc-200 bg-white px-1 opacity-0 transition-opacity group-hover:opacity-100 [@media(hover:none)]:opacity-100">
                    <button class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-blue-500 group-hover:opacity-100 [@media(hover:none)]:opacity-100" title="{{ __('csv_editor.edit_russian_text') }}" x-show="$store.csvEditing.rowIndex !== rowIndex || $store.csvEditing.columnIndex !== 1" @click.stop="$store.csvEditing.rowIndex = rowIndex; $store.csvEditing.columnIndex = 1; $nextTick(() => $refs.input_1?.focus())">
                        <flux:icon.pencil class="mx-1 my-2 size-4" />
                    </button>

                    @if (!empty(trim($row[1] ?? '')))
                        <button type="button" class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-30 group-hover:opacity-100 [@media(hover:none)]:opacity-100" title="{{ $this->isTtsBatchRunning ? __('csv_editor.bulk_tts_in_progress') : __('csv_editor.open_audio_player') }}" wire:click="openTtsModal({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="openTtsModal({{ $rowIndex }})" @disabled($this->isTtsBatchRunning)>
                            <span wire:loading.attr="disabled" wire:target="openTtsModal({{ $rowIndex }})">
                                <flux:icon.speaker-wave class="mx-1 my-2 size-4" />
                            </span>
                        </button>
                    @endif

                    @if (!empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded px-0 text-zinc-400 opacity-0 transition-opacity hover:text-violet-500 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100 [@media(hover:none)]:opacity-100" title="{{ __('csv_editor.translate_with_chatgpt') }}" wire:click="translateWithChatGpt({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="translateWithChatGpt({{ $rowIndex }})">
                            <span wire:loading wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.arrow-path class="mx-1 my-2 size-4 animate-spin" />
                            </span>
                            <span wire:loading.remove wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.sparkles class="mx-1 my-2 size-4" />
                            </span>
                        </button>
                    @endif

                    @if (!empty(trim($row[0] ?? '')) && !empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-violet-500 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100 [@media(hover:none)]:opacity-100" title="{{ __('csv_editor.retranslate_with_chatgpt') }}" wire:click="translateWithChatGpt({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="translateWithChatGpt({{ $rowIndex }})">
                            <span wire:loading wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.arrow-path class="mx-1 my-2 size-4 animate-spin" />
                            </span>
                            <span wire:loading.remove wire:target="translateWithChatGpt({{ $rowIndex }})">
                                <flux:icon.arrow-path class="mx-1 my-2 size-4" />
                            </span>
                        </button>
                    @endif

                    @if (!empty(trim($row[1] ?? '')))
                        <button class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-orange-500 disabled:cursor-not-allowed disabled:opacity-30 group-hover:opacity-100 [@media(hover:none)]:opacity-100" title="{{ $this->isStressBatchRunning ? __('csv_editor.bulk_stress_in_progress') : __('csv_editor.fix_stress_marks') }}" wire:click="correctStressMarks({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="correctStressMarks({{ $rowIndex }})" @disabled($this->isStressBatchRunning)>
                            <span wire:loading wire:target="correctStressMarks({{ $rowIndex }})">
                                <flux:icon.arrow-path class="mx-1 my-2 size-4 animate-spin" />
                            </span>
                            <span wire:loading.remove wire:target="correctStressMarks({{ $rowIndex }})">
                                <flux:icon.exclamation-circle class="mx-1 my-2 size-4" />
                            </span>
                        </button>
                    @endif
                </div>

                <flux:separator class="my-1 opacity-0 transition-opacity group-hover:opacity-75 [@media(hover:none)]:opacity-75" vertical />
            @endif

            <div class="flex items-center whitespace-nowrap rounded-full border border-zinc-200 bg-white opacity-0 transition-opacity group-hover:opacity-100 [@media(hover:none)]:opacity-100">
                <button class="cursor-pointer rounded text-zinc-400 opacity-0 transition-opacity hover:text-red-500 group-hover:opacity-100 [@media(hover:none)]:opacity-100" title="{{ __('csv_editor.delete_row') }}" wire:click="deleteRow({{ $rowIndex }})">
                    <flux:icon.trash class="m-2 size-4" />
                </button>
            </div>
        </div>{{-- end desktop actions --}}
    </flux:table.cell>
</flux:table.row>
