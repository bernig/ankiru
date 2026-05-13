<?php

namespace App\Services;

/**
 * Pure utility service for manipulating Russian stress-mark tags (<b>…</b>)
 * in Cyrillic text. Contains no I/O or framework dependencies.
 */
class RussianAccentService
{
    /**
     * Hash-keyed set of all Russian vowels for O(1) membership checks inside
     * tight character-scanning loops. Equivalent to the string 'аеёиоуыэюяАЕЁИОУЫЭЮЯ'
     * but avoids the O(n) linear scan of mb_strpos on every character.
     */
    private const array RUSSIAN_VOWEL_SET = [
        'а' => true, 'е' => true, 'ё' => true, 'и' => true, 'о' => true,
        'у' => true, 'ы' => true, 'э' => true, 'ю' => true, 'я' => true,
        'А' => true, 'Е' => true, 'Ё' => true, 'И' => true, 'О' => true,
        'У' => true, 'Ы' => true, 'Э' => true, 'Ю' => true, 'Я' => true,
    ];

    /**
     * Return true when the given Russian text contains at least one Cyrillic word
     * with two or more vowels that has no <b>…</b> stress mark.
     *
     * Mirrors the JS `cellNeedsAccent` logic so the server-side condition stays
     * consistent with what Alpine highlights in the UI, and matches the ≥2-vowel
     * rule used by the AI agents.
     */
    public function textNeedsStressCorrection(string $rawText): bool
    {
        if (empty(trim($rawText))) {
            return false;
        }

        $inBold = false;
        $wordTotalVowels = 0;
        $wordAccentedVowels = 0;
        $inCyrillicWord = false;

        /**
         * Evaluate the current word and reset per-word counters.
         * Returns true when the word needs a stress mark.
         */
        $flushWord = function () use (&$wordTotalVowels, &$wordAccentedVowels, &$inCyrillicWord): bool {
            $needsAccent = $inCyrillicWord && $wordTotalVowels > 1 && $wordAccentedVowels === 0;
            $wordTotalVowels = 0;
            $wordAccentedVowels = 0;
            $inCyrillicWord = false;

            return $needsAccent;
        };

        // Pre-split once so each character access is O(1) instead of O(i).
        $chars = mb_str_split($rawText);
        $length = count($chars);
        $i = 0;

        while ($i < $length) {
            $char = $chars[$i];

            if ($char === '<') {
                if ($i + 2 < $length && $chars[$i + 1] === 'b' && $chars[$i + 2] === '>') {
                    $inBold = true;
                    $i += 3;

                    continue;
                }

                if ($i + 3 < $length && $chars[$i + 1] === '/' && $chars[$i + 2] === 'b' && $chars[$i + 3] === '>') {
                    $inBold = false;
                    $i += 4;

                    continue;
                }
            }

            if ($this->isCyrillicChar($char)) {
                $inCyrillicWord = true;

                if (isset(self::RUSSIAN_VOWEL_SET[$char])) {
                    $wordTotalVowels++;

                    if ($inBold) {
                        $wordAccentedVowels++;
                    }
                }
            } else {
                // Non-Cyrillic character: word boundary, evaluate the completed word.
                if ($flushWord()) {
                    return true;
                }
            }

            $i++;
        }

        // Evaluate any trailing word after the loop ends.
        return $flushWord();
    }

    /**
     * Ensure that every ё (or Ё) inside a Cyrillic word with ≥2 vowels is
     * wrapped in <b>…</b>, stripping any competing bold tag on other vowels of
     * that same word.  Words whose only vowel is ё (e.g. "всё", "ёж") are left
     * without a tag because the ≥2-vowel threshold is not met.
     *
     * This is used as a deterministic post-processing step after AI correction
     * because LLMs occasionally omit the tag on ё even when explicitly instructed.
     */
    public function normalizeYoAccent(string $rawText): string
    {
        $plainText = str_replace(['<b>', '</b>'], '', $rawText);
        // Pre-split once so each character access is O(1) instead of O(i).
        $plainChars = mb_str_split($plainText);
        $plainLength = count($plainChars);

        $currentRawText = $rawText;
        $i = 0;

        while ($i < $plainLength) {
            if (! $this->isCyrillicChar($plainChars[$i])) {
                $i++;

                continue;
            }

            // Scan forward to find the full extent of this Cyrillic word.
            $wordStart = $i;
            $wordEnd = $i;
            while ($wordEnd < $plainLength && $this->isCyrillicChar($plainChars[$wordEnd])) {
                $wordEnd++;
            }

            // Collect vowel count and the position of the first ё/Ё in the word.
            $vowelCount = 0;
            $yoAbsolutePosition = -1;

            for ($j = $wordStart; $j < $wordEnd; $j++) {
                $wordChar = $plainChars[$j];

                if (isset(self::RUSSIAN_VOWEL_SET[$wordChar])) {
                    $vowelCount++;
                }

                if (($wordChar === 'ё' || $wordChar === 'Ё') && $yoAbsolutePosition === -1) {
                    $yoAbsolutePosition = $j;
                }
            }

            // Only fix words that qualify for a stress mark and contain ё.
            if ($vowelCount >= 2 && $yoAbsolutePosition !== -1) {
                // moveAccentToPosition strips bold from the whole word then places
                // it only on the target vowel, exactly what we need here.
                $currentRawText = $this->moveAccentToPosition($currentRawText, $yoAbsolutePosition);
            }

            // Advance past the current word.
            $i = $wordEnd;
        }

        return $currentRawText;
    }

    /**
     * Move the <b> accent marker to the Russian vowel at the given plain-text
     * character position within a cell, preserving accent marks on all other words.
     *
     * @param  string  $rawText  Cell content possibly containing <b>…</b> tags.
     * @param  int  $charPosition  0-based Unicode character index in stripped text.
     */
    public function moveAccentToPosition(string $rawText, int $charPosition): string
    {
        // Strip tags to build the plain text used for validation and word detection.
        $plainText = str_replace(['<b>', '</b>'], '', $rawText);
        // Pre-split once so each character access is O(1) instead of O(i).
        $plainChars = mb_str_split($plainText);
        $plainLength = count($plainChars);

        if ($charPosition < 0 || $charPosition >= $plainLength) {
            return $rawText;
        }

        // Reject non-vowel positions silently.
        if (! isset(self::RUSSIAN_VOWEL_SET[$plainChars[$charPosition]])) {
            return $rawText;
        }

        // Find the Cyrillic word [wordStart, wordEnd) that contains charPosition.
        $wordStart = $charPosition;
        while ($wordStart > 0 && $this->isCyrillicChar($plainChars[$wordStart - 1])) {
            $wordStart--;
        }

        $wordEnd = $charPosition + 1;
        while ($wordEnd < $plainLength && $this->isCyrillicChar($plainChars[$wordEnd])) {
            $wordEnd++;
        }

        // Rebuild the raw string segment by segment:
        // – Characters inside the target word: strip any existing bold, place bold
        //   only on the clicked vowel.
        // – Characters outside the target word: preserve their original bold state.
        $result = '';
        $plainPos = 0;

        foreach ($this->parseRawTextSegments($rawText) as ['text' => $segText, 'bold' => $segBold]) {
            // Pre-split each segment for O(1) character access.
            $segChars = mb_str_split($segText);
            $segLength = count($segChars);

            for ($j = 0; $j < $segLength; $j++) {
                $ch = $segChars[$j];
                $pos = $plainPos + $j;

                if ($pos === $charPosition) {
                    $result .= '<b>'.$ch.'</b>';
                } elseif ($pos >= $wordStart && $pos < $wordEnd) {
                    $result .= $ch;
                } else {
                    $result .= $segBold ? '<b>'.$ch.'</b>' : $ch;
                }
            }

            $plainPos += $segLength;
        }

        return $result;
    }

    /**
     * Normalise a cell value that may carry accent markers in any of the formats
     * this application can produce (or that external sources may use), converting
     * them all to the canonical <b>vowel</b> internal representation:
     *
     *   <font color="…"><b>X</b></font>  →  <b>X</b>   (colour + bold export)
     *   <b><font color="…">X</font></b>  →  <b>X</b>   (bold-outer / Anki re-export)
     *   <font color="…">X</font>          →  <b>X</b>   (colour-only export)
     *   X + U+0301 (combining acute)      →  <b>X</b>   (unicode export / external)
     *   <b>X</b>                          →  <b>X</b>   (already canonical, untouched)
     *
     * Safe to call on non-Russian text; the patterns are specific enough that
     * they will not transform ordinary Latin or punctuation content.
     */
    public function normalizeImportedCellValue(string $rawText): string
    {
        // Strip <font> wrappers. Handle both tag-order variants before the
        // generic <font> pass so it cannot double-wrap an already-bold payload.
        $text = preg_replace(
            '/<font[^>]*><b>(.*?)<\/b><\/font>/us',
            '<b>$1</b>',
            $rawText,
        ) ?? $rawText;

        $text = preg_replace(
            '/<b><font[^>]*>(.*?)<\/font><\/b>/us',
            '<b>$1</b>',
            $text,
        ) ?? $text;

        $text = preg_replace(
            '/<font[^>]*>(.*?)<\/font>/us',
            '<b>$1</b>',
            $text,
        ) ?? $text;

        // Convert vowel + U+0301 (combining acute accent) to <b>vowel</b>.
        $text = preg_replace(
            '/([аеёиоуыэюяАЕЁИОУЫЭЮЯ])\x{0301}/u',
            '<b>$1</b>',
            $text,
        ) ?? $text;

        // Safety net: collapse any double-nested <b> tags produced by unusual
        // source formatting (e.g. Anki re-ordering bold/font wrappers).
        return preg_replace('/<b>(<b>.*?<\/b>)<\/b>/us', '$1', $text) ?? $text;
    }

    /**
     * Parse a raw text string with <b>…</b> tags into flat segments.
     *
     * @return array<int, array{text: string, bold: bool}>
     */
    private function parseRawTextSegments(string $rawText): array
    {
        $segments = [];
        $isBold = false;

        $parts = preg_split('/(<\/?b>)/', $rawText, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        foreach ($parts as $part) {
            if ($part === '<b>') {
                $isBold = true;
            } elseif ($part === '</b>') {
                $isBold = false;
            } elseif ($part !== '') {
                $segments[] = ['text' => $part, 'bold' => $isBold];
            }
        }

        return $segments;
    }

    /**
     * Return true when the given single character is a Cyrillic letter.
     *
     * Uses Unicode code-point range checks instead of a regex to avoid
     * preg_match overhead inside tight character-scanning loops.
     * Ranges: А–Я = U+0410–U+042F, а–я = U+0430–U+044F, Ё = U+0401, ё = U+0451.
     */
    private function isCyrillicChar(string $char): bool
    {
        $cp = mb_ord($char, 'UTF-8');

        return ($cp >= 0x0410 && $cp <= 0x044F) || $cp === 0x0401 || $cp === 0x0451;
    }
}
