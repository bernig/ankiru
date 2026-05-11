<?php

use App\Models\User;

test('les visiteurs voient la page d\'accueil à la racine', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(__('welcome.hero_title'))
        ->assertSee(__('welcome.cta_register'))
        ->assertSee(__('welcome.cta_login'));
});

test('les utilisateurs connectés et vérifiés voient l\'éditeur CSV à la racine', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSeeLivewire('csv-editor');
});

test('les utilisateurs connectés mais non vérifiés voient la page d\'accueil', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee(__('welcome.hero_title'));
});
