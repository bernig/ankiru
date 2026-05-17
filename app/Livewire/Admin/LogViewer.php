<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Livewire\Component;

class LogViewer extends Component
{
    public int $lines = 100;

    /** @return list<array{level: string, datetime: string, channel: string, message: string, raw: string}> */
    public function entries(): array
    {
        $path = storage_path('logs/laravel.log');

        if (! File::exists($path)) {
            return [];
        }

        $content = File::get($path);
        $rawEntries = preg_split('/(?=\[\d{4}-\d{2}-\d{2})/m', $content, -1, PREG_SPLIT_NO_EMPTY);
        $rawEntries = array_slice(array_reverse($rawEntries), 0, $this->lines);

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
