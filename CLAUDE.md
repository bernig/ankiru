<laravel-boost-guidelines>
=== .ai/project-context rules ===

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

## Architecture notes

- `CsvEditor` is the main Livewire 4 component, decomposed into concern traits:
  - `ManagesMassOperations` — batch dispatch, progress polling, cancellation
  - `ManagesPersistence` — CsvDraft CRUD, draft switching, auto-save
  - `ManagesRowGeneration` — AI row generation modal
  - `ManagesTranslation` — single-cell and single-row translation
  - `ManagesTtsAudio` — TTS modal, individual audio generation/deletion
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

## Out of scope

- Do not change the `.apkg` export format (SQLite schema, media JSON map, ZIP structure) — it must remain compatible with Anki Desktop and AnkiDroid.
- Do not change the internal stress mark encoding (`<b>vowel</b>`) — it is assumed everywhere in services, agents, and client-side Alpine.js code.
- Do not move service injection from `boot()` to `__construct()` in `CsvEditor`.
- Do not change queue or broadcast driver configuration without approval.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- laravel/ai (AI) - v0
- laravel/framework (LARAVEL) - v13
- laravel/mcp (MCP) - v0
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- livewire/flux (FLUXUI_FREE) - v2
- livewire/flux-pro (FLUXUI_PRO) - v2
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4
- laravel-echo (ECHO) - v2

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
