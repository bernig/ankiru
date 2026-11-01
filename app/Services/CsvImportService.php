<?php

namespace App\Services;

/**
 * Turns the raw bytes of an uploaded CSV file into rows of strings.
 *
 * Spreadsheet exports vary a lot: Excel in a French locale writes `;`, a
 * "Unicode text" export is UTF-16 with tabs, older tools write a legacy
 * code page (Windows-1251 for Russian, Windows-1252 for French), and most
 * add a byte order mark. All of these are normalised to UTF-8 rows
 * here, so the editor only ever deals with one shape.
 */
class CsvImportService
{
    /** @var list<string> Delimiters tried, in order of preference on a tie. */
    private const array CANDIDATE_DELIMITERS = [',', ';', "\t"];

    /** Number of non-empty lines inspected to guess the delimiter. */
    private const int DELIMITER_SAMPLE_LINES = 20;

    /**
     * @return array<int, array<int, string>>|false False when no row could be read.
     */
    public function parse(string $content): array|false
    {
        $content = $this->toUtf8($content);
        $delimiter = $this->detectDelimiter($content);

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return false;
        }

        try {
            fwrite($handle, $content);
            rewind($handle);

            $rows = [];

            while (($row = fgetcsv($handle, separator: $delimiter, escape: '\\')) !== false) {
                // fgetcsv returns [null] for completely blank lines: skip them.
                if ($row === [null]) {
                    continue;
                }

                $rows[] = array_map('strval', $row);
            }

            return count($rows) > 0 ? $rows : false;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Decode UTF-16 (detected by its byte order mark) and legacy code pages
     * to UTF-8, and drop the UTF-8 byte order mark.
     */
    private function toUtf8(string $content): string
    {
        if (str_starts_with($content, "\xFF\xFE")) {
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        }

        if (str_starts_with($content, "\xFE\xFF")) {
            return mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            return mb_convert_encoding($content, 'UTF-8', $this->guessLegacyCodePage($content));
        }

        return $content;
    }

    /**
     * No single legacy code page holds both French accents and Cyrillic, so a
     * non-UTF-8 file is either Russian (Windows-1251) or Latin (Windows-1252).
     * In Windows-1251 every Cyrillic letter is a byte from 0xC0 up, so Russian
     * words show up as runs of such bytes; French text almost never has three
     * accented letters in a row.
     */
    private function guessLegacyCodePage(string $content): string
    {
        return preg_match('/[\xC0-\xFF]{3,}/', $content) === 1 ? 'Windows-1251' : 'Windows-1252';
    }

    /**
     * Pick the delimiter that splits the first lines into the most consistent
     * number of columns (at least two). Sentences often contain commas, so a
     * delimiter only wins when most lines agree on the same column count.
     * Falls back to a comma for single-column files.
     */
    private function detectDelimiter(string $content): string
    {
        $sampleLines = array_slice(
            array_values(array_filter(preg_split('/\R/', $content) ?: [], fn (string $line): bool => trim($line) !== '')),
            0,
            self::DELIMITER_SAMPLE_LINES,
        );

        $bestDelimiter = ',';
        $bestScore = 0;

        foreach (self::CANDIDATE_DELIMITERS as $delimiter) {
            $columnCounts = array_map(fn (string $line): int => count(str_getcsv($line, $delimiter, escape: '\\')), $sampleLines);
            $linesPerColumnCount = array_count_values(array_filter($columnCounts, fn (int $count): bool => $count >= 2));

            if ($linesPerColumnCount === []) {
                continue;
            }

            $score = max($linesPerColumnCount);

            if ($score > $bestScore) {
                $bestDelimiter = $delimiter;
                $bestScore = $score;
            }
        }

        return $bestDelimiter;
    }
}
