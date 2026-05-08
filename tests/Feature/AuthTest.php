<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

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

test('forgot password page is accessible to guests', function () {
    $response = $this->get('/forgot-password');

    $response->assertOk();
});

test('password reset link is sent for existing email', function () {
    Notification::fake();

    /** @var User $user */
    $user = User::factory()->create();

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class);
});

test('no error is revealed for unknown email on forgot password', function () {
    Notification::fake();

    $response = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

    $response->assertSessionHasErrors('email');
    Notification::assertNothingSent();
});

test('password can be reset with valid token', function () {
    Notification::fake();

    /** @var User $user */
    $user = User::factory()->create();

    Password::sendResetLink(['email' => $user->email]);

    $token = '';
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
});

test('password reset fails with invalid token', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('email');
});
