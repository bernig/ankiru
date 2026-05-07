<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register and is redirected to editor', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

test('user can login with valid credentials', function () {
    /** @var User $existingUser */
    $existingUser = User::factory()->create([
        'password' => 'secret-password',
    ]);

    $response = $this->post('/login', [
        'email' => $existingUser->email,
        'password' => 'secret-password',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticated();
    expect(auth()->id())->toBe($existingUser->id);
});

test('login fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'correct-password',
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('authenticated user can logout', function () {
    /** @var User $authenticatedUser */
    $authenticatedUser = User::factory()->create();

    $this->actingAs($authenticatedUser);

    $response = $this->post('/logout');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('guest cannot access the csv editor', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
