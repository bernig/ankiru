<?php

use App\Ai\Agents\RowGeneratorAgent;
use App\Livewire\CsvEditor;
use App\Models\ApiUsageLog;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create([
        'openai_api_key' => 'sk-test-key-for-automated-tests',
        'learning_context' => 'Native French speaker, beginner level.',
    ]);

    $this->actingAs($authenticatedUser);
});

// ── Pagination & filter reset ────────────────────────────────────────────────

test('navigates to the last page after generation so new rows are immediately visible', function () {
    // 52 existing rows fills page 1 (50 per page) and leaves 2 on page 2.
    $existing = array_fill(0, 52, ['Existing', 'Существующий']);

    RowGeneratorAgent::fake(['[{"source": "New", "russian": "Нов<b>ы</b>й"}]']);

    $component = Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('csvRows', $existing)
        ->set('generateRowsPrompt', 'One more word')
        ->set('generateRowsCount', 1)
        ->call('generateRows');

    // 53 rows at 50 per page → last page is 2.
    expect($component->instance()->getPage())->toBe(2);
});

test('clears active filters after generation so new rows are not hidden', function () {
    RowGeneratorAgent::fake(['[{"source": "Hello", "russian": "При<b>в</b>ет"}]']);

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('csvRows', [['Bonjour', 'При<b>в</b>ет']])
        ->set('searchQuery', 'Bonjour')
        ->set('filterAccentNeeded', true)
        ->set('generateRowsPrompt', 'Greetings')
        ->set('generateRowsCount', 1)
        ->call('generateRows')
        ->assertSet('searchQuery', '')
        ->assertSet('filterAccentNeeded', false)
        ->assertSet('filterNoAudio', false);
});

// ── Successful generation ────────────────────────────────────────────────────

test('generates rows and appends them to csvRows', function () {
    RowGeneratorAgent::fake(['[{"source": "Hello", "russian": "При<b>в</b>ет"}, {"source": "Thank you", "russian": "Спас<b>и</b>бо"}]']);

    $component = Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('csvRows', [['Bonjour', 'При<b>в</b>ет']])
        ->set('generateRowsPrompt', 'Basic greetings')
        ->set('generateRowsCount', 2)
        ->call('generateRows');

    expect($component->instance()->csvRows)->toHaveCount(3)
        ->and($component->instance()->csvRows[1][0])->toBe('Hello')
        ->and($component->instance()->csvRows[1][1])->toBe('При<b>в</b>ет')
        ->and($component->instance()->csvRows[2][0])->toBe('Thank you')
        ->and($component->instance()->csvRows[2][1])->toBe('Спас<b>и</b>бо');

    $component->assertSet('translationError', '')->assertSet('generateRowsPrompt', '');
});

test('populates generatedRows with the new pairs after successful generation', function () {
    RowGeneratorAgent::fake(['[{"source": "Hello", "russian": "При<b>в</b>ет"}]']);

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('generateRowsPrompt', 'Greetings')
        ->set('generateRowsCount', 1)
        ->call('generateRows')
        ->assertSet('generatedRows', [['source' => 'Hello', 'russian' => 'При<b>в</b>ет']]);
});

test('generateRowsReset clears generatedRows and the prompt', function () {
    RowGeneratorAgent::fake(['[{"source": "Hello", "russian": "При<b>в</b>ет"}]']);

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('generateRowsPrompt', 'Greetings')
        ->set('generateRowsCount', 1)
        ->call('generateRows')
        ->call('generateRowsReset')
        ->assertSet('generatedRows', [])
        ->assertSet('generateRowsPrompt', '')
        ->assertSet('translationError', '');
});

test('logs api usage after successful generation', function () {
    RowGeneratorAgent::fake(['[{"source": "Hello", "russian": "При<b>в</b>ет"}]']);

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('generateRowsPrompt', 'Greetings')
        ->set('generateRowsCount', 1)
        ->call('generateRows');

    expect(ApiUsageLog::where('operation', 'row_generation')->count())->toBe(1);
});

test('sends the personal context and prompt to the agent', function () {
    RowGeneratorAgent::fake(['[{"source": "Hello", "russian": "При<b>в</b>ет"}]']);

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('generateRowsContext', 'Native French speaker, beginner.')
        ->set('generateRowsPrompt', 'Restaurant phrases')
        ->set('generateRowsCount', 1)
        ->call('generateRows');

    RowGeneratorAgent::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'Native French speaker, beginner.')
            && str_contains($prompt->prompt, 'Restaurant phrases')
            && str_contains($prompt->prompt, 'GENERATE: 1 flashcard pairs');
    });
});

// ── Include existing rows ────────────────────────────────────────────────────

test('sends existing source phrases to the agent when the toggle is on and rows fit', function () {
    RowGeneratorAgent::fake(['[{"source": "Hello", "russian": "При<b>в</b>ет"}]']);

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('csvRows', [['Bonjour', 'При<b>в</b>ет'], ['Merci', 'Спас<b>и</b>бо']])
        ->set('generateRowsIncludeExisting', true)
        ->set('generateRowsPrompt', 'More greetings')
        ->set('generateRowsCount', 1)
        ->call('generateRows');

    RowGeneratorAgent::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'EXISTING PHRASES')
            && str_contains($prompt->prompt, '- Bonjour')
            && str_contains($prompt->prompt, '- Merci');
    });
});

test('sends only the count when existing phrases exceed the character threshold', function () {
    // Build rows with total source text > 6000 chars (20 × 350 chars = 7000).
    $largeRows = array_map(fn ($i) => [str_repeat('a', 350), ''], range(1, 20));

    RowGeneratorAgent::fake(['[{"source": "Hello", "russian": "При<b>в</b>ет"}]']);

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('csvRows', $largeRows)
        ->set('generateRowsIncludeExisting', true)
        ->set('generateRowsPrompt', 'More phrases')
        ->set('generateRowsCount', 1)
        ->call('generateRows');

    RowGeneratorAgent::assertPrompted(function ($prompt) use ($largeRows) {
        return str_contains($prompt->prompt, 'NOTE:')
            && str_contains($prompt->prompt, count($largeRows).' existing phrases')
            && ! str_contains($prompt->prompt, 'EXISTING PHRASES');
    });
});

test('does not mention existing phrases when the toggle is off', function () {
    RowGeneratorAgent::fake(['[{"source": "Hello", "russian": "При<b>в</b>ет"}]']);

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('csvRows', [['Bonjour', 'При<b>в</b>ет']])
        ->set('generateRowsIncludeExisting', false)
        ->set('generateRowsPrompt', 'Greetings')
        ->set('generateRowsCount', 1)
        ->call('generateRows');

    RowGeneratorAgent::assertPrompted(function ($prompt) {
        return ! str_contains($prompt->prompt, 'EXISTING PHRASES')
            && ! str_contains($prompt->prompt, 'NOTE:');
    });
});

// ── Error handling ───────────────────────────────────────────────────────────

test('sets a translation error when the agent throws an exception', function () {
    RowGeneratorAgent::fake(function () {
        throw new RuntimeException('Service unavailable.');
    });

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('generateRowsPrompt', 'Greetings')
        ->set('generateRowsCount', 5)
        ->call('generateRows')
        ->assertSet('translationError', 'Service unavailable.')
        ->assertSet('generatedRows', []);
});

test('validates that the prompt is required', function () {
    RowGeneratorAgent::fake()->preventStrayPrompts();

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('generateRowsPrompt', '')
        ->set('generateRowsCount', 5)
        ->call('generateRows')
        ->assertHasErrors('generateRowsPrompt');

    RowGeneratorAgent::assertNeverPrompted();
});

test('validates that count is between 1 and 50', function () {
    RowGeneratorAgent::fake()->preventStrayPrompts();

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('generateRowsPrompt', 'Greetings')
        ->set('generateRowsCount', 0)
        ->call('generateRows')
        ->assertHasErrors('generateRowsCount');

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('generateRowsPrompt', 'Greetings')
        ->set('generateRowsCount', 51)
        ->call('generateRows')
        ->assertHasErrors('generateRowsCount');

    RowGeneratorAgent::assertNeverPrompted();
});

test('is blocked when the AI rate limit is exceeded', function () {
    RowGeneratorAgent::fake()->preventStrayPrompts();

    $rateLimitKey = 'ai-translation:'.auth()->id();
    RateLimiter::clear($rateLimitKey);
    for ($i = 0; $i < 30; $i++) {
        RateLimiter::hit($rateLimitKey, 60);
    }

    Livewire::test(CsvEditor::class)
        ->set('hasCsvLoaded', true)
        ->set('generateRowsPrompt', 'Greetings')
        ->set('generateRowsCount', 5)
        ->call('generateRows')
        ->assertSet('translationError', __('csv_editor.error_rate_limit'));

    RowGeneratorAgent::assertNeverPrompted();
    RateLimiter::clear($rateLimitKey);
});
