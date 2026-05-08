<?php

use App\Models\User;

test('authenticated user sees language options in top menu', function () {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create();

    $response = $this->actingAs($authenticatedUser)->get('/');

    $response->assertOk();
    $response->assertSee(__('csv_editor.language_french'));
    $response->assertSee(__('csv_editor.language_english', [], 'en'));
});

test('authenticated user can switch locale and preference is stored in session', function () {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create();

    $response = $this->actingAs($authenticatedUser)
        ->from('/')
        ->get(route('locale.update', 'fr'));

    $response->assertRedirect('/');
    $response->assertSessionHas('locale', 'fr');
});

test('guest can switch locale and the preference is applied on subsequent pages', function () {
    $switchResponse = $this->from(route('login'))
        ->get(route('locale.update', 'fr'));

    $switchResponse->assertRedirect(route('login'));
    $switchResponse->assertSessionHas('locale', 'fr');

    $loginResponse = $this->withSession(['locale' => 'fr'])->get(route('login'));
    $loginResponse->assertOk();
    $loginResponse->assertSee(__('auth.login', [], 'fr'));
});

test('guest can switch locale from french to english', function () {
    $switchResponse = $this->withSession(['locale' => 'fr'])
        ->from(route('login'))
        ->get(route('locale.update', 'en'));

    $switchResponse->assertRedirect(route('login'));
    $switchResponse->assertSessionHas('locale', 'en');

    $loginResponse = $this->withSession(['locale' => 'en'])->get(route('login'));
    $loginResponse->assertOk();
    $loginResponse->assertSee(__('auth.login', [], 'en'));
});

test('guest language switcher renders navigable locale links', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('href="'.route('locale.update', 'fr').'"', false);
    $response->assertSee('href="'.route('locale.update', 'en').'"', false);
});
