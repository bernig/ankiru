<?php

use App\Livewire\OpenAiKeySetup;
use App\Models\User;
use Livewire\Livewire;

test('mount ne distribue aucun événement pour un utilisateur non authentifié', function (): void {
    Livewire::test(OpenAiKeySetup::class)
        ->assertNotDispatched('open-openai-key-setup');
});

test('mount distribue l\'événement quand l\'utilisateur n\'a pas de clé API', function (): void {
    $user = User::factory()->create(['openai_api_key' => null]);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->assertDispatched('open-openai-key-setup');
});

test('mount ne distribue pas l\'événement quand l\'utilisateur a déjà une clé API', function (): void {
    $user = User::factory()->create(['openai_api_key' => 'sk-test-valid-key-de-plus-de-20-caracteres']);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->assertNotDispatched('open-openai-key-setup');
});

test('mount ne distribue pas l\'événement quand le prompt a déjà été affiché cette session', function (): void {
    $user = User::factory()->create(['openai_api_key' => null]);

    session()->put('openai_key_prompt_shown', true);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->assertNotDispatched('open-openai-key-setup');
});

test('mount enregistre le flag de session lors de la distribution de l\'événement', function (): void {
    $user = User::factory()->create(['openai_api_key' => null]);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class);

    expect(session('openai_key_prompt_shown'))->toBeTrue();
});

test('saveApiKey enregistre la clé, vide le champ et distribue l\'événement openai-key-saved', function (): void {
    $user = User::factory()->create(['openai_api_key' => null]);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->set('openai_api_key', 'sk-test-valid-key-de-plus-de-20-caracteres')
        ->call('saveApiKey')
        ->assertDispatched('openai-key-saved')
        ->assertSet('openai_api_key', '')
        ->assertHasNoErrors();

    expect($user->fresh()->openai_api_key)->toBe('sk-test-valid-key-de-plus-de-20-caracteres');
});

test('saveApiKey valide que la clé est requise', function (): void {
    $user = User::factory()->create(['openai_api_key' => null]);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->set('openai_api_key', '')
        ->call('saveApiKey')
        ->assertHasErrors(['openai_api_key'])
        ->assertNotDispatched('openai-key-saved');
});

test('saveApiKey valide que la clé comporte au moins 20 caractères', function (): void {
    $user = User::factory()->create(['openai_api_key' => null]);

    Livewire::actingAs($user)
        ->test(OpenAiKeySetup::class)
        ->set('openai_api_key', 'trop-court')
        ->call('saveApiKey')
        ->assertHasErrors(['openai_api_key'])
        ->assertNotDispatched('openai-key-saved');
});
