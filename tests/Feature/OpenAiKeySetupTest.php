<?php

use App\Livewire\OpenAiKeySetup;
use App\Models\User;
use Livewire\Livewire;

test('mount dispatches no event for unauthenticated user', function (): void {
    Livewire::test(OpenAiKeySetup::class)
        ->assertNotDispatched('open-openai-key-setup');
});

test('mount dispatches the event when user has no key and no credits', function (): void {
    $user = User::factory()->create(['openai_api_key' => null, 'credits' => 0]);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->assertDispatched('open-openai-key-setup');
});

test('mount does not dispatch the event when user has a personal API key', function (): void {
    $user = User::factory()->create(['openai_api_key' => 'sk-test-valid-key-de-plus-de-20-caracteres']);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->assertNotDispatched('open-openai-key-setup');
});

test('mount does not dispatch the event when user has credits', function (): void {
    $user = User::factory()->create(['openai_api_key' => null, 'credits' => 500_000]);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->assertNotDispatched('open-openai-key-setup');
});

test('mount does not dispatch the event when the prompt has already been shown this session', function (): void {
    $user = User::factory()->create(['openai_api_key' => null, 'credits' => 0]);

    session()->put('openai_key_prompt_shown', true);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->assertNotDispatched('open-openai-key-setup');
});

test('mount stores the session flag when dispatching the event', function (): void {
    $user = User::factory()->create(['openai_api_key' => null, 'credits' => 0]);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class);

    expect(session('openai_key_prompt_shown'))->toBeTrue();
});
