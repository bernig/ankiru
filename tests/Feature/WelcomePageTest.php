<?php

use App\Models\User;

dataset('welcomePageLocales', ['en', 'fr', 'ru']);

test('les visiteurs voient la page d\'accueil à la racine', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(__('welcome.hero_title'))
        ->assertSee(__('welcome.hero_description'))
        ->assertSee(__('welcome.pricing_title'))
        ->assertSee(__('welcome.pricing_description'))
        ->assertSee(__('welcome.features_title'))
        ->assertSee(__('welcome.feature_generation_title'))
        ->assertSee(__('welcome.anki_callout_title'))
        ->assertSee(__('welcome.cta_register'))
        ->assertSee(__('welcome.cta_login'));
});

test('la page d\'accueil affiche le texte localisé pour chaque langue disponible', function (string $locale) {
    $this->withSession(['locale' => $locale])
        ->get('/')
        ->assertOk()
        ->assertSee(__('welcome.hero_title', [], $locale))
        ->assertSee(__('welcome.hero_description', [], $locale))
        ->assertSee(__('welcome.pricing_title', [], $locale))
        ->assertSee(__('welcome.pricing_description', [], $locale))
        ->assertSee(__('welcome.features_title', [], $locale))
        ->assertSee(__('welcome.feature_generation_title', [], $locale));
})->with('welcomePageLocales');

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
