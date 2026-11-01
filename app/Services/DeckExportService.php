<?php

namespace App\Services;

/**
 * Turns a deck's rows into what the editor hands to the browser: a CSV
 * string in the user's accent style, the card list consumed by
 * AnkiPackageExporterService, and a file name safe for a download header.
 */
class DeckExportService
{
    public function __construct(private readonly RussianTextToSpeechService $ttsService) {}

    /**
     * Build a CSV string from the rows (no header row).
     *
     * Stressed vowels (stored as <b>X</b>) are re-encoded using the given
     * accent style: a <font color> wrapper is added when a color is set,
     * and the <b> wrapper is kept or removed based on the bold flag.
     *
     * @param  array<int, array<int, string>>  $rows
     */
    public function toCsv(array $rows, ?string $accentColor, bool $accentBold, bool $accentUnicode): string
    {
        $buffer = fopen('php://temp', 'r+');

        if ($buffer === false) {
            return '';
        }

        $applyStyle = fn (string $cellValue): string => $this->applyAccentStyle($cellValue, $accentColor, $accentBold, $accentUnicode);

        try {
            foreach ($rows as $row) {
                fputcsv($buffer, array_map($applyStyle, $row), escape: '\\');
            }

            rewind($buffer);

            return stream_get_contents($buffer) ?: '';
        } finally {
            fclose($buffer);
        }
    }

    /**
     * Build the card data array expected by AnkiPackageExporterService
     * from the given rows, attaching cached MP3 references where available.
     *
     * Audio existence is checked once per distinct phrase via audioFilesExistBatch().
     *
     * @param  array<int, array<int, string>>  $rows
     * @param  int  $draftId  Draft the rows belong to, part of each note's stable GUID.
     * @return array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null, guid: string}>
     */
    public function toAnkiCards(array $rows, int $draftId): array
    {
        $russianTexts = array_values(array_filter(
            array_map(fn (array $row): string => $row[1] ?? '', $rows),
            fn (string $text): bool => trim($text) !== '',
        ));

        $audioExistence = $this->ttsService->audioFilesExistBatch($russianTexts);

        $cards = [];
        $occurrencesBySourceText = [];

        foreach ($rows as $row) {
            $sourceText = $row[0] ?? '';
            $sourceKey = trim($sourceText);
            $occurrence = $occurrencesBySourceText[$sourceKey] ?? 0;
            $occurrencesBySourceText[$sourceKey] = $occurrence + 1;
            $rawRussianText = $row[1] ?? '';
            $backFieldValue = trim($rawRussianText);
            $mp3StoragePath = null;
            $mp3FileName = null;

            if ($backFieldValue !== '' && ($audioExistence[$rawRussianText] ?? false)) {
                $filenameHash = $this->ttsService->buildFilenameHash($rawRussianText);
                $mp3FileName = "{$filenameHash}.mp3";
                $mp3StoragePath = "tts/{$mp3FileName}";
                $backFieldValue .= " [sound:{$mp3FileName}]";
            }

            $cards[] = [
                'front' => $sourceText,
                'back' => $backFieldValue,
                'mp3StoragePath' => $mp3StoragePath,
                'mp3FileName' => $mp3FileName,
                'guid' => AnkiPackageExporterService::noteGuid($draftId, $sourceText, $occurrence),
            ];
        }

        return $cards;
    }

    /**
     * Make a user-chosen name usable as a download file name. Symfony rejects
     * "/" and "\" in a Content-Disposition header (an exception, so a 500),
     * and control characters have no place there either; each is replaced
     * by a hyphen. The deck name inside the package keeps the original text.
     */
    public function toDownloadFileName(string $name, string $fallback): string
    {
        $safeName = trim((string) preg_replace('/[\/\\\\\x00-\x1F\x7F]+/u', '-', $name), ' -.');

        return $safeName === '' ? $fallback : $safeName;
    }

    /**
     * Re-encode <b>X</b> accent markers in a cell value using the chosen
     * export style (color and/or bold and/or combining accent).
     *
     * Possible outputs for a stressed vowel X:
     *   color + bold  → <font color="#HEX"><b>X</b></font>
     *   color only    → <font color="#HEX">X</font>
     *   bold only     → <b>X</b>  (unchanged, no color tag added)
     *
     * When multiple options are active they are combined:
     *   unicode + color + bold → <font color="#HEX"><b>X́</b></font>
     */
    private function applyAccentStyle(string $cellValue, ?string $accentColor, bool $bold, bool $unicode): string
    {
        if (! str_contains($cellValue, '<b>')) {
            return $cellValue;
        }

        $color = $accentColor
            ? htmlspecialchars($accentColor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : null;

        // Nothing to do, keep the raw <b> tags as the accent position marker.
        if (! $unicode && ! $bold && ! $color) {
            return $cellValue;
        }

        return preg_replace_callback('/<b>(.*?)<\/b>/s', static function (array $m) use ($unicode, $bold, $color): string {
            $vowel = $m[1];

            // 1. Optionally append the combining acute accent (U+0301).
            //    ё/Ё are inherently stressed, no additional mark needed.
            $content = ($unicode && $vowel !== 'ё' && $vowel !== 'Ё')
                ? $vowel."\u{0301}"
                : $vowel;

            // 2. Optionally wrap in <b>.
            if ($bold) {
                $content = "<b>{$content}</b>";
            }

            // 3. Optionally wrap in <font color>.
            if ($color) {
                $content = "<font color=\"{$color}\">{$content}</font>";
            }

            return $content;
        }, $cellValue) ?? $cellValue;
    }
}
