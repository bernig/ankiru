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

/**
 * Livewire requests still waiting for their response. Clicking an export link
 * blurs the cell being edited, which sends its save right before the download
 * request: the export waits for these first, or it could read the deck
 * without that last edit.
 *
 * @type {Set<Promise<void>>}
 */
const pendingCommits = new Set();

/** Longest wait for pending saves before an export goes ahead anyway. */
const EXPORT_SAVE_WAIT_MS = 5000;

document.addEventListener('livewire:init', () => {
    Livewire.hook('commit', ({ succeed, fail }) => {
        let settle;
        const pending = new Promise(resolve => { settle = resolve; });
        pendingCommits.add(pending);

        const done = () => {
            pendingCommits.delete(pending);
            settle();
        };
        succeed(done);
        fail(done);
    });

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

    /** HTML entity replacements used by escapeHtml(). */
    const HTML_ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

    /**
     * Escape every HTML-significant character so the string renders as text.
     *
     * @param  {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
        return String(text).replace(/[&<>"']/g, ch => HTML_ESCAPES[ch]);
    }

    /**
     * Make a raw cell value safe for x-html while keeping the canonical
     * <b>…</b> stress markers: everything is escaped, then only the exact,
     * attribute-free <b> and </b> tags are restored.
     *
     * @param  {string} rawText
     * @returns {string}
     */
    function sanitizeRichText(rawText) {
        return escapeHtml(rawText ?? '').replace(/&lt;(\/?)b&gt;/g, '<$1b>');
    }

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
                                result += `<span class="rv-vowel-accented" data-vowel-pos="${pos}" tabindex="-1">${unicodeDisplay}</span>`;
                            } else {
                                // No active style: plain span retains click target and stored position.
                                result += `<span data-vowel-pos="${pos}" tabindex="-1">${ch}</span>`;
                            }
                        } else {
                            result += `<span class="rv-vowel" data-vowel-pos="${pos}" tabindex="-1">${ch}</span>`;
                        }
                    }
                } else if (seg.bold) {
                    // Non-vowel inside <b> (edge case) - preserve bold rendering.
                    result += `<b>${escapeHtml(ch)}</b>`;
                } else {
                    result += escapeHtml(ch);
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
        // The deck rows are not on the client: only the visible page (pageRows)
        // and, while test mode is open, the queued cards (testCards).
        if (type === 'cell' && secondary !== undefined) {
            for (const rows of [wire.pageRows, wire.testCards]) {
                const row = rows?.[primary];
                if (!row || row[secondary] === undefined) continue;

                const newValue = moveAccentToPosition(row[secondary], vowelPos);
                if (newValue !== row[secondary]) {
                    row[secondary] = newValue;
                }
            }
        }

        wire.placeAccentOnVowel(primary, secondary, vowelPos);
    }

    /**
     * Keyboard counterpart of handleClick(), bound on the Russian cell itself.
     *
     * The cell is a single Tab stop (tabindex="0"); its clickable vowels carry
     * tabindex="-1", so they can receive focus from script without adding one
     * Tab stop per vowel ("roving focus"). Keys:
     *   - ArrowRight / ArrowLeft: move focus to the next / previous vowel.
     *   - Enter / Space on a focused vowel: place the accent on it.
     *
     * Placing the accent re-renders the cell (x-html), which destroys the focused
     * span; focusVowel() puts focus back on the vowel at the same position.
     *
     * @param {KeyboardEvent}    event
     * @param {object}           wire
     * @param {'cell'|'header'}  type
     * @param {number}           primary
     * @param {number|undefined} secondary
     */
    function handleKeydown(event, wire, type, primary, secondary) {
        const cell = event.currentTarget;
        const vowels = [...cell.querySelectorAll('[data-vowel-pos]')];
        if (vowels.length === 0) return;

        const current = vowels.indexOf(document.activeElement);
        const last = vowels.length - 1;
        // Stop at both ends instead of wrapping: more predictable with a screen reader.
        const targets = {
            ArrowRight: current === -1 ? 0 : Math.min(current + 1, last),
            ArrowLeft: current === -1 ? last : Math.max(current - 1, 0),
            Home: 0,
            End: last,
        };

        if (event.key in targets) {
            event.preventDefault();
            vowels[targets[event.key]].focus();

            return;
        }

        if ((event.key === 'Enter' || event.key === ' ') && current !== -1) {
            event.preventDefault();
            // Read before handleClick(): the re-render destroys the focused span.
            const vowelPos = parseInt(vowels[current].dataset.vowelPos, 10);
            handleClick(event, wire, type, primary, secondary);
            focusVowel(cell, vowelPos);
        }
    }

    /**
     * Focus the vowel at plain-text position vowelPos inside cell, after
     * Alpine has re-rendered it.
     *
     * @param {HTMLElement} cell
     * @param {number}      vowelPos
     */
    function focusVowel(cell, vowelPos) {
        requestAnimationFrame(() => cell.querySelector(`[data-vowel-pos="${vowelPos}"]`)?.focus());
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

    return { buildHtml, sanitizeRichText, handleClick, handleKeydown, cellNeedsAccent, invalidateCache, setStyleMode, moveAccentToPosition };

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

/**
 * Build an export link with the browser's time zone, so the server stamps
 * the downloaded file name with the user's local time, plus any extra query
 * parameters (e.g. the collection name). Called from the link's click
 * handler, right before the browser follows it: a value bound earlier could
 * be reset to the server's plain URL when Livewire re-renders the menu.
 *
 * @param {string} url
 * @param {Object<string, string>} [params]
 * @returns {string}
 */
window.exportUrl = function (url, params = {}) {
    const query = new URLSearchParams(params);
    query.set('tz', Intl.DateTimeFormat().resolvedOptions().timeZone);

    return `${url}?${query}`;
};

/**
 * Click handler of the export links: waits for the pending Livewire saves,
 * then follows the link with the browser's time zone (see exportUrl). A
 * modifier click (new tab, new window) is left to the browser, with the time
 * zone added, since it cannot be delayed.
 *
 * @param {MouseEvent|KeyboardEvent} event
 * @param {string} url
 * @param {Object<string, string>} [params]
 */
window.followExportLink = async function (event, url, params = {}) {
    const target = window.exportUrl(url, params);

    if (event.ctrlKey || event.metaKey || event.shiftKey || event.button === 1) {
        event.currentTarget.href = target;

        return;
    }

    event.preventDefault();

    if (pendingCommits.size > 0) {
        await Promise.race([
            Promise.all(pendingCommits),
            new Promise(resolve => setTimeout(resolve, EXPORT_SAVE_WAIT_MS)),
        ]);
    }

    window.location.href = target;
};
