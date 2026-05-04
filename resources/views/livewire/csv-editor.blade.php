<div class="min-h-screen">

    {{-- ── Header bar ── --}}
    <div class="mx-auto mb-6 flex max-w-full items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:icon.table-cells class="size-7 text-zinc-500 dark:text-zinc-400" />
            <flux:heading size="xl">{{ $title ?? 'CSV Editor' }}</flux:heading>
            @if ($hasCsvLoaded && $originalFileName)
                <flux:badge class="text-xs" variant="outline">{{ $originalFileName }}</flux:badge>
            @endif
        </div>

        @if ($hasCsvLoaded)
            <div class="flex items-center gap-2">
                {{-- Russian Accent Mode toggle --}}
                <flux:button class="{{ $isRussianAccentMode ? 'ring-2 ring-amber-400 dark:ring-amber-500' : '' }}" wire:click="toggleRussianAccentMode" variant="{{ $isRussianAccentMode ? 'primary' : 'ghost' }}" icon="language">
                    🇷🇺 Accent Mode
                </flux:button>

                <flux:button wire:click="downloadCsv" icon="arrow-down-tray" variant="primary">
                    Save &amp; Download
                </flux:button>
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

        {{-- Accent mode info banner --}}
        @if ($isRussianAccentMode)
            <div class="mb-3 flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-800 dark:border-amber-600 dark:bg-amber-900/20 dark:text-amber-300">
                <flux:icon.language class="size-4 shrink-0" />
                <span>
                    <strong>Russian Accent Mode active.</strong>
                    Click any <span style="color:#666;font-weight:600">gray vowel</span> to place the stress accent there.
                    <span style="color:#d97706;font-weight:600">Amber vowels</span> already carry an accent.
                    Cells outlined in amber contain unaccented words with 3+ syllables.
                </span>
            </div>
        @endif

        <div class="flex flex-col">
            <flux:table container:class="w-full rounded border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm text-sm">
                <flux:table.rows>
                    @forelse ($this->paginatedRows as $rowIndex => $row)
                        <flux:table.row class="dark:hover:bg-zinc-750 group border-b border-zinc-100 hover:bg-zinc-50 dark:border-zinc-700" wire:key="row-{{ $rowIndex }}">
                            @foreach ($row as $columnIndex => $cellValue)
                                <flux:table.cell class="py-1! w-1/2 whitespace-normal" x-data="{ editing: false }" x-on:click="if (!$wire.isRussianAccentMode && !($wire.accentModeRowIndex === {{ $rowIndex }} && {{ $columnIndex }} === 1)) { editing = true; $nextTick(() => $refs.input.focus()) }" x-bind:class="{
                                    'cursor-text': !$wire.isRussianAccentMode && !($wire.accentModeRowIndex === {{ $rowIndex }} && {{ $columnIndex }} === 1),
                                    'bg-amber-50 dark:bg-amber-900/20 ring-1 ring-inset ring-amber-300 dark:ring-amber-600': ($wire.isRussianAccentMode || ($wire.accentModeRowIndex === {{ $rowIndex }} && {{ $columnIndex }} === 1)) && window.csvAccentMode.cellNeedsAccent($wire.csvRows[{{ $rowIndex }}]?.[{{ $columnIndex }}] ?? ''),
                                    'bg-green-50 dark:bg-green-900/20 ring-2 ring-inset ring-green-400 dark:ring-green-500': {{ $columnIndex }} === 1 && !$wire.isRussianAccentMode && $wire.accentModeRowIndex !== {{ $rowIndex }} && $wire.stressCorrectionStatus[{{ $rowIndex }}] === 'corrected',
                                }">
                                    {{-- Normal display: render <b> and other inline markup --}}
                                    <div class="px-2 text-zinc-800 dark:text-zinc-100" x-show="!editing && !$wire.isRussianAccentMode && !($wire.accentModeRowIndex === {{ $rowIndex }} && {{ $columnIndex }} === 1)" x-html="$wire.csvRows[{{ $rowIndex }}][{{ $columnIndex }}] !== undefined && $wire.csvRows[{{ $rowIndex }}][{{ $columnIndex }}] !== ''
                                            ? $wire.csvRows[{{ $rowIndex }}][{{ $columnIndex }}]
                                            : '<span class=\'text-zinc-400\'>—</span>'"></div>

                                    {{-- Accent mode display: vowels are clickable spans (global mode or per-row mode for column 1) --}}
                                    <div class="cursor-default px-2 text-zinc-800 dark:text-zinc-100" x-show="$wire.isRussianAccentMode || ($wire.accentModeRowIndex === {{ $rowIndex }} && {{ $columnIndex }} === 1)" x-html="window.csvAccentMode.buildHtml($wire.csvRows[{{ $rowIndex }}]?.[{{ $columnIndex }}] ?? '')" @click="window.csvAccentMode.invalidateCache($wire.csvRows[{{ $rowIndex }}]?.[{{ $columnIndex }}] ?? ''); window.csvAccentMode.handleClick($event, $wire, 'cell', {{ $rowIndex }}, {{ $columnIndex }})"></div>

                                    {{-- Edit input: raw text so <b> tags are visible and editable --}}
                                    <input class="mx-1 w-full rounded border border-blue-500 bg-white px-2 py-1 text-zinc-800 outline-none transition-colors dark:bg-zinc-900 dark:text-zinc-100" x-show="editing && !$wire.isRussianAccentMode && !($wire.accentModeRowIndex === {{ $rowIndex }} && {{ $columnIndex }} === 1)" x-ref="input" :value="$wire.csvRows[{{ $rowIndex }}][{{ $columnIndex }}]" @blur="window.csvAccentMode.invalidateCache($wire.csvRows[{{ $rowIndex }}]?.[{{ $columnIndex }}] ?? ''); $wire.updateCell({{ $rowIndex }}, {{ $columnIndex }}, $event.target.value); editing = false" @keydown.enter="$el.blur()" @keydown.escape="editing = false" @click.stop placeholder="—" />
                                </flux:table.cell>
                            @endforeach

                            {{-- Row actions: per-row accent mode + translate + correct-stress + delete buttons --}}
                            <flux:table.cell class="py-1! whitespace-nowrap" align="end">
                                {{-- Per-row accent mode button: toggles accent mode for the right column of this row --}}
                                @if (!empty(trim($row[1] ?? '')))
                                    <button class="{{ $accentModeRowIndex === $rowIndex ? 'text-amber-500 dark:text-amber-400' : 'text-zinc-400 opacity-0 hover:text-amber-500 group-hover:opacity-100 dark:hover:text-amber-400' }} cursor-pointer rounded p-1 transition-opacity" title="{{ $accentModeRowIndex === $rowIndex ? 'Disable accent mode for this row' : 'Enable accent mode for this row' }}" wire:click="toggleRowAccentMode({{ $rowIndex }})">
                                        <flux:icon.language />
                                    </button>
                                @endif

                                {{-- Translate button: visible when left column has text and right column is empty --}}
                                @if (!empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? '')))
                                    <button class="cursor-default cursor-pointer rounded p-1 text-violet-500 opacity-0 transition-opacity hover:text-violet-700 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100 dark:text-violet-400 dark:hover:text-violet-300" title="Translate with ChatGPT" wire:click="translateWithChatGpt({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="translateWithChatGpt({{ $rowIndex }})">
                                        <span wire:loading wire:target="translateWithChatGpt({{ $rowIndex }})">
                                            <flux:icon.arrow-path class="animate-spin" />
                                        </span>
                                        <span wire:loading.remove wire:target="translateWithChatGpt({{ $rowIndex }})">
                                            <flux:icon.sparkles />
                                        </span>
                                    </button>
                                @endif

                                {{-- Correct-stress button: visible when right column has text --}}
                                @if (!empty(trim($row[1] ?? '')))
                                    @if (($stressCorrectionStatus[$rowIndex] ?? null) === 'ok')
                                        {{-- Green checkmark: stress marks were already correct --}}
                                        <span class="inline-flex items-center p-1 text-green-500 opacity-0 group-hover:opacity-100 dark:text-green-400" title="Stress marks are correct">
                                            <flux:icon.check-circle />
                                        </span>
                                    @else
                                        <button class="cursor-pointer rounded p-1 text-violet-500 opacity-0 transition-opacity hover:text-violet-700 disabled:cursor-wait disabled:opacity-30 group-hover:opacity-100 dark:text-violet-400 dark:hover:text-violet-300" title="Fix stress marks with ChatGPT" wire:click="correctStressMarks({{ $rowIndex }})" wire:loading.attr="disabled" wire:target="correctStressMarks({{ $rowIndex }})">
                                            {{-- Spinner while correcting --}}
                                            <span wire:loading wire:target="correctStressMarks({{ $rowIndex }})">
                                                <flux:icon.arrow-path class="animate-spin" />
                                            </span>
                                            {{-- Sparkles icon when idle --}}
                                            <span wire:loading.remove wire:target="correctStressMarks({{ $rowIndex }})">
                                                <flux:icon.sparkles />
                                            </span>
                                        </button>
                                    @endif
                                @endif

                                <button class="cursor-pointer rounded p-1 text-zinc-400 opacity-0 transition-opacity hover:text-red-500 group-hover:opacity-100 dark:hover:text-red-400" title="Delete row" wire:click="deleteRow({{ $rowIndex }})">
                                    <flux:icon.trash />
                                </button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell class="px-4 py-8 text-center text-zinc-400 dark:text-zinc-500">
                                No rows yet. Click "Add row" to add one.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            {{-- Translation error banner --}}
            @if ($translationError)
                <div class="mb-3 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800 dark:border-red-600 dark:bg-red-900/20 dark:text-red-300">
                    <flux:icon.exclamation-triangle class="size-4 shrink-0" />
                    <span>{{ $translationError }}</span>
                </div>
            @endif

            {{-- ── Footer toolbar ── --}}
            <div class="mx-auto mt-4 flex w-full flex-col items-center gap-3">
                {{-- Flux pagination (shown only when there is more than one page) --}}
                @if ($this->totalPages > 1)
                    <flux:pagination class="w-full" :paginator="$this->paginatedRows" scroll-to />
                @endif

                {{-- Action row: Add row button (hidden in global accent mode) --}}
                @if (!$isRussianAccentMode)
                    <flux:button wire:click="addRow" icon="plus">
                        Add row
                    </flux:button>
                @endif
            </div>
        </div>
    @endif

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

        /* Dark-mode overrides — uses class-based dark mode (.dark ancestor) */
        :where(.dark, .dark *) .rv-vowel {
            color: #60a5fa;
        }

        :where(.dark, .dark *) .rv-vowel:hover {
            color: #93c5fd;
            background-color: #1e3a5f;
        }

        :where(.dark, .dark *) .rv-vowel-accented {
            color: #fbbf24;
        }

        :where(.dark, .dark *) .rv-vowel-accented:hover {
            color: #fde68a;
        }
    </style>
@endonce

{{-- ── JavaScript helpers for Russian accent mode ── --}}
@once
    <script>
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
