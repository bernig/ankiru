<?php

use App\Models\CsvDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function sampleRows(): array
{
    return [
        ['Je travaille depuis chez moi.', 'Я раб<b>о</b>таю из д<b>о</b>ма.'],
        ['Je suis développeur web.', 'Я веб-разраб<b>о</b>тчик.'],
    ];
}

/*
|--------------------------------------------------------------------------
| CsvEditor rows
|--------------------------------------------------------------------------
|
| The editor rows are not a public Livewire property: they live in the active
| draft and are reloaded from the database on every request. These macros
| replace set('csvRows', ...) and assertSet('csvRows...', ...) accordingly.
|
*/

/**
 * Store the rows in the component's active draft (created for the acting user
 * when there is none), then let the component reload them.
 */
Testable::macro('setRows', function (array $rows): Testable {
    /** @var Testable $this */
    $draft = CsvDraft::query()
        ->where('user_id', auth()->id())
        ->find($this->get('activeDraftId'));

    if ($draft === null) {
        $draft = CsvDraft::withoutEvents(fn (): CsvDraft => CsvDraft::factory()->create([
            'user_id' => auth()->id(),
            'csv_rows' => $rows,
        ]));

        return $this->call('switchToDraft', $draft->id);
    }

    $draft->update(['csv_rows' => $rows]);

    return $this->call('$refresh');
});

/**
 * The rows held by the component after the last request.
 *
 * @return array<int, array<int, string>>
 */
Testable::macro('rows', function (): array {
    /** @var Testable $this */
    return (function (): array {
        return $this->csvRows;
    })->call($this->instance());
});

/**
 * Assert a value in the rows, by dot path ('0.1' is row 0, column 1), or the
 * whole rows array when the path is empty.
 */
Testable::macro('assertRowsSet', function (string $path, mixed $expected): Testable {
    /** @var Testable $this */
    $rows = $this->rows();

    Assert::assertEquals($expected, $path === '' ? $rows : data_get($rows, $path));

    return $this;
});

Testable::macro('assertRowCount', function (int $expected): Testable {
    /** @var Testable $this */
    Assert::assertCount($expected, $this->rows());

    return $this;
});
