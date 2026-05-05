<?php

namespace App\Services;

/**
 * Pure utility service for manipulating Russian stress-mark tags (<b>…</b>)
 * in Cyrillic text. Contains no I/O or framework dependencies.
 */
class RussianAccentService
{
    private const RUSSIAN_VOWELS = 'аеёиоуыэюяАЕЁИОУЫЭЮЯ';

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

        $i = 0;
        $length = mb_strlen($rawText);

        while ($i < $length) {
            $remaining = mb_substr($rawText, $i);

            if (str_starts_with($remaining, '<b>')) {
                $inBold = true;
                $i += 3;

                continue;
            }

            if (str_starts_with($remaining, '</b>')) {
                $inBold = false;
                $i += 4;

                continue;
            }

            $char = mb_substr($rawText, $i, 1);

            if ($this->isCyrillicChar($char)) {
                $inCyrillicWord = true;

                if (mb_strpos(self::RUSSIAN_VOWELS, $char) !== false) {
                    $wordTotalVowels++;

                    if ($inBold) {
                        $wordAccentedVowels++;
                    }
                }
            } else {
                // Non-Cyrillic character: word boundary — evaluate the completed word.
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
        $plainLength = mb_strlen($plainText);

        if ($charPosition < 0 || $charPosition >= $plainLength) {
            return $rawText;
        }

        $targetChar = mb_substr($plainText, $charPosition, 1);

        // Reject non-vowel positions silently.
        if (mb_strpos(self::RUSSIAN_VOWELS, $targetChar) === false) {
            return $rawText;
        }

        // Find the Cyrillic word [wordStart, wordEnd) that contains charPosition.
        $wordStart = $charPosition;
        while ($wordStart > 0 && $this->isCyrillicChar(mb_substr($plainText, $wordStart - 1, 1))) {
            $wordStart--;
        }

        $wordEnd = $charPosition + 1;
        while ($wordEnd < $plainLength && $this->isCyrillicChar(mb_substr($plainText, $wordEnd, 1))) {
            $wordEnd++;
        }

        // Rebuild the raw string segment by segment:
        // – Characters inside the target word: strip any existing bold, place bold
        //   only on the clicked vowel.
        // – Characters outside the target word: preserve their original bold state.
        $result = '';
        $plainPos = 0;

        foreach ($this->parseRawTextSegments($rawText) as ['text' => $segText, 'bold' => $segBold]) {
            $segLength = mb_strlen($segText);

            for ($j = 0; $j < $segLength; $j++) {
                $ch = mb_substr($segText, $j, 1);
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
     */
    private function isCyrillicChar(string $char): bool
    {
        return preg_match('/[а-яёА-ЯЁ]/u', $char) === 1;
    }
}
