/**
 * Alpine store that tracks which single cell is currently in edit mode.
 *
 * Using a shared store ensures that activating edit on any row automatically
 * deactivates any previously open row, preventing multiple text fields from
 * being active at the same time.
 */
document.addEventListener('alpine:init', () => {
    Alpine.store('csvEditing', {
        rowIndex: -1,
        columnIndex: -1,
    });

    Alpine.store('csvSearch', {
        active: false,
    });

    Alpine.store('mobileEdit', {
        rowIndex: -1,
    });
});

document.addEventListener('livewire:init', () => {
    const paginationMethods = ['gotoPage', 'previousPage', 'nextPage', 'setPage'];

    Livewire.hook('commit', ({ commit, succeed }) => {
        succeed(() => {
            const isPagination = (commit.calls || []).some(
                call => paginationMethods.includes(call.method)
            );
            const isPerPageChange = commit.updates && 'perPage' in commit.updates;
            if (isPagination || isPerPageChange) {
                requestAnimationFrame(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
        });
    });
});

document.addEventListener('csv-file-switched', () => {
    const store = Alpine.store('csvSearch');
    if (store) {
        store.active = false;
    }
});

/**
 * Russian accent mode utilities.
 *
 * Exposed as window.csvAccentMode so Alpine x-html expressions and event
 * handlers can call them without needing access to the Livewire component scope.
 */
window.csvAccentMode = (function () {

    /** Set of all Russian vowels (lower- and upper-case). */
    const RUSSIAN_VOWELS = new Set('аеёиоуыэюяАЕЁИОУЫЭЮЯ');

    /** Regex that matches maximal runs of Cyrillic characters (= one Russian word). */
    const CYRILLIC_WORD_RE = /[а-яёА-ЯЁ]+/g;

    /** Regex that matches a single Cyrillic character (used for word-boundary walking). */
    const CYRILLIC_CHAR_RE = /[а-яёА-ЯЁ]/;

    /** Unicode combining acute accent (U+0301), appended to the stressed vowel in unicode mode. */
    const COMBINING_ACUTE = '́';

    /** ё and Ё are inherently stressed: never add a combining accent on top of them. */
    const YO_CHARS = new Set(['ё', 'Ё']);

    /** When true, stressed vowels include the U+0301 combining acute accent in their text content. */
    let unicodeMode = false;

    /**
     * When false (all three of color/bold/unicode are off), accented vowels in
     * multi-syllable words are rendered as plain <span data-vowel-pos> with no
     * CSS class - invisible but still clickable so the stored position is kept.
     * Single-syllable accented vowels become bare characters.
     */
    let anyStyleActive = true;

    /**
     * Memoization caches keyed by raw text → result.
     * These are pure functions so the same input always yields the same output.
     * With ~200 rows the maps stay small and avoid repeating expensive string
     * walks on every Alpine reactive tick.
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
                if (current) segments.push({ text: current, bold: false });
                current = '';
                inBold = true;
                i += 3;
            } else if (rawText.startsWith('</b>', i)) {
                if (current) segments.push({ text: current, bold: true });
                current = '';
                inBold = false;
                i += 4;
            } else {
                current += rawText[i];
                i++;
            }
        }

        if (current) segments.push({ text: current, bold: inBold });
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
     * Build a Set of plain-text character indices belonging to single-syllable
     * Russian words (words that contain exactly one vowel).
     *
     * Vowels in single-syllable words must not be rendered as interactive: there
     * is no ambiguity about which syllable carries the stress, so the user should
     * not be able to click them.
     *
     * @param  {string} plain  Plain text with <b> tags already stripped.
     * @returns {Set<number>}
     */
    function buildSingleSyllableWordPositions(plain) {
        const positions = new Set();
        CYRILLIC_WORD_RE.lastIndex = 0;
        let m;
        while ((m = CYRILLIC_WORD_RE.exec(plain)) !== null) {
            const vowelCount = [...m[0]].filter(ch => RUSSIAN_VOWELS.has(ch)).length;
            if (vowelCount <= 1) {
                for (let p = m.index; p < m.index + m[0].length; p++) {
                    positions.add(p);
                }
            }
        }
        return positions;
    }

    /**
     * Build the display HTML for a cell in Russian accent mode.
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
            return '<span style="color:#a1a1aa">-</span>';
        }

        const segments = parseSegments(rawText);
        const plain = segments.map(s => s.text).join('');
        const inWord = buildRussianWordPositions(plain);
        // Positions belonging to single-syllable words: stress is unambiguous,
        // so these vowels must not be rendered as interactive click targets.
        const singleSyllable = buildSingleSyllableWordPositions(plain);
        let result = '';
        let plainPos = 0;

        for (const seg of segments) {
            for (let j = 0; j < seg.text.length; j++) {
                const ch = seg.text[j];
                const pos = plainPos + j;

                if (RUSSIAN_VOWELS.has(ch) && inWord.has(pos)) {
                    const isAccented = seg.bold;
                    const unicodeDisplay = (isAccented && unicodeMode && !YO_CHARS.has(ch)) ? ch + COMBINING_ACUTE : ch;

                    if (singleSyllable.has(pos)) {
                        // Single-syllable word: show accent state but no click interaction.
                        if (isAccented && anyStyleActive) {
                            result += `<span class="rv-vowel-accented">${unicodeDisplay}</span>`;
                        } else {
                            result += ch;
                        }
                    } else {
                        // Multi-syllable word: render as a clickable vowel span.
                        if (isAccented) {
                            if (anyStyleActive) {
                                result += `<span class="rv-vowel-accented" data-vowel-pos="${pos}">${unicodeDisplay}</span>`;
                            } else {
                                // No active style: plain span retains click target and stored position.
                                result += `<span data-vowel-pos="${pos}">${ch}</span>`;
                            }
                        } else {
                            result += `<span class="rv-vowel" data-vowel-pos="${pos}">${ch}</span>`;
                        }
                    }
                } else if (seg.bold) {
                    // Non-vowel inside <b> (edge case) - preserve bold rendering.
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
     * Client-side mirror of RussianAccentService::moveAccentToPosition().
     *
     * Moves the <b> accent tag to the Russian vowel at the given plain-text
     * character position, stripping existing bold from the same word and
     * preserving bold on all other words.
     *
     * @param  {string} rawText
     * @param  {number} charPosition  0-based index in the stripped plain text.
     * @returns {string}
     */
    function moveAccentToPosition(rawText, charPosition) {
        const plain = rawText.replace(/<\/?b>/g, '');
        const plainChars = [...plain];
        const plainLength = plainChars.length;

        if (charPosition < 0 || charPosition >= plainLength) return rawText;
        if (!RUSSIAN_VOWELS.has(plainChars[charPosition])) return rawText;

        // Find the word boundaries [wordStart, wordEnd) around charPosition.
        let wordStart = charPosition;
        while (wordStart > 0 && CYRILLIC_CHAR_RE.test(plainChars[wordStart - 1])) wordStart--;
        let wordEnd = charPosition + 1;
        while (wordEnd < plainLength && CYRILLIC_CHAR_RE.test(plainChars[wordEnd])) wordEnd++;

        const segments = parseSegments(rawText);
        let result = '';
        let plainPos = 0;

        for (const seg of segments) {
            const segChars = [...seg.text];
            for (let j = 0; j < segChars.length; j++) {
                const ch = segChars[j];
                const pos = plainPos + j;

                if (pos === charPosition) {
                    result += '<b>' + ch + '</b>';
                } else if (pos >= wordStart && pos < wordEnd) {
                    result += ch; // strip existing bold within the target word
                } else {
                    result += seg.bold ? '<b>' + ch + '</b>' : ch;
                }
            }
            plainPos += segChars.length;
        }

        return result;
    }

    /**
     * Handle a click inside an accent-mode cell display element.
     *
     * Applies an optimistic update to the local Alpine/Livewire state immediately
     * (so the DOM reflects the new accent without waiting for the server), then
     * dispatches the Livewire action to persist and confirm the change server-side.
     *
     * @param {MouseEvent}       event
     * @param {object}           wire      Alpine $wire proxy for this component.
     * @param {'cell'|'header'}  type
     * @param {number}           primary   rowIndex (cell) or columnIndex (header).
     * @param {number|undefined} secondary columnIndex (cell only - omit for header).
     */
    function handleClick(event, wire, type, primary, secondary) {
        const target = event.target.closest('[data-vowel-pos]');
        if (!target) return;

        event.stopPropagation();

        const vowelPos = parseInt(target.dataset.vowelPos, 10);
        if (isNaN(vowelPos)) return;

        // Optimistic update: compute and apply the new accent position immediately
        // in the local Alpine/Livewire reactive state so the DOM updates instantly,
        // before the server round-trip completes. The server call below confirms and
        // persists the same change; on response the value stays identical (no flicker).
        if (type === 'cell' && secondary !== undefined) {
            const oldValue = (wire.csvRows[primary] ?? [])[secondary] ?? '';
            const newValue = moveAccentToPosition(oldValue, vowelPos);
            if (newValue !== oldValue) {
                wire.csvRows[primary][secondary] = newValue;
            }
        }

        wire.placeAccentOnVowel(primary, secondary, vowelPos);
    }

    /**
     * Return true when rawText contains at least one Russian word with more than
     * 2 vowels that carries no <b> accent mark.
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
            chars.push({ c: rawText[i], bold: inBold });
            i++;
        }

        const cyrillicRe = /[а-яёА-ЯЁ]/;
        let wordChars = [];

        const wordNeedsAccent = (wc) => {
            if (wc.length === 0) return false;
            const totalVowels = wc.filter(({ c }) => RUSSIAN_VOWELS.has(c)).length;
            const accentedVowels = wc.filter(({ c, bold }) => RUSSIAN_VOWELS.has(c) && bold).length;
            // Highlight only when there are > 1 vowel (two or more) and no accent yet.
            // Matches the ≥2-vowel rule used by the AI agents and RussianAccentService.
            return totalVowels > 1 && accentedVowels === 0;
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
     *
     * Call this after programmatically changing a cell's content so that the
     * next render reflects the updated accent state.
     *
     * @param {string} rawText
     */
    function invalidateCache(rawText) {
        _buildHtmlCache.delete(rawText);
        _cellNeedsAccentCache.delete(rawText);
    }

    /**
     * Update the accent display mode from the three user style settings.
     *
     * Clears the buildHtml cache whenever any of the three values changes,
     * because the generated HTML structure can differ (e.g. adding combining
     * acute, switching between rv-vowel-accented and a plain <span>, etc.).
     *
     * @param {string|null} color  Hex color or null.
     * @param {boolean}     bold
     * @param {boolean}     unicode
     */
    function setStyleMode(color, bold, unicode) {
        const newUnicode = !!unicode;
        const newAnyActive = !!(color || bold || unicode);

        if (unicodeMode !== newUnicode || anyStyleActive !== newAnyActive) {
            unicodeMode = newUnicode;
            anyStyleActive = newAnyActive;
            _buildHtmlCache.clear();
        }
    }

    return { buildHtml, handleClick, cellNeedsAccent, invalidateCache, setStyleMode, moveAccentToPosition };

}());

/**
 * Play an audio element as soon as it has buffered enough data.
 *
 * Calls load() then waits for the 'canplay' event on mobile where the element
 * may not be ready immediately after the src is set.
 *
 * @param {HTMLAudioElement|null} el
 */
window.playAudioWhenReady = function (el) {
    if (!el) {
        return;
    }

    el.load();

    if (el.readyState >= HTMLMediaElement.HAVE_ENOUGH_DATA) {
        el.play().catch(() => {});
    } else {
        el.addEventListener('canplaythrough', () => el.play().catch(() => {}), { once: true });
    }
};

/**
 * Apply accent style CSS custom properties to the document root.
 *
 * Called from the Blade template (x-init) with server-rendered values so
 * the correct style is set on the very first paint without a round-trip.
 * Also called directly from the style modal's save handler for instant feedback.
 *
 * @param {string|null} color   Hex color string or null (no color - bold only).
 * @param {boolean}     bold    Whether stressed vowels should be bold.
 * @param {boolean}     unicode unicode Whether stressed vowels should include the U+0301 combining acute in their text content.
 */
window.applyAccentStyle = function (color, bold, unicode) {
    const root = document.documentElement;
    root.style.setProperty('--rv-accent-color', color || 'inherit');
    root.style.setProperty('--rv-accent-bold', bold ? 'bold' : 'normal');
    window.csvAccentMode.setStyleMode(color, bold, unicode || false);
};

