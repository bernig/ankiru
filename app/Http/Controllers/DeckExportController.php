<?php

namespace App\Http\Controllers;

use App\Models\CsvDraft;
use App\Services\AnkiPackageExporterService;
use App\Services\DeckExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * File downloads of the editor, served as plain GET links: the browser
 * downloads them on the user's own click (no "multiple downloads" block)
 * and the file is streamed instead of travelling base64-encoded inside a
 * Livewire response. Rows are read from the stored draft, which the editor
 * saves on every change.
 */
class DeckExportController extends Controller
{
    public function __construct(
        private readonly DeckExportService $deckExportService,
        private readonly AnkiPackageExporterService $ankiExporterService,
    ) {}

    /**
     * The draft's rows as a CSV file, in the user's accent style.
     */
    public function csv(Request $request, CsvDraft $draft): StreamedResponse
    {
        $this->ensureOwnedByUser($request, $draft);

        $user = $request->user();
        $csvContent = $this->deckExportService->toCsv(
            $draft->csv_rows ?? [],
            $user->accent_color,
            (bool) ($user->accent_bold ?? true),
            (bool) ($user->accent_unicode ?? false),
        );

        $downloadFileName = $this->baseFileName($draft).'_edited_'.$this->timestamp($request).'.csv';

        return response()->streamDownload(function () use ($csvContent): void {
            echo $csvContent;
        }, $downloadFileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * The draft as an Anki package: one note per row, with the cached audio.
     */
    public function apkg(Request $request, CsvDraft $draft): BinaryFileResponse
    {
        $this->ensureOwnedByUser($request, $draft);

        $cards = $this->deckExportService->toAnkiCards($draft->csv_rows ?? [], $draft->id);
        $deckName = CsvDraft::baseName($draft->original_file_name) ?: 'French-Russian';
        $apkgPath = $this->ankiExporterService->export($cards, $deckName);

        return $this->packageDownload($apkgPath, $this->baseFileName($draft).'_'.$this->timestamp($request).'.apkg');
    }

    /**
     * All of the user's drafts as one package, one deck each under a parent
     * deck named after the `name` query parameter.
     */
    public function collection(Request $request): BinaryFileResponse
    {
        $validated = $request->validate(['name' => ['nullable', 'string', 'max:255']]);

        $decks = $request->user()->csvDrafts()
            ->orderBy('id')
            ->get()
            ->map(fn (CsvDraft $draft): array => [
                'deckName' => CsvDraft::baseName($draft->original_file_name) ?: 'Deck',
                'cards' => $this->deckExportService->toAnkiCards($draft->csv_rows ?? [], $draft->id),
            ])
            ->all();

        $parentName = trim($validated['name'] ?? '') ?: 'Collection';
        $apkgPath = $this->ankiExporterService->exportCollection($decks, $parentName);
        $downloadFileName = $this->deckExportService->toDownloadFileName($parentName, 'Collection').'_'.$this->timestamp($request).'.apkg';

        return $this->packageDownload($apkgPath, $downloadFileName);
    }

    /**
     * A draft of another user answers 404, so its existence is not revealed.
     */
    private function ensureOwnedByUser(Request $request, CsvDraft $draft): void
    {
        abort_unless($draft->user_id === $request->user()->id, 404);
    }

    private function baseFileName(CsvDraft $draft): string
    {
        return $this->deckExportService->toDownloadFileName(CsvDraft::baseName($draft->original_file_name), 'export');
    }

    /**
     * Current time for the file name, in the browser's time zone (the `tz`
     * query parameter added by the page) so it matches the user's clock.
     * An unknown zone falls back to the application's instead of failing
     * the download.
     */
    private function timestamp(Request $request): string
    {
        $timezone = $request->query('tz');

        if (! is_string($timezone) || ! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = config('app.timezone');
        }

        return now($timezone)->format('Ymd_His');
    }

    /**
     * Send a temporary package file, deleted once it has been sent.
     */
    private function packageDownload(string $apkgPath, string $downloadFileName): BinaryFileResponse
    {
        return response()
            ->download($apkgPath, $downloadFileName, ['Content-Type' => 'application/octet-stream'])
            ->deleteFileAfterSend();
    }
}
