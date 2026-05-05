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
            return '<span style="color:#a1a1aa">—</span>';
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
                    if (singleSyllable.has(pos)) {
                        // Single-syllable word: show accent state but no click interaction.
                        const cssClass = seg.bold ? 'rv-vowel-accented' : '';
                        result += cssClass ? `<span class="${cssClass}">${ch}</span>` : ch;
                    } else {
                        // Multi-syllable word: render as a clickable, colour-coded vowel span.
                        const cssClass = seg.bold ? 'rv-vowel-accented' : 'rv-vowel';
                        result += `<span class="${cssClass}" data-vowel-pos="${pos}">${ch}</span>`;
                    }
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
     * Handle a click inside an accent-mode cell display element.
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
            // Highlight only when there are > 2 vowels and no accent yet.
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

    return { buildHtml, handleClick, cellNeedsAccent, invalidateCache };

}());

