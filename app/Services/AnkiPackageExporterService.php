<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Generates Anki-compatible .apkg export packages for AnkiDroid and Anki Desktop.
 *
 * The .apkg format is a ZIP archive containing:
 *   - collection.anki2  : SQLite database with all deck/model/note/card rows
 *   - media             : JSON map from numeric string → original filename
 *   - 0, 1, 2, …       : media files stored under their numeric index name
 *
 * @see https://github.com/ankidroid/Anki-Android/wiki/Database-Structure
 */
class AnkiPackageExporterService
{
    /**
     * Arbitrary but stable model (note-type) identifier.
     * Must be a large integer; Anki uses epoch-millisecond style integers.
     */
    private const MODEL_ID = 1715000000000;

    /**
     * Arbitrary but stable deck identifier.
     * Must differ from the default deck ID (1) and from the model ID.
     */
    private const DECK_ID = 1715000000001;

    /**
     * Build a .apkg file from the given flashcard data and return its absolute path.
     * The caller is responsible for deleting the file after streaming it.
     *
     * @param  array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}>  $cards
     *                                                                                                                        Each element represents one flashcard:
     *                                                                                                                        - front          : question side (French text, plain)
     *                                                                                                                        - back           : answer side (Russian plain text + optional Anki sound tag)
     *                                                                                                                        - mp3StoragePath : storage-relative path like "tts/hash.mp3", or null
     *                                                                                                                        - mp3FileName    : original filename like "hash.mp3", or null
     * @return string Absolute filesystem path to the generated .apkg file.
     *
     * @throws RuntimeException when the ZIP archive cannot be created.
     */
    public function export(array $cards, string $deckName): string
    {
        $temporaryDbPath = sys_get_temp_dir().'/'.uniqid('anki_col_', true).'.anki2';
        $apkgPath = sys_get_temp_dir().'/'.uniqid('anki_pkg_', true).'.apkg';

        try {
            $this->buildCollectionDatabase($temporaryDbPath, $cards, $deckName);
            $this->assembleApkgZip($apkgPath, $temporaryDbPath, $cards);
        } finally {
            // Always clean up the temporary SQLite file, even on failure.
            @unlink($temporaryDbPath);
        }

        return $apkgPath;
    }

    /**
     * Build a .colpkg file containing multiple decks and return its absolute path.
     * The caller is responsible for deleting the file after streaming it.
     *
     * @param  array<int, array{deckName: string, cards: array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}>}>  $decks
     * @return string Absolute filesystem path to the generated .colpkg file.
     *
     * @throws RuntimeException when the ZIP archive cannot be created.
     */
    public function exportCollection(array $decks): string
    {
        $temporaryDbPath = sys_get_temp_dir().'/'.uniqid('anki_col_', true).'.anki2';
        $colpkgPath = sys_get_temp_dir().'/'.uniqid('anki_pkg_', true).'.colpkg';

        $deckConfigs = [];
        foreach (array_values($decks) as $index => $deck) {
            $deckConfigs[] = [
                'id' => self::DECK_ID + 1 + $index,
                'name' => $deck['deckName'],
                'cards' => $deck['cards'],
            ];
        }

        try {
            $this->buildMultiDeckDatabase($temporaryDbPath, $deckConfigs);
            $allCards = array_merge(...array_column($deckConfigs, 'cards'));
            $this->assembleApkgZip($colpkgPath, $temporaryDbPath, $allCards);
        } finally {
            @unlink($temporaryDbPath);
        }

        return $colpkgPath;
    }

    // -------------------------------------------------------------------------
    // SQLite collection database
    // -------------------------------------------------------------------------

    /**
     * Create and populate the collection.anki2 SQLite database.
     *
     * @param  array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}>  $cards
     */
    private function buildCollectionDatabase(string $dbPath, array $cards, string $deckName): void
    {
        $pdo = new PDO('sqlite:'.$dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createSchema($pdo);
        $this->insertCollectionRow($pdo, $deckName);
        $this->insertNotesAndCards($pdo, $cards);
    }

    /**
     * Create all Anki-required tables and indexes in the given PDO connection.
     */
    private function createSchema(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS col (
                id    integer PRIMARY KEY,
                crt   integer NOT NULL,
                mod   integer NOT NULL,
                scm   integer NOT NULL,
                ver   integer NOT NULL,
                dty   integer NOT NULL,
                usn   integer NOT NULL,
                ls    integer NOT NULL,
                conf  text    NOT NULL,
                models text   NOT NULL,
                decks  text   NOT NULL,
                dconf  text   NOT NULL,
                tags   text   NOT NULL
            );

            CREATE TABLE IF NOT EXISTS notes (
                id    integer PRIMARY KEY,
                guid  text    NOT NULL,
                mid   integer NOT NULL,
                mod   integer NOT NULL,
                usn   integer NOT NULL,
                tags  text    NOT NULL,
                flds  text    NOT NULL,
                sfld  integer NOT NULL,
                csum  integer NOT NULL,
                flags integer NOT NULL,
                data  text    NOT NULL
            );

            CREATE TABLE IF NOT EXISTS cards (
                id     integer PRIMARY KEY,
                nid    integer NOT NULL,
                did    integer NOT NULL,
                ord    integer NOT NULL,
                mod    integer NOT NULL,
                usn    integer NOT NULL,
                type   integer NOT NULL,
                queue  integer NOT NULL,
                due    integer NOT NULL,
                ivl    integer NOT NULL,
                factor integer NOT NULL,
                reps   integer NOT NULL,
                lapses integer NOT NULL,
                left   integer NOT NULL,
                odue   integer NOT NULL,
                odid   integer NOT NULL,
                flags  integer NOT NULL,
                data   text    NOT NULL
            );

            CREATE TABLE IF NOT EXISTS revlog (
                id      integer PRIMARY KEY,
                cid     integer NOT NULL,
                usn     integer NOT NULL,
                ease    integer NOT NULL,
                ivl     integer NOT NULL,
                lastIvl integer NOT NULL,
                factor  integer NOT NULL,
                time    integer NOT NULL,
                type    integer NOT NULL
            );

            CREATE TABLE IF NOT EXISTS graves (
                usn  integer NOT NULL,
                oid  integer NOT NULL,
                type integer NOT NULL
            );

            CREATE INDEX IF NOT EXISTS ix_notes_usn   ON notes (usn);
            CREATE INDEX IF NOT EXISTS ix_cards_usn   ON cards (usn);
            CREATE INDEX IF NOT EXISTS ix_cards_nid   ON cards (nid);
            CREATE INDEX IF NOT EXISTS ix_cards_sched ON cards (did, queue, due);
            CREATE INDEX IF NOT EXISTS ix_revlog_usn  ON revlog (usn);
            CREATE INDEX IF NOT EXISTS ix_revlog_cid  ON revlog (cid);
        SQL);
    }

    /**
     * Insert the single `col` row that defines the deck, model (note type),
     * scheduler configuration, and collection-level settings.
     */
    private function insertCollectionRow(PDO $pdo, string $deckName): void
    {
        $now = time();
        // scm (schema modification time) is stored in milliseconds.
        $schemaModTime = $now * 1000;

        $conf = json_encode([
            'nextPos' => 1,
            'estTimes' => true,
            'activeDecks' => [self::DECK_ID],
            'sortType' => 'noteFld',
            'timeLim' => 0,
            'sortBackwards' => false,
            'addToCur' => true,
            'curDeck' => self::DECK_ID,
            'newBury' => true,
            'newSpread' => 0,
            'dueCounts' => true,
            'curModel' => (string) self::MODEL_ID,
            'collapseTime' => 1200,
        ]);

        $models = json_encode([
            (string) self::MODEL_ID => [
                'id' => self::MODEL_ID,
                'name' => $deckName,
                'type' => 0,
                'mod' => $now,
                'usn' => -1,
                'sortf' => 0,
                'did' => self::DECK_ID,
                'tmpls' => [
                    [
                        'name' => 'Card 1',
                        'ord' => 0,
                        'qfmt' => '{{Front}}',
                        'afmt' => '{{FrontSide}}<hr id=answer>{{Back}}',
                        'bqfmt' => '',
                        'bafmt' => '',
                        'did' => null,
                        'bfont' => '',
                        'bsize' => 0,
                    ],
                ],
                'flds' => [
                    ['name' => 'Front', 'ord' => 0, 'sticky' => false, 'rtl' => false, 'font' => 'Arial', 'size' => 20, 'media' => []],
                    ['name' => 'Back',  'ord' => 1, 'sticky' => false, 'rtl' => false, 'font' => 'Arial', 'size' => 20, 'media' => []],
                ],
                'css' => '.card { font-family: arial; font-size: 20px; text-align: center; color: black; background-color: white; }',
                'latexPre' => "\\documentclass[12pt]{article}\n\\special{papersize=3in,5in}\n\\usepackage[utf8]{inputenc}\n\\usepackage{amssymb,amsmath}\n\\pagestyle{empty}\n\\setlength{\\parindent}{0in}\n\\begin{document}\n",
                'latexPost' => '\\end{document}',
                'latexsvg' => false,
                'req' => [[0, 'any', [0]]],
            ],
        ]);

        $decks = json_encode([
            // Anki always expects the default deck (id=1) to be present.
            '1' => [
                'id' => 1, 'name' => 'Default', 'desc' => '', 'extendRev' => 50,
                'usn' => 0, 'collapsed' => false, 'browserCollapsed' => false,
                'newToday' => [0, 0], 'revToday' => [0, 0], 'lrnToday' => [0, 0],
                'timeToday' => [0, 0], 'dyn' => 0, 'extendNew' => 10, 'conf' => 1, 'mod' => $now,
            ],
            (string) self::DECK_ID => [
                'id' => self::DECK_ID, 'name' => $deckName, 'desc' => '', 'extendRev' => 50,
                'usn' => -1, 'collapsed' => false, 'browserCollapsed' => false,
                'newToday' => [0, 0], 'revToday' => [0, 0], 'lrnToday' => [0, 0],
                'timeToday' => [0, 0], 'dyn' => 0, 'extendNew' => 10, 'conf' => 1, 'mod' => $now,
            ],
        ]);

        $dconf = json_encode([
            '1' => [
                'id' => 1, 'name' => 'Default', 'replayq' => true, 'autoplay' => true,
                'timer' => 0, 'maxTaken' => 60, 'usn' => 0, 'mod' => $now,
                'lapse' => ['leechFails' => 8, 'delays' => [10], 'minInt' => 1, 'leechAction' => 0, 'mult' => 0.0],
                'rev' => ['perDay' => 100, 'ease4' => 1.3, 'fuzz' => 0.05, 'minSpace' => 1, 'ivlFct' => 1.0, 'maxIvl' => 36500, 'bury' => true, 'hardFactor' => 1.2],
                'new' => ['perDay' => 20, 'delays' => [1, 10], 'separate' => true, 'ints' => [1, 4, 7], 'initialFactor' => 2500, 'bury' => true, 'order' => 1],
            ],
        ]);

        $pdo->prepare('INSERT INTO col VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([1, $now, $now, $schemaModTime, 11, 0, -1, 0, $conf, $models, $decks, $dconf, '{}']);
    }

    /**
     * Insert one note row and one card row for every flashcard.
     *
     * Note IDs are epoch-millisecond integers incremented per card so they are
     * unique without requiring any external state.
     *
     * Fields are joined with ASCII unit-separator (0x1f) as required by Anki.
     *
     * @param  array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}>  $cards
     */
    private function insertNotesAndCards(PDO $pdo, array $cards): void
    {
        $now = time();
        // Start from the current millisecond, incrementing by 2 per card so note and
        // card IDs are interleaved without collisions.
        $baseNoteId = (int) (microtime(true) * 1000);

        $noteStatement = $pdo->prepare(
            'INSERT INTO notes VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $cardStatement = $pdo->prepare(
            'INSERT INTO cards VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($cards as $position => $card) {
            $noteId = $baseNoteId + ($position * 2);
            $cardId = $baseNoteId + ($position * 2) + 1;

            // Anki separates field values inside a note with the ASCII unit-separator (0x1f).
            $joinedFields = $card['front'].chr(0x1F).$card['back'];

            $noteStatement->execute([
                $noteId,
                substr(md5((string) $noteId), 0, 10),  // guid: short unique stable string
                self::MODEL_ID,
                $now,
                -1,    // usn: -1 means "pending sync"
                '',    // tags: empty
                $joinedFields,
                $card['front'],  // sfld: sort field (front/first field)
                $this->computeFieldChecksum($card['front']),
                0,     // flags
                '',    // data
            ]);

            $cardStatement->execute([
                $cardId,
                $noteId,
                self::DECK_ID,
                0,          // ord: template index (0 = first template)
                $now,
                -1,         // usn
                0,          // type: 0 = new card
                0,          // queue: 0 = new card queue
                $position,  // due: position in new-card queue (natural import order)
                0,          // ivl: interval (set by Anki on first review)
                0,          // factor: ease factor (set by Anki on graduation)
                0,          // reps
                0,          // lapses
                0,          // left
                0,          // odue
                0,          // odid
                0,          // flags
                '',         // data
            ]);
        }
    }

    /**
     * Create and populate the collection.anki2 SQLite database for multiple decks.
     *
     * @param  array<int, array{id: int, name: string, cards: array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}>}>  $deckConfigs
     */
    private function buildMultiDeckDatabase(string $dbPath, array $deckConfigs): void
    {
        $pdo = new PDO('sqlite:'.$dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createSchema($pdo);
        $this->insertCollectionRowMultiple($pdo, $deckConfigs);
        $this->insertNotesAndCardsMultiple($pdo, $deckConfigs);
    }

    /**
     * Insert the `col` row for a collection containing multiple decks.
     *
     * @param  array<int, array{id: int, name: string, cards: array}>  $deckConfigs
     */
    private function insertCollectionRowMultiple(PDO $pdo, array $deckConfigs): void
    {
        $now = time();
        $schemaModTime = $now * 1000;
        $firstDeckId = $deckConfigs[0]['id'];

        $conf = json_encode([
            'nextPos' => 1,
            'estTimes' => true,
            'activeDecks' => array_column($deckConfigs, 'id'),
            'sortType' => 'noteFld',
            'timeLim' => 0,
            'sortBackwards' => false,
            'addToCur' => true,
            'curDeck' => $firstDeckId,
            'newBury' => true,
            'newSpread' => 0,
            'dueCounts' => true,
            'curModel' => (string) self::MODEL_ID,
            'collapseTime' => 1200,
        ]);

        $models = json_encode([
            (string) self::MODEL_ID => [
                'id' => self::MODEL_ID,
                'name' => 'Basic',
                'type' => 0,
                'mod' => $now,
                'usn' => -1,
                'sortf' => 0,
                'did' => $firstDeckId,
                'tmpls' => [
                    [
                        'name' => 'Card 1',
                        'ord' => 0,
                        'qfmt' => '{{Front}}',
                        'afmt' => '{{FrontSide}}<hr id=answer>{{Back}}',
                        'bqfmt' => '',
                        'bafmt' => '',
                        'did' => null,
                        'bfont' => '',
                        'bsize' => 0,
                    ],
                ],
                'flds' => [
                    ['name' => 'Front', 'ord' => 0, 'sticky' => false, 'rtl' => false, 'font' => 'Arial', 'size' => 20, 'media' => []],
                    ['name' => 'Back',  'ord' => 1, 'sticky' => false, 'rtl' => false, 'font' => 'Arial', 'size' => 20, 'media' => []],
                ],
                'css' => '.card { font-family: arial; font-size: 20px; text-align: center; color: black; background-color: white; }',
                'latexPre' => "\\documentclass[12pt]{article}\n\\special{papersize=3in,5in}\n\\usepackage[utf8]{inputenc}\n\\usepackage{amssymb,amsmath}\n\\pagestyle{empty}\n\\setlength{\\parindent}{0in}\n\\begin{document}\n",
                'latexPost' => '\\end{document}',
                'latexsvg' => false,
                'req' => [[0, 'any', [0]]],
            ],
        ]);

        $decksJson = [
            '1' => [
                'id' => 1, 'name' => 'Default', 'desc' => '', 'extendRev' => 50,
                'usn' => 0, 'collapsed' => false, 'browserCollapsed' => false,
                'newToday' => [0, 0], 'revToday' => [0, 0], 'lrnToday' => [0, 0],
                'timeToday' => [0, 0], 'dyn' => 0, 'extendNew' => 10, 'conf' => 1, 'mod' => $now,
            ],
        ];

        foreach ($deckConfigs as $deckConfig) {
            $decksJson[(string) $deckConfig['id']] = [
                'id' => $deckConfig['id'], 'name' => $deckConfig['name'], 'desc' => '', 'extendRev' => 50,
                'usn' => -1, 'collapsed' => false, 'browserCollapsed' => false,
                'newToday' => [0, 0], 'revToday' => [0, 0], 'lrnToday' => [0, 0],
                'timeToday' => [0, 0], 'dyn' => 0, 'extendNew' => 10, 'conf' => 1, 'mod' => $now,
            ];
        }

        $dconf = json_encode([
            '1' => [
                'id' => 1, 'name' => 'Default', 'replayq' => true, 'autoplay' => true,
                'timer' => 0, 'maxTaken' => 60, 'usn' => 0, 'mod' => $now,
                'lapse' => ['leechFails' => 8, 'delays' => [10], 'minInt' => 1, 'leechAction' => 0, 'mult' => 0.0],
                'rev' => ['perDay' => 100, 'ease4' => 1.3, 'fuzz' => 0.05, 'minSpace' => 1, 'ivlFct' => 1.0, 'maxIvl' => 36500, 'bury' => true, 'hardFactor' => 1.2],
                'new' => ['perDay' => 20, 'delays' => [1, 10], 'separate' => true, 'ints' => [1, 4, 7], 'initialFactor' => 2500, 'bury' => true, 'order' => 1],
            ],
        ]);

        $pdo->prepare('INSERT INTO col VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([1, $now, $now, $schemaModTime, 11, 0, -1, 0, $conf, $models, json_encode($decksJson), $dconf, '{}']);
    }

    /**
     * Insert notes and cards for all decks in a multi-deck collection.
     *
     * @param  array<int, array{id: int, name: string, cards: array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}>}>  $deckConfigs
     */
    private function insertNotesAndCardsMultiple(PDO $pdo, array $deckConfigs): void
    {
        $now = time();
        $baseNoteId = (int) (microtime(true) * 1000);
        $position = 0;

        $noteStatement = $pdo->prepare(
            'INSERT INTO notes VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $cardStatement = $pdo->prepare(
            'INSERT INTO cards VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($deckConfigs as $deckConfig) {
            foreach ($deckConfig['cards'] as $card) {
                $noteId = $baseNoteId + ($position * 2);
                $cardId = $baseNoteId + ($position * 2) + 1;
                $joinedFields = $card['front'].chr(0x1F).$card['back'];

                $noteStatement->execute([
                    $noteId,
                    substr(md5((string) $noteId), 0, 10),
                    self::MODEL_ID,
                    $now,
                    -1,
                    '',
                    $joinedFields,
                    $card['front'],
                    $this->computeFieldChecksum($card['front']),
                    0,
                    '',
                ]);

                $cardStatement->execute([
                    $cardId,
                    $noteId,
                    $deckConfig['id'],
                    0,
                    $now,
                    -1,
                    0,
                    0,
                    $position,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    '',
                ]);

                $position++;
            }
        }
    }

    // -------------------------------------------------------------------------
    // ZIP assembly
    // -------------------------------------------------------------------------

    /**
     * Build the final .apkg ZIP archive from the SQLite database and media files.
     *
     * Media files are renamed to sequential integers inside the ZIP (Anki requirement).
     * The `media` JSON file maps these integers back to the original filenames.
     *
     * @param  array<int, array{front: string, back: string, mp3StoragePath: string|null, mp3FileName: string|null}>  $cards
     *
     * @throws RuntimeException when the ZIP archive cannot be opened.
     */
    private function assembleApkgZip(string $apkgPath, string $dbPath, array $cards): void
    {
        $zip = new ZipArchive;

        if ($zip->open($apkgPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create .apkg archive at {$apkgPath}.");
        }

        // Read the SQLite file into memory so addFromString is not affected by
        // any lazy-read ordering issues with addFile.
        $dbContent = file_get_contents($dbPath);

        if ($dbContent === false) {
            $zip->close();
            throw new RuntimeException("Could not read collection database at {$dbPath}.");
        }

        $zip->addFromString('collection.anki2', $dbContent);

        // Build the media map: "0" → "hash.mp3", "1" → "hash2.mp3", …
        // Duplicate mp3 filenames are deduplicated so the same audio is only stored once.
        /** @var array<string, string> $mediaMap numericIndex → originalMp3Filename */
        $mediaMap = [];
        $numericIndex = 0;
        $seenFileNames = [];

        foreach ($cards as $card) {
            if ($card['mp3StoragePath'] === null || $card['mp3FileName'] === null) {
                continue;
            }

            // Skip if this exact file was already added (multiple rows can share audio).
            if (isset($seenFileNames[$card['mp3FileName']])) {
                continue;
            }

            $mp3BinaryContent = Storage::disk('local')->get($card['mp3StoragePath']);

            if ($mp3BinaryContent === null) {
                continue;
            }

            // Media files inside the ZIP must be named as plain integers (no extension).
            $zip->addFromString((string) $numericIndex, $mp3BinaryContent);
            $mediaMap[(string) $numericIndex] = $card['mp3FileName'];
            $seenFileNames[$card['mp3FileName']] = true;
            $numericIndex++;
        }

        // The `media` file is required by Anki even when there are no media files.
        // json_encode with an object cast ensures "{}" for empty maps instead of "[]".
        $zip->addFromString('media', json_encode((object) $mediaMap));

        $zip->close();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Compute Anki's field checksum used for duplicate detection within a collection.
     *
     * Algorithm (mirrors Anki's Python source):
     *   int(sha1(field_value.encode("utf-8")).hexdigest()[:8], 16)
     */
    private function computeFieldChecksum(string $fieldValue): int
    {
        return (int) hexdec(substr(sha1($fieldValue), 0, 8));
    }
}
