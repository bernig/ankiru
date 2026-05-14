<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Honeypot\EncryptedTime;

beforeEach(function (): void {
    RateLimiter::clear(md5('login127.0.0.1'));
    RateLimiter::clear(md5('register127.0.0.1'));
    RateLimiter::clear(md5('password-reset-request127.0.0.1'));
});

test('register est bloqué quand le formulaire est soumis trop rapidement (bot speed)', function (): void {
    Notification::fake();

    // valid_from = maintenant + 1s simule un bot qui soumet instantanément,
    // avant que le délai minimum ne soit écoulé.
    $validFrom = (string) EncryptedTime::create(now()->addSeconds(1));

    $this->post('/register', [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'valid_from' => $validFrom,
    ])->assertOk()->assertContent('');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    Notification::assertNothingSent();
});

test('register est bloqué quand le champ honeypot est rempli par un bot', function (): void {
    // Un bot remplit le champ caché "my_name". Le valid_from est valide (passé)
    // pour que seul le champ rempli soit le déclencheur du blocage.
    $validFrom = (string) EncryptedTime::create(now()->subSeconds(5));

    $this->post('/register', [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'my_name' => 'rempli-par-un-bot',
        'valid_from' => $validFrom,
    ])->assertOk()->assertContent('');

    $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
});

test('login est bloqué par le honeypot quand le formulaire est soumis trop rapidement', function (): void {
    $user = User::factory()->create(['password' => 'password']);
    $validFrom = (string) EncryptedTime::create(now()->addSeconds(1));

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'valid_from' => $validFrom,
    ])->assertOk()->assertContent('');

    $this->assertGuest();
});

test('mot de passe oublié est bloqué par le honeypot quand le formulaire est soumis trop rapidement', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $validFrom = (string) EncryptedTime::create(now()->addSeconds(1));

    $this->post('/forgot-password', [
        'email' => $user->email,
        'valid_from' => $validFrom,
    ])->assertOk()->assertContent('');

    Notification::assertNothingSent();
});
