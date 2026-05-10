<?php

use App\Ai\Agents\RussianStressCorrectorAgent;
use App\Ai\Agents\SourceToRussianTranslatorAgent;
use App\Livewire\CsvEditor;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function (): void {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create();

    $this->actingAs($authenticatedUser);
});

// ── ChatGPT Translation ─────────────────────────────────────────────────────
test('translates french text to russian and stores the result', function () {
    SourceToRussianTranslatorAgent::fake(['Я раб<b>о</b>таю из д<b>о</b>ма.']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('csvRows.0.1', 'Я раб<b>о</b>таю из д<b>о</b>ма.')
        ->assertSet('translationError', '')
        ->assertSet('translatingRowIndex', -1);

    SourceToRussianTranslatorAgent::assertPrompted('Je travaille depuis chez moi.');
});

test('sets a translation error when the agent throws an exception', function () {
    SourceToRussianTranslatorAgent::fake(function () {
        throw new RuntimeException('Service unavailable.');
    });

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', 'Service unavailable.');
});

test('does nothing when the french column is empty', function () {
    SourceToRussianTranslatorAgent::fake()->preventStrayPrompts();

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', '');

    SourceToRussianTranslatorAgent::assertNeverPrompted();
});

// ── Stress Correction ───────────────────────────────────────────────────────
test('shows an error when the stress correction agent throws an exception', function () {
    RussianStressCorrectorAgent::fake(function () {
        throw new RuntimeException('Service unavailable.');
    });

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille.', 'Я работаю.']])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0)
        ->assertSet('translationError', 'Service unavailable.');
});

test('corrects stress marks and sends french context with the russian text', function () {
    RussianStressCorrectorAgent::fake(['Я раб<b>о</b>таю из д<b>о</b>ма.']);

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', 'Я работаю из дома.']])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0)
        ->assertSet('csvRows.0.1', 'Я раб<b>о</b>таю из д<b>о</b>ма.')
        ->assertSet('correctingStressRowIndex', -1);

    RussianStressCorrectorAgent::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'Source text for meaning/context only:')
            && str_contains($prompt->prompt, 'Je travaille depuis chez moi.')
            && str_contains($prompt->prompt, 'Russian text to review and correct stress marks in:')
            && str_contains($prompt->prompt, 'Я работаю из дома.');
    });
});

test('skips the AI call and fixes bare ё directly when normalisation alone suffices', function () {
    // "Пойдём" has two vowels (о + ё) but only ё is bare — normalizeYoAccent
    // can fix that without AI; the AI must never be called.
    RussianStressCorrectorAgent::fake(function () {
        throw new RuntimeException('AI should not have been called.');
    });

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Rentrons.', 'Пойдём дом<b>о</b>й.']])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0)
        ->assertSet('csvRows.0.1', 'Пойд<b>ё</b>м дом<b>о</b>й.')
        ->assertSet('translationError', '');

    RussianStressCorrectorAgent::assertNeverPrompted();
});

test('row needs stress correction returns false when the agent returns already correct text', function () {
    $alreadyCorrectText = 'Я раб<b>о</b>таю из д<b>о</b>ма.';
    RussianStressCorrectorAgent::fake([$alreadyCorrectText]);

    $component = Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', $alreadyCorrectText]])
        ->set('hasCsvLoaded', true)
        ->call('correctStressMarks', 0);

    expect($component->instance()->rowNeedsStressCorrection(0))->toBeFalse();
});

// ── Rate Limiting ─────────────────────────────────────────────────────────────
test('translation is blocked and an error is set after exceeding the rate limit', function () {
    SourceToRussianTranslatorAgent::fake()->preventStrayPrompts();

    // Exhaust the 30-attempt limit without triggering real agent calls.
    $rateLimitKey = 'ai-translation:'.session()->getId();
    RateLimiter::clear($rateLimitKey);
    for ($i = 0; $i < 30; $i++) {
        RateLimiter::hit($rateLimitKey, 60);
    }

    Livewire::test(CsvEditor::class)
        ->set('csvRows', [['Je travaille depuis chez moi.', '']])
        ->set('hasCsvLoaded', true)
        ->call('translateWithChatGpt', 0)
        ->assertSet('translationError', __('csv_editor.error_rate_limit'));

    SourceToRussianTranslatorAgent::assertNeverPrompted();
    RateLimiter::clear($rateLimitKey);
});
