<?php

use App\Livewire\Profile;
use App\Models\ApiUsageLog;
use App\Models\User;
use Livewire\Livewire;

test('page profil est accessible', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk();
});

test('page profil redirige les invités', function () {
    $this->get(route('profile'))
        ->assertRedirect(route('login'));
});

test('le composant profil est monté avec les données utilisateur', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->assertSet('name', $user->name)
        ->assertSet('email', $user->email);
});

test('le nom et l\'email peuvent être mis à jour', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('name', 'Nouveau Nom')
        ->set('email', 'nouveau@example.com')
        ->call('updateProfile')
        ->assertSet('profileSaved', true)
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Nouveau Nom');
    expect($user->fresh()->email)->toBe('nouveau@example.com');
});

test('la mise à jour du profil valide les champs requis', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('name', '')
        ->set('email', 'pas-un-email')
        ->call('updateProfile')
        ->assertHasErrors(['name', 'email']);
});

test('le mot de passe peut être mis à jour', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('current_password', 'password')
        ->set('password', 'nouveau-mot-de-passe-securise-123!')
        ->set('password_confirmation', 'nouveau-mot-de-passe-securise-123!')
        ->call('updatePassword')
        ->assertSet('passwordSaved', true)
        ->assertHasNoErrors();
});

test('la mise à jour du mot de passe exige le mot de passe actuel correct', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('current_password', 'mauvais-mot-de-passe')
        ->set('password', 'nouveau-mot-de-passe-securise-123!')
        ->set('password_confirmation', 'nouveau-mot-de-passe-securise-123!')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});

test('la mise à jour du mot de passe exige la confirmation', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('current_password', 'password')
        ->set('password', 'nouveau-mot-de-passe-securise-123!')
        ->set('password_confirmation', 'pas-la-meme-chose')
        ->call('updatePassword')
        ->assertHasErrors(['password']);
});

test('la clé API OpenAI peut être enregistrée', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('openai_api_key', 'sk-test-cle-api-valide-de-plus-de-20-caracteres')
        ->call('saveApiKey')
        ->assertSet('apiKeySaved', true)
        ->assertHasNoErrors();

    expect($user->fresh()->openai_api_key)->toBe('sk-test-cle-api-valide-de-plus-de-20-caracteres');
});

test('la clé API OpenAI est validée', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->set('openai_api_key', 'trop-court')
        ->call('saveApiKey')
        ->assertHasErrors(['openai_api_key']);
});

test('la clé API OpenAI peut être supprimée', function () {
    $user = User::factory()->create(['openai_api_key' => 'sk-test-cle-api-valide-de-plus-de-20-caracteres']);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->call('clearApiKey');

    expect($user->fresh()->openai_api_key)->toBeNull();
});

test('les statistiques d\'utilisation sont vides sans logs', function () {
    $user = User::factory()->create(['openai_api_key' => 'sk-test-cle-api-valide-de-plus-de-20-caracteres']);

    Livewire::actingAs($user)
        ->test(Profile::class)
        ->assertSee(__('profile.api_usage_empty'));
});

test('les statistiques d\'utilisation agrègent les logs par opération', function () {
    $user = User::factory()->create();

    ApiUsageLog::factory()->create(['user_id' => $user->id, 'operation' => 'translation', 'prompt_tokens' => 100, 'completion_tokens' => 50, 'characters' => null]);
    ApiUsageLog::factory()->create(['user_id' => $user->id, 'operation' => 'translation', 'prompt_tokens' => 200, 'completion_tokens' => 80, 'characters' => null]);
    ApiUsageLog::factory()->create(['user_id' => $user->id, 'operation' => 'tts', 'prompt_tokens' => null, 'completion_tokens' => null, 'characters' => 500]);

    $component = Livewire::actingAs($user)->test(Profile::class);
    $stats = $component->instance()->usageStats;

    $translation = $stats->firstWhere('operation', 'translation');
    expect($translation['calls'])->toBe(2);
    expect($translation['prompt_tokens'])->toBe(300);
    expect($translation['completion_tokens'])->toBe(130);

    $tts = $stats->firstWhere('operation', 'tts');
    expect($tts['calls'])->toBe(1);
    expect($tts['characters'])->toBe(500);
});

test('les statistiques n\'incluent pas les logs des autres utilisateurs', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    ApiUsageLog::factory()->create(['user_id' => $other->id, 'operation' => 'translation', 'prompt_tokens' => 999, 'completion_tokens' => 999, 'characters' => null]);

    $component = Livewire::actingAs($user)->test(Profile::class);
    expect($component->instance()->usageStats)->toBeEmpty();
});

test('le middleware injecte la clé API de l\'utilisateur dans la config', function () {
    $userKey = 'sk-user-test-cle-api-valide-de-plus-de-20-caracteres';
    $user = User::factory()->create(['openai_api_key' => $userKey]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk();

    expect(config('ai.providers.openai.key'))->toBe($userKey);
});
