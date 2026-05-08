<?php

use App\Livewire\Profile;
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
