# Project Context

## What this application does

This is a web-based Anki flashcard creation tool specialized for French → Russian language learning. Users import or create CSV files, edit them in a Livewire-powered spreadsheet editor, apply AI operations (translation, Russian stress-mark correction, text-to-speech audio generation), and export Anki-compatible `.apkg` packages ready to import into Anki Desktop or AnkiDroid.

## Domain concepts

- **CsvDraft**: A user's saved file/deck. Stored in the `csv_drafts` table as a JSON array of rows. Each user can have multiple drafts open simultaneously.
- **Row**: A single flashcard, stored as a 2-element array `[sourceText, russianText]`. Index 0 is the source language (typically French), index 1 is Russian. There is **no header row** in the CSV.
- **Stress mark / accent**: Russian stressed vowels are marked internally with `<b>vowel</b>` HTML tags (e.g. `при<b>в</b>ет`). This is the **canonical internal format** throughout the codebase. Only words with ≥ 2 vowels are tagged; `ё` is always stressed.
- **TTS (Text-to-Speech)**: MP3 audio files generated via OpenAI for the Russian column of a card. Cached on disk at `storage/app/tts/{sha256hash}.mp3`. The hash is computed from the normalized phrase (stress tags stripped), so the same spoken phrase always maps to the same file.
- **Mass operation**: A batch process that dispatches one `MassOperationJob` per qualifying row for either stress correction (`OperationType::Stress`) or TTS generation (`OperationType::Tts`). Progress is tracked via Laravel Cache and broadcast in real time via Laravel Reverb WebSockets (`MassOperationProgressEvent`).
- **Anki package (.apkg)**: ZIP archive containing `collection.anki2` (SQLite database) and media files. Built by `AnkiPackageExporterService`. The format follows the Anki database spec strictly.
- **OpenAI key**: Each user provides their own OpenAI API key, stored encrypted in the `users.openai_api_key` column. It is injected at request time via the `SetUserOpenAiKey` middleware. All AI operations require it.

## Key workflows

1. **Upload or create a file** → CSV is parsed via `fgetcsv`, accent formats normalized, rows saved as a new `CsvDraft` (auto-saved on every change).
2. **Edit rows** → inline cell editing, add/delete rows, search and filter (by text, missing stress marks, missing audio).
3. **Single-row translation** → `ManagesTranslation` calls `OpenAiTranslationService` → `SourceToRussianTranslatorAgent` (gpt-5.4) to translate the source cell into Russian with stress marks.
4. **Generate new rows** → `ManagesRowGeneration` calls `RowGenerationService` → `RowGeneratorAgent` (gpt-5.4, temperature 0.8) to produce N new source/Russian pairs on a given topic.
5. **Mass stress correction** → `MassOperationService::dispatchStressBatch()` dispatches one `MassOperationJob` per row needing correction → `RussianStressCorrectorAgent` (gpt-5.4, temperature 0) reviews and corrects stress tags.
6. **Mass TTS generation** → `MassOperationService::dispatchTtsBatch()` dispatches one `MassOperationJob` per row without cached audio → `RussianTextToSpeechService` generates and caches the MP3.
7. **Export** → `.csv` download (accent style applied: color, bold, unicode combining accent), single-deck `.apkg`, or multi-deck `.apkg` collection (all user drafts combined).
8. **Test mode** → `ManagesTestMode` shuffles all rows with non-empty source and Russian text into a queue, then lets the user flip each card (front = source, back = Russian + audio). No SM-2, no score tracking.

## Architecture notes

- `CsvEditor` is the main Livewire 4 component, decomposed into concern traits:
  - `ManagesMassOperations` — batch dispatch, progress polling, cancellation
  - `ManagesPersistence` — CsvDraft CRUD, draft switching, auto-save
  - `ManagesRowGeneration` — AI row generation modal
  - `ManagesTranslation` — single-cell and single-row translation
  - `ManagesTtsAudio` — TTS modal, individual audio generation/deletion
  - `ManagesTestMode` — simple test mode: shuffles all non-empty rows, shows source text, user flips to reveal Russian + audio, advances to next card. No spaced repetition, no progress persistence.
- Services are injected in `boot()` (not `__construct`) because Livewire does not serialize protected properties between requests. Never move them to the constructor.
- Mass operation progress is tracked in Laravel Cache under keys `mass_op:{sessionId}:{operationType}:{counter}` (e.g. `mass_op:abc123:tts:processed`). The session ID is a per-batch UUID generated at dispatch time.
- TTS audio is stored on the `local` disk (not `public`). It is embedded into `.apkg` exports as binary media entries.
- The application is multilingual; locale is switched via `LocaleController` and applied by `SetApplicationLocale` middleware.
- Users must verify their email (`MustVerifyEmail`).
- API usage (tokens, characters) is logged in the `api_usage_logs` table per operation type.
- A `DebugAiErrorSwitch` Livewire component (dev-only) forces AI errors to test error-handling paths.

## Business rules & constraints

- **Stress mark format** — the canonical internal representation is `<b>vowel</b>`. All services, agents, and the CSV parser read and write this format. On export, the user's accent style preference (color `#RRGGBB`, bold, unicode U+0301) is applied by `applyAccentStyle()`, but the stored value is never changed.
- **Row structure** — column 0 is always source language, column 1 is always Russian. The column count is inferred from the first row; all subsequent rows are padded or truncated to match.
- **TTS hash** — the MP3 filename is the SHA-256 of the Russian text with `<b>`/`</b>` stripped and whitespace trimmed. Changing stress marks does not invalidate the cached audio.
- **OpenAI key required** — all AI actions (translate, generate, stress correct, TTS) check `apiKeyMissing()` first and open the key setup modal if absent.
- **Qualifying rows for stress** — a row qualifies for mass stress correction only when `RussianAccentService::textNeedsStressCorrection()` returns true (at least one Cyrillic word with ≥ 2 vowels has no stress tag).
- **Qualifying rows for TTS** — a row qualifies only when its Russian text is non-empty and no cached MP3 exists for it.
- All AI agents use `gpt-5.4`.

## UI conventions

- **No em dashes in views** — never use `—` (U+2014) in Blade templates. Use a simple hyphen `-` as a null/empty placeholder in tables, and plain punctuation (`:`, `,`, `.`) elsewhere. Em dashes feel unnatural in a web UI context.

## Out of scope

- Do not change the `.apkg` export format (SQLite schema, media JSON map, ZIP structure) — it must remain compatible with Anki Desktop and AnkiDroid.
- Do not change the internal stress mark encoding (`<b>vowel</b>`) — it is assumed everywhere in services, agents, and client-side Alpine.js code.
- Do not move service injection from `boot()` to `__construct()` in `CsvEditor`.
- Do not change queue or broadcast driver configuration without approval.
