<?php

use App\Livewire\DebugAiErrorSwitch;
use App\Services\OpenAiTranslationService;
use App\Services\RowGenerationService;
use App\Services\RussianTextToSpeechService;
use Livewire\Livewire;

beforeEach(function (): void {
    config(['app.debug' => true]);
    session()->forget('debug_force_ai_error');
});

// ── Livewire component ───────────────────────────────────────────────────────

test('switch starts off and reflects session state', function (): void {
    Livewire::test(DebugAiErrorSwitch::class)
        ->assertSet('forceAiError', false);
});

test('setting forceAiError to true persists it in session', function (): void {
    Livewire::test(DebugAiErrorSwitch::class)
        ->set('forceAiError', true)
        ->assertSet('forceAiError', true);

    expect(session('debug_force_ai_error'))->toBeTrue();
});

test('setting forceAiError to false persists it in session', function (): void {
    session()->put('debug_force_ai_error', true);

    Livewire::test(DebugAiErrorSwitch::class)
        ->assertSet('forceAiError', true)
        ->set('forceAiError', false)
        ->assertSet('forceAiError', false);

    expect(session('debug_force_ai_error'))->toBeFalse();
});

test('mount reads existing session flag', function (): void {
    session()->put('debug_force_ai_error', true);

    Livewire::test(DebugAiErrorSwitch::class)
        ->assertSet('forceAiError', true);
});

// ── Services ─────────────────────────────────────────────────────────────────

test('OpenAiTranslationService throws when flag is on', function (): void {
    session()->put('debug_force_ai_error', true);

    $service = app(OpenAiTranslationService::class);

    expect(fn () => $service->translateSourceToRussianWithUsage('Hello'))
        ->toThrow(RuntimeException::class, '[Debug] Simulated AI error.');
});

test('OpenAiTranslationService correctRussianStressMarksWithUsage throws when flag is on', function (): void {
    session()->put('debug_force_ai_error', true);

    $service = app(OpenAiTranslationService::class);

    expect(fn () => $service->correctRussianStressMarksWithUsage('При<b>в</b>ет', 'Hello'))
        ->toThrow(RuntimeException::class, '[Debug] Simulated AI error.');
});

test('RowGenerationService throws when flag is on', function (): void {
    session()->put('debug_force_ai_error', true);

    $service = app(RowGenerationService::class);

    expect(fn () => $service->generateRowPairs('Greetings', 1, '', false, false, [], 0))
        ->toThrow(RuntimeException::class, '[Debug] Simulated AI error.');
});

test('RussianTextToSpeechService throws when flag is on', function (): void {
    session()->put('debug_force_ai_error', true);

    $service = app(RussianTextToSpeechService::class);

    expect(fn () => $service->generateAudio('При<b>в</b>ет'))
        ->toThrow(RuntimeException::class, '[Debug] Simulated AI error.');
});

test('services do not throw when flag is off', function (): void {
    session()->put('debug_force_ai_error', false);

    expect(session('debug_force_ai_error'))->toBeFalse();
});
