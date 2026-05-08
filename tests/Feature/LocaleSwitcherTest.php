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

test('guest cannot switch locale', function () {
    $response = $this->get(route('locale.update', 'fr'));

    $response->assertRedirect(route('login'));
});
