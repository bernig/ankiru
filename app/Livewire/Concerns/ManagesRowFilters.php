<?php

namespace App\Livewire\Concerns;

use App\Services\RussianAccentService;
use App\Services\RussianTextToSpeechService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;

/**
 * Search, filters and pagination of the CsvEditor rows. Row keys are kept
 * through filtering and slicing, so every row action receives the absolute
 * index of the row in the deck.
 *
 * @property array<int, array<int, string>> $csvRows
 * @property RussianAccentService $accentService
 * @property RussianTextToSpeechService $ttsService
 */
trait ManagesRowFilters
{
    /** @var list<int> Page sizes offered by the per-page selector. */
    private const array ALLOWED_PER_PAGE = [10, 25, 50, 100];

    /** Number of rows displayed per page (user-configurable). */
    public int $perPage = 50;

    public string $searchQuery = '';

    public bool $filterAccentNeeded = false;

    public bool $filterNoAudio = false;

    /**
     * Rows visible on the current page wrapped in a LengthAwarePaginator so
     * flux:pagination can consume it directly. Original array keys are
     * preserved so all row-index-based actions still work.
     */
    #[Computed]
    public function paginatedRows(): LengthAwarePaginator
    {
        $rows = $this->filteredRows;
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * $this->perPage;
        $slicedItems = collect(array_slice($rows, $offset, $this->perPage, true));

        return new LengthAwarePaginator(
            $slicedItems,
            count($rows),
            $this->perPage,
            $currentPage,
            ['path' => request()->url()]
        );
    }

    /**
     * Total number of pages given the current row count and page size.
     */
    #[Computed]
    public function totalPages(): int
    {
        return max(1, (int) ceil(count($this->filteredRows) / $this->perPage));
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAccentNeeded(): void
    {
        $this->resetPage();
    }

    public function updatedFilterNoAudio(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, self::ALLOWED_PER_PAGE, true)) {
            $this->perPage = 50;
        }

        $this->resetPage();
    }

    /**
     * Return the subset of csvRows matching the active search query and toggle
     * filters, preserving original array keys so row-index-based actions work.
     *
     * @return array<int, array<int, string>>
     */
    #[Computed]
    public function filteredRows(): array
    {
        $rows = $this->csvRows;

        $normalizedQuery = $this->normalizeForSearch(trim($this->searchQuery));

        if ($normalizedQuery !== '') {
            $rows = array_filter(
                $rows,
                fn (array $row): bool => str_contains($this->normalizeForSearch($row[0] ?? ''), $normalizedQuery)
                    || str_contains($this->normalizeForSearch($row[1] ?? ''), $normalizedQuery)
            );
        }

        if ($this->filterAccentNeeded) {
            $rows = array_filter(
                $rows,
                fn (array $row): bool => $this->accentService->textNeedsStressCorrection($row[1] ?? '')
            );
        }

        if ($this->filterNoAudio) {
            $russianTexts = array_values(array_filter(
                array_map(fn (array $row): string => $row[1] ?? '', $rows),
                fn (string $text): bool => trim($text) !== '',
            ));
            $audioExistence = $this->ttsService->audioFilesExistBatch($russianTexts);
            $rows = array_filter(
                $rows,
                fn (array $row): bool => trim($row[1] ?? '') !== '' && ! ($audioExistence[$row[1] ?? ''] ?? false)
            );
        }

        return $rows;
    }

    /**
     * Strip HTML tags, remove combining acute accents (U+0301), and lowercase.
     * Mirrors the normalisation done client-side in window.csvSearch.normalizeText().
     */
    private function normalizeForSearch(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\x{0301}/u', '', $text) ?? $text;

        return mb_strtolower($text);
    }
}
