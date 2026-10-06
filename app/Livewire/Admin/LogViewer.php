<?php

namespace App\Livewire\Admin;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LogViewer extends Component
{
    /**
     * Only the end of the log is parsed: the single-channel file grows without
     * rotation and reading it whole every poll would exhaust the memory limit.
     */
    private const TAIL_BYTES = 2 * 1024 * 1024;

    private const MAX_LINES = 500;

    public int $lines = 100;

    /**
     * The route middleware only guards the initial page load; Livewire update
     * requests must re-check admin rights on their own.
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    /** @return list<array{level: string, datetime: string, channel: string, message: string, raw: string}> */
    #[Computed]
    public function entries(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return [];
        }

        $offset = max(0, filesize($path) - self::TAIL_BYTES);
        $content = (string) file_get_contents($path, offset: $offset);

        $rawEntries = preg_split('/(?=^\[\d{4}-\d{2}-\d{2})/m', $content, -1, PREG_SPLIT_NO_EMPTY);

        // Reading from the middle of the file: the first chunk is a truncated entry.
        if ($offset > 0) {
            array_shift($rawEntries);
        }

        $lineCount = max(1, min($this->lines, self::MAX_LINES));
        $rawEntries = array_slice(array_reverse($rawEntries), 0, $lineCount);

        return array_map(function (string $raw): array {
            preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+?)(\{.*\})?$/s', trim($raw), $m);

            return [
                'datetime' => $m[1] ?? '',
                'channel' => $m[2] ?? '',
                'level' => strtolower($m[3] ?? ''),
                'message' => isset($m[4]) ? rtrim($m[4]) : trim($raw),
                'raw' => trim($raw),
            ];
        }, $rawEntries);
    }

    public function render(): View
    {
        return view('livewire.admin.log-viewer');
    }
}
