<?php

use App\Livewire\Admin\LogViewer;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->originalStoragePath = storage_path();
    $this->temporaryStoragePath = sys_get_temp_dir().'/'.uniqid('log_viewer_', true);
    File::ensureDirectoryExists($this->temporaryStoragePath.'/logs');
    app()->useStoragePath($this->temporaryStoragePath);

    Livewire::actingAs(User::factory()->create(['is_admin' => true]));
});

afterEach(function (): void {
    app()->useStoragePath($this->originalStoragePath);
    File::deleteDirectory($this->temporaryStoragePath);
});

function writeLog(string $content): void
{
    file_put_contents(storage_path('logs/laravel.log'), $content);
}

/**
 * @return list<array{level: string, datetime: string, channel: string, message: string, raw: string}>
 */
function logEntries(int $lines = 100): array
{
    return Livewire::test(LogViewer::class)->set('lines', $lines)->instance()->entries;
}

test('lists entries newest first with their level, channel and message', function (): void {
    writeLog(
        "[2026-10-06 05:00:00] local.INFO: Draft saved. {\"user_id\":1}\n"
        ."[2026-10-06 05:01:00] production.ERROR: Service unavailable. {\"exception\":\"[object] (RuntimeException(code: 0))\n[stacktrace]\n#0 /app/Jobs/MassOperationJob.php(80)\n\"}\n"
    );

    $entries = logEntries();

    expect($entries)->toHaveCount(2)
        ->and($entries[0])->toMatchArray([
            'datetime' => '2026-10-06 05:01:00',
            'channel' => 'production',
            'level' => 'error',
            'message' => 'Service unavailable.',
        ])
        ->and($entries[0]['raw'])->toContain('#0 /app/Jobs/MassOperationJob.php(80)')
        ->and($entries[1])->toMatchArray(['level' => 'info', 'message' => 'Draft saved.']);
});

test('does not split an entry on a date that appears inside its message', function (): void {
    writeLog("[2026-10-06 05:00:00] local.WARNING: Retry scheduled [2026-10-07 00:00:00] for job 42\n");

    expect(logEntries())->toHaveCount(1)
        ->sequence(fn ($entry) => $entry->message->toBe('Retry scheduled [2026-10-07 00:00:00] for job 42'));
});

test('keeps the raw text when a line does not follow the log format', function (): void {
    writeLog("Something unexpected\n");

    expect(logEntries()[0])->toMatchArray(['level' => '', 'datetime' => '', 'message' => 'Something unexpected']);
});

test('limits the entries to the selected count and clamps tampered values', function (int $requestedLines, int $expectedCount): void {
    writeLog(implode('', array_map(
        fn (int $i) => sprintf("[2026-10-06 05:%02d:%02d] local.DEBUG: Entry %d\n", intdiv($i, 60) % 60, $i % 60, $i),
        range(1, 600),
    )));

    $entries = logEntries($requestedLines);

    expect($entries)->toHaveCount($expectedCount)
        ->and($entries[0]['message'])->toBe('Entry 600');
})->with([
    'selected value' => [50, 50],
    'above the maximum' => [100_000, 500],
    'zero' => [0, 1],
]);

test('only reads the end of a large log file and drops the truncated first entry', function (): void {
    writeLog(
        '[2026-10-06 04:00:00] local.INFO: Oldest '.str_repeat('x', 3 * 1024 * 1024)."\n"
        ."[2026-10-06 05:00:00] local.INFO: Recent one\n"
        ."[2026-10-06 05:01:00] local.INFO: Recent two\n"
    );

    expect(array_column(logEntries(), 'message'))->toBe(['Recent two', 'Recent one']);
});

test('shows the empty state when there is no log file', function (): void {
    Livewire::test(LogViewer::class)
        ->assertSee(__('admin.log_empty'));
});
