<div class="min-h-screen" x-data="{ ttsAudioUrl: null, ttsModalAudioSrc: null, ttsModalOpen: false }" x-on:tts-audio-ready.window="
        ttsAudioUrl = $event.detail.audioUrl;
        ttsModalAudioSrc = $event.detail.audioUrl;
        $nextTick(() => {
            if (ttsModalOpen) {
                // Modal is visible: play through the modal player only
                const modalPlayer = document.getElementById('tts-modal-audio');
                if (modalPlayer) { modalPlayer.load(); modalPlayer.play(); }
            } else {
                // No modal: play the hidden background player
                if ($refs.ttsPlayer) { $refs.ttsPlayer.load(); $refs.ttsPlayer.play(); }
            }
        });
    " x-on:open-tts-modal.window="
        ttsModalAudioSrc = $event.detail.audioUrl || null;
        ttsModalOpen = true;
        $flux.modal('tts-player').show();
        if ($event.detail.audioUrl) {
            $nextTick(() => {
                const modalPlayer = document.getElementById('tts-modal-audio');
                if (modalPlayer) { modalPlayer.load(); modalPlayer.play(); }
            });
        }
    ">

    {{-- Hidden audio element driven by Alpine.js when TTS audio is ready --}}
    <audio class="hidden" x-ref="ttsPlayer" :src="ttsAudioUrl"></audio>

    {{-- ── Header bar ── --}}
    <div class="mx-auto mb-6 flex max-w-full items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:icon.table-cells class="size-7 text-zinc-500" />
            <flux:heading size="xl">{{ $title ?? 'CSV Editor' }}</flux:heading>
            @if ($hasCsvLoaded && $originalFileName)
                <flux:badge class="text-xs" variant="outline">{{ $originalFileName }}</flux:badge>
            @endif
        </div>

        @if ($hasCsvLoaded)
            <div class="flex items-center gap-2">

                {{-- Export dropdown: plain CSV or full Anki package with TTS audio --}}
                <flux:dropdown position="bottom" align="end">
                    <flux:button icon="arrow-down-tray" icon:trailing="chevron-down" variant="primary">
                        Export
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="downloadCsv" icon="document-text">
                            Export CSV
                        </flux:menu.item>
                        <flux:menu.item wire:click="downloadAnkiPackage" icon="archive-box-arrow-down">
                            Export Anki Package (.apkg)
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
                <flux:button wire:click="resetEditor" icon="arrow-up-tray" variant="ghost" wire:confirm="This will discard the current file. Are you sure?">
                    Load new file
                </flux:button>
            </div>
        @endif
    </div>

    {{-- ── Upload panel (shown when no CSV is loaded) ── --}}
    @if (!$hasCsvLoaded)
        <div class="mx-auto mt-16 max-w-lg">
            <flux:card class="p-8 text-center">
                <flux:icon.document-text class="mx-auto mb-4 size-12 text-zinc-400" />
                <flux:heading class="mb-1" size="lg">Upload a CSV file</flux:heading>
                <flux:text class="mb-6 text-zinc-500">Select a .csv file to start editing.</flux:text>

                <flux:file-upload wire:model="uploadedCsvFile" accept=".csv,text/csv">
                    <flux:file-upload.dropzone heading="Drop your CSV here" text="or click to browse" with-progress />
                </flux:file-upload>

                <flux:error class="mt-2" name="uploadedCsvFile" />

                @if ($validationError)
                    <flux:callout class="mt-4 text-left" variant="danger" icon="exclamation-triangle">
                        {{ $validationError }}
                    </flux:callout>
                @endif

                <div class="mt-3 text-center text-sm text-zinc-500" wire:loading wire:target="uploadedCsvFile">
                    Parsing…
                </div>
            </flux:card>
        </div>
    @endif

    {{-- ── Editable table (shown once a CSV is loaded) ── --}}
    @if ($hasCsvLoaded)

        <div class="flex flex-col">
            <flux:table container:class="w-full rounded border border-zinc-200 bg-white shadow-sm text-sm">
                <flux:table.rows>
                    @forelse ($this->paginatedRows as $rowIndex => $row)
                        @php
                            $rowHasAudio = !empty(trim($row[1] ?? '')) && $this->ttsAudioExistsForRow($rowIndex);
                        @endphp

                        <flux:table.row class="group border-b border-zinc-100 hover:bg-zinc-50" x-bind:class="{
                            'bg-amber-50': ($store.csvEditing.rowIndex !== {{ $rowIndex }} || $store.csvEditing.columnIndex !== 1) && window.csvAccentMode.cellNeedsAccent($wire.csvRows[{{ $rowIndex }}]?.[1] ?? ''),
                            '': ($store.csvEditing.rowIndex !== {{ $rowIndex }} || $store.csvEditing.columnIndex !== 1) && !window.csvAccentMode.cellNeedsAccent($wire.csvRows[{{ $rowIndex }}]?.[1] ?? '') && ($wire.csvRows[{{ $rowIndex }}]?.[1] ?? '').trim() !== '' && {{ $rowHasAudio ? 'true' : 'false' }},
                            'bg-blue-50/30': ($store.csvEditing.rowIndex !== {{ $rowIndex }} || $store.csvEditing.columnIndex !== 1) && !window.csvAccentMode.cellNeedsAccent($wire.csvRows[{{ $rowIndex }}]?.[1] ?? '') && ($wire.csvRows[{{ $rowIndex }}]?.[1] ?? '').trim() !== '' && !{{ $rowHasAudio ? 'true' : 'false' }},
                        }" wire:key="row-{{ $rowIndex }}" x-data="{}">

                            {{-- ── Column 0: French text ── --}}
                            <flux:table.cell class="p-1! w-1/2 whitespace-normal">
                                {{-- Plain text display; clicking opens the edit input --}}
                                <div class="px-2 text-zinc-800" x-show="$store.csvEditing.rowIndex !== {{ $rowIndex }} || $store.csvEditing.columnIndex !== 0" x-html="$wire.csvRows[{{ $rowIndex }}][0] !== undefined && $wire.csvRows[{{ $rowIndex }}][0] !== ''
                                             ? $wire.csvRows[{{ $rowIndex }}][0]
                                             : '<span class=\'text-zinc-400\'>—</span>'">
                                </div>

                                <input class="m-1.5! w-full rounded border border-blue-500 bg-white px-2 py-1 text-zinc-800 outline-none transition-colors" x-show="$store.csvEditing.rowIndex === {{ $rowIndex }} && $store.csvEditing.columnIndex === 0" x-ref="input_0" :value="$wire.csvRows[{{ $rowIndex }}][0]" @blur="$wire.updateCell({{ $rowIndex }}, 0, $event.target.value); $store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @keydown.enter="$el.blur()" @keydown.escape="$store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @click.stop placeholder="—" />
                            </flux:table.cell>

                            {{-- ── Pencil column: edit French text ── --}}
                            <flux:table.cell class="p-1! whitespace-nowrap px-1">
                                <div class="flex justify-end gap-1">
                                    <div class="m-0.5 mr-1.5 whitespace-nowrap rounded-full border border-zinc-200 bg-white px-1.5 py-1 opacity-0 transition-opacity group-hover:opacity-100" x-show="$store.csvEditing.rowIndex !== {{ $rowIndex }} || $store.csvEditing.columnIndex !== 0">
                                        <button class="cursor-pointer rounded px-0.5 py-1 text-zinc-400 hover:text-blue-500" title="Edit French text" @click="$store.csvEditing.rowIndex = {{ $rowIndex }}; $store.csvEditing.columnIndex = 0; $nextTick(() => $refs.input_0?.focus())">
                                            <flux:icon.pencil class="size-4" />
                                        </button>
                                    </div>
                                </div>
                            </flux:table.cell>

                            {{-- ── Column 1: Russian text (accent mode) ── --}}
                            <flux:table.cell class="p-1! w-1/2 whitespace-normal">
                                {{-- Accent HTML display — vowels are clickable spans --}}
                                <div class="cursor-default px-2 text-zinc-800" x-show="$store.csvEditing.rowIndex !== {{ $rowIndex }} || $store.csvEditing.columnIndex !== 1" x-html="window.csvAccentMode.buildHtml($wire.csvRows[{{ $rowIndex }}]?.[1] ?? '')" @click="window.csvAccentMode.invalidateCache($wire.csvRows[{{ $rowIndex }}]?.[1] ?? ''); window.csvAccentMode.handleClick($event, $wire, 'cell', {{ $rowIndex }}, 1)"></div>

                                <input class="mx-1 w-full rounded border border-blue-500 bg-white px-2 py-1 text-zinc-800 outline-none transition-colors" x-show="$store.csvEditing.rowIndex === {{ $rowIndex }} && $store.csvEditing.columnIndex === 1" x-ref="input_1" :value="$wire.csvRows[{{ $rowIndex }}][1]" @blur="window.csvAccentMode.invalidateCache($wire.csvRows[{{ $rowIndex }}]?.[1] ?? ''); $wire.updateCell({{ $rowIndex }}, 1, $event.target.value); $store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @keydown.enter="$el.blur()" @keydown.escape="$store.csvEditing.rowIndex = -1; $store.csvEditing.columnIndex = -1" @click.stop placeholder="—" />
                            </flux:table.cell>

                            {{-- Row actions --}}
                            <flux:table.cell class="p-0.5!">
                                <div class="flex justify-end gap-1">
                                    <div class="m-0.5 whitespace-nowrap rounded-full border border-zinc-200 bg-white px-1.5 py-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        {{--
                                            Russian pencil: enters edit mode for column 1.
                                            Hidden while the edit input for that column is active.
                                        --}}
                                        @if (!empty(trim($row[1] ?? '')))
                                            <button class="cursor-pointer rounded p-1 text-zinc-400 opacity-0 transition-opacity hover:text-blue-500 group-hover:opacity-100" title="Edit Russian text" x-show="$store.csvEditing.rowIndex !== {{ $rowIndex }} || $store.csvEditing.columnIndex !== 1" @click="$store.csvEditing.rowIndex = {{ $rowIndex }}; $store.csvEditing.columnIndex = 1; $nextTick(() => $refs.input_1?.focus())">
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
                                            <button class="cursor-default cursor-pointer rounded p-1 text-zinc-400 opacity-0 transition-opacity hover:text-violet-500 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100" title="Translate with ChatGPT" wire:click="translateWithChatGpt({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="translateWithChatGpt({{ $rowIndex }})">
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
                                                {{-- Spinner while correcting --}}
                                                <span wire:loading wire:target="correctStressMarks({{ $rowIndex }})">
                                                    <flux:icon.arrow-path class="size-4 animate-spin" />
                                                </span>
                                                {{-- Exclamation icon when idle --}}
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
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell class="px-4 py-8 text-center text-zinc-400">
                                No rows yet. Click "Add row" to add one.
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
                    <flux:pagination class="w-full" :paginator="$this->paginatedRows" scroll-to />
                @endif

                {{-- Action row: Add row button --}}
                <flux:button wire:click="addRow" icon="plus">
                    Add row
                </flux:button>
            </div>
        </div>
    @endif

    {{-- ── TTS audio player modal ── --}}
    @php
        $ttsModalRussianText = $ttsModalRowIndex >= 0 && isset($csvRows[$ttsModalRowIndex]) ? $csvRows[$ttsModalRowIndex][1] ?? '' : '';

        $ttsModalAudioExists = false;
        $ttsModalCreatedAt = null;

        if ($ttsModalRowIndex >= 0 && !empty(trim($ttsModalRussianText))) {
            /** @var \App\Services\RussianTextToSpeechService $ttsServiceForModal */
            $ttsServiceForModal = app(\App\Services\RussianTextToSpeechService::class);
            $ttsModalAudioExists = $ttsServiceForModal->audioFileExists($ttsModalRussianText);

            if ($ttsModalAudioExists) {
                $ttsModalCacheKey = $ttsServiceForModal->hashRawString($ttsModalRussianText);
                $ttsModalLastModified = Storage::disk('local')->lastModified("tts/{$ttsModalCacheKey}.mp3");
                $ttsModalCreatedAt = now()->setTimestamp($ttsModalLastModified)->format('j M Y, H:i');
            }
        }
    @endphp

    <flux:modal class="md:w-xl" name="tts-player" x-on:close="
            ttsModalOpen = false;
            ttsModalAudioSrc = null;
            const player = document.getElementById('tts-modal-audio');
            if (player) { player.pause(); player.removeAttribute('src'); }
        ">
        <div class="flex flex-col gap-5">
            <flux:heading size="lg">{!! $ttsModalRussianText !!}</flux:heading>

            @if ($ttsModalAudioExists)
                <div class="flex flex-col gap-2">
                    {{-- Native audio player; src is driven by Alpine to stay reactive across generate/refresh --}}
                    <audio class="w-full rounded" id="tts-modal-audio" controls lang="ru" :src="ttsModalAudioSrc"></audio>

                    {{-- File creation date in muted text --}}
                    <flux:text class="text-xs text-zinc-400">
                        Generated {{ $ttsModalCreatedAt }}
                    </flux:text>
                </div>
            @elseif ($ttsModalRowIndex >= 0)
                <flux:callout variant="warning" icon="speaker-x-mark">
                    <flux:callout.text>
                        No audio file generated yet for this phrase.
                    </flux:callout.text>
                </flux:callout>
            @endif

            {{-- Action buttons — inside default slot since flux:modal has no footer slot --}}
            <div class="flex items-center gap-2">
                <flux:spacer />

                @if ($ttsModalRowIndex >= 0 && $ttsModalAudioExists)
                    {{-- Delete: removes the cached file; modal stays open showing the "no audio" state --}}
                    <flux:button variant="danger" icon="trash" wire:click="deleteTtsAudio({{ $ttsModalRowIndex }})" wire:loading.attr="disabled" wire:target="deleteTtsAudio({{ $ttsModalRowIndex }})" />

                    {{-- Refresh: deletes + regenerates; tts-audio-ready updates the audio player src --}}
                    <flux:button icon="sparkles" wire:click="refreshTtsAudio({{ $ttsModalRowIndex }})" wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="refreshTtsAudio({{ $ttsModalRowIndex }})" variant="primary">
                        Regenerate
                    </flux:button>
                @elseif ($ttsModalRowIndex >= 0)
                    {{-- Generate: creates audio for the first time --}}
                    <flux:button variant="primary" icon="speaker-wave" wire:click="generateTtsAudio({{ $ttsModalRowIndex }})" wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="generateTtsAudio({{ $ttsModalRowIndex }})">
                        Generate Audio
                    </flux:button>
                @endif

                <flux:modal.close>
                    <flux:button variant="filled">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>

{{-- ── CSS for dynamically generated Russian-accent vowel spans ── --}}
@once
    <style>
        /* Unstressed Russian vowel — clickable in accent mode */
        .rv-vowel {
            color: #666;
            cursor: pointer;
            border-radius: 2px;
            transition: color 0.1s, background-color 0.1s;
        }

        .rv-vowel:hover {
            color: #1d4ed8;
            background-color: #dbeafe;
            text-decoration: underline;
        }

        /* Stressed (accented) Russian vowel — already wrapped in <b> */
        .rv-vowel-accented {
            color: #d97706;
            font-weight: bold;
            cursor: pointer;
            border-radius: 2px;
            padding: 0 1px;
        }

        .rv-vowel-accented:hover {
            color: #b45309;
        }

    </style>
@endonce

{{-- ── JavaScript helpers for Russian accent mode ── --}}
@once
    <script>
        /**
         * Global Alpine store that tracks which single cell is currently in edit mode.
         * Using a shared store ensures that activating edit on any row automatically
         * deactivates any previously open row, preventing multiple text fields from
         * being active at the same time.
         */
        document.addEventListener('alpine:init', () => {
            Alpine.store('csvEditing', {
                rowIndex: -1,
                columnIndex: -1
            });
        });

        /**
         * Russian accent mode utilities.
         * Exposed as window.csvAccentMode so Alpine x-html expressions and event
         * handlers can call them without needing access to the Livewire component scope.
         */
        window.csvAccentMode = (function() {

            /** Set of all Russian vowels (lower- and upper-case). */
            const RUSSIAN_VOWELS = new Set('аеёиоуыэюяАЕЁИОУЫЭЮЯ');

            /** Regex that matches maximal runs of Cyrillic characters (= one Russian word). */
            const CYRILLIC_WORD_RE = /[а-яёА-ЯЁ]+/g;

            /**
             * Memoization caches: keyed by raw text → result.
             * These are pure functions so the same input always yields the same output.
             * With ~200 rows the map stays small and avoids repeating expensive string walks
             * on every Alpine reactive tick.
             */
            const _buildHtmlCache = new Map();
            const _cellNeedsAccentCache = new Map();

            /**
             * Walk rawText character-by-character, respecting <b>…</b> tags, and return
             * an array of {text, bold} segments.
             *
             * @param  {string} rawText
             * @returns {{ text: string, bold: boolean }[]}
             */
            function parseSegments(rawText) {
                const segments = [];
                let inBold = false;
                let current = '';
                let i = 0;

                while (i < rawText.length) {
                    if (rawText.startsWith('<b>', i)) {
                        if (current) segments.push({
                            text: current,
                            bold: false
                        });
                        current = '';
                        inBold = true;
                        i += 3;
                    } else if (rawText.startsWith('</b>', i)) {
                        if (current) segments.push({
                            text: current,
                            bold: true
                        });
                        current = '';
                        inBold = false;
                        i += 4;
                    } else {
                        current += rawText[i];
                        i++;
                    }
                }

                if (current) segments.push({
                    text: current,
                    bold: inBold
                });
                return segments;
            }

            /**
             * Build a Set of plain-text character indices that sit inside a Russian word.
             *
             * @param  {string} plain  Plain text with <b> tags already stripped.
             * @returns {Set<number>}
             */
            function buildRussianWordPositions(plain) {
                const positions = new Set();
                CYRILLIC_WORD_RE.lastIndex = 0;
                let m;
                while ((m = CYRILLIC_WORD_RE.exec(plain)) !== null) {
                    for (let p = m.index; p < m.index + m[0].length; p++) {
                        positions.add(p);
                    }
                }
                return positions;
            }

            /**
             * Build the HTML for a cell/header in Russian accent mode.
             *
             * Each Russian vowel is wrapped in a <span class="rv-vowel"> (unstressed) or
             * <span class="rv-vowel-accented"> (already stressed) with a data-vowel-pos
             * attribute holding its 0-based index in the stripped plain text.
             *
             * Results are memoized by raw text to avoid repeating the string walk on every
             * Alpine reactive tick.
             *
             * @param  {string} rawText  Raw cell value (may contain <b>…</b> accent tags).
             * @returns {string}         HTML string safe for x-html.
             */
            function buildHtml(rawText) {
                if (_buildHtmlCache.has(rawText)) {
                    return _buildHtmlCache.get(rawText);
                }

                const result = _computeBuildHtml(rawText);
                _buildHtmlCache.set(rawText, result);
                return result;
            }

            function _computeBuildHtml(rawText) {
                if (!rawText || rawText.trim() === '') {
                    return '<span style="color:#a1a1aa">—</span>';
                }

                const segments = parseSegments(rawText);
                const plain = segments.map(s => s.text).join('');
                const inWord = buildRussianWordPositions(plain);
                let result = '';
                let plainPos = 0;

                for (const seg of segments) {
                    for (let j = 0; j < seg.text.length; j++) {
                        const ch = seg.text[j];
                        const pos = plainPos + j;

                        if (RUSSIAN_VOWELS.has(ch) && inWord.has(pos)) {
                            // Render as a clickable, colour-coded vowel span.
                            const cssClass = seg.bold ? 'rv-vowel-accented' : 'rv-vowel';
                            result += `<span class="${cssClass}" data-vowel-pos="${pos}">${ch}</span>`;
                        } else if (seg.bold) {
                            // Non-vowel inside <b> (edge case) — preserve bold rendering.
                            result += `<b>${ch}</b>`;
                        } else {
                            result += ch;
                        }
                    }
                    plainPos += seg.text.length;
                }

                return result;
            }

            /**
             * Handle a click inside an accent-mode cell or header display element.
             *
             * Finds the nearest ancestor span with data-vowel-pos and dispatches the
             * appropriate Livewire action.
             *
             * @param {MouseEvent}       event
             * @param {object}           wire      Alpine $wire proxy for this component.
             * @param {'cell'|'header'}  type
             * @param {number}           primary   rowIndex (cell) or columnIndex (header).
             * @param {number|undefined} secondary columnIndex (cell only — omit for header).
             */
            function handleClick(event, wire, type, primary, secondary) {
                const target = event.target.closest('[data-vowel-pos]');
                if (!target) return;

                event.stopPropagation();

                const vowelPos = parseInt(target.dataset.vowelPos, 10);
                if (isNaN(vowelPos)) return;

                if (type === 'header') {
                    wire.placeAccentOnHeaderVowel(primary, vowelPos);
                } else {
                    wire.placeAccentOnVowel(primary, secondary, vowelPos);
                }
            }

            /**
             * Return true when rawText contains at least one Russian word with more than
             * 2 syllables (= more than 2 vowels) that carries no <b> accent mark.
             *
             * Used to decide whether to outline a cell in amber in accent mode.
             * Results are memoized by raw text to avoid repeating the walk on every
             * Alpine reactive tick.
             *
             * @param  {string} rawText
             * @returns {boolean}
             */
            function cellNeedsAccent(rawText) {
                if (!rawText || typeof rawText !== 'string') return false;

                if (_cellNeedsAccentCache.has(rawText)) {
                    return _cellNeedsAccentCache.get(rawText);
                }

                const result = _computeCellNeedsAccent(rawText);
                _cellNeedsAccentCache.set(rawText, result);
                return result;
            }

            function _computeCellNeedsAccent(rawText) {
                const chars = [];
                let inBold = false;
                let i = 0;

                while (i < rawText.length) {
                    if (rawText.startsWith('<b>', i)) {
                        inBold = true;
                        i += 3;
                        continue;
                    }
                    if (rawText.startsWith('</b>', i)) {
                        inBold = false;
                        i += 4;
                        continue;
                    }
                    chars.push({
                        c: rawText[i],
                        bold: inBold
                    });
                    i++;
                }

                // Collect Cyrillic runs (words) and check each one.
                const cyrillicRe = /[а-яёА-ЯЁ]/;
                let wordChars = [];

                const wordNeedsAccent = (wc) => {
                    if (wc.length === 0) return false;
                    const totalVowels = wc.filter(({
                        c
                    }) => RUSSIAN_VOWELS.has(c)).length;
                    const accentedVowels = wc.filter(({
                        c,
                        bold
                    }) => RUSSIAN_VOWELS.has(c) && bold).length;
                    // Highlight only when there are > 2 syllables and no accent yet.
                    return totalVowels > 2 && accentedVowels === 0;
                };

                for (const charObj of chars) {
                    if (cyrillicRe.test(charObj.c)) {
                        wordChars.push(charObj);
                    } else {
                        if (wordNeedsAccent(wordChars)) return true;
                        wordChars = [];
                    }
                }

                return wordNeedsAccent(wordChars);
            }

            /**
             * Invalidate both memoization caches for the given raw text value.
             * Call this after programmatically changing a cell's content so that the
             * next render reflects the updated accent state.
             *
             * @param {string} rawText
             */
            function invalidateCache(rawText) {
                _buildHtmlCache.delete(rawText);
                _cellNeedsAccentCache.delete(rawText);
            }

            return {
                buildHtml,
                handleClick,
                cellNeedsAccent,
                invalidateCache
            };

        }());
    </script>
@endonce
