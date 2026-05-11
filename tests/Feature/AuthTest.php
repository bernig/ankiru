<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    RateLimiter::clear(md5('login127.0.0.1'));
    RateLimiter::clear(md5('register127.0.0.1'));
    RateLimiter::clear(md5('password-reset-request127.0.0.1'));
});

test('user can register and is redirected to email verification notice', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    Notification::assertSentTo(User::where('email', 'test@example.com')->first(), VerifyEmail::class);
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

test('unverified user is redirected to verification notice when accessing protected routes', function () {
    /** @var User $user */
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    $this->get('/')->assertRedirect(route('verification.notice'));
    $this->get('/profile')->assertRedirect(route('verification.notice'));
});

test('verified user can access protected routes', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/')->assertOk();
});

test('verification notice page is accessible to authenticated unverified user', function () {
    /** @var User $user */
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/email/verify')->assertOk();
});

test('email is verified when clicking the signed verification link', function () {
    /** @var User $user */
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)->get($verificationUrl)->assertRedirect(route('csv-editor'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('verification link can be resent', function () {
    Notification::fake();

    /** @var User $user */
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->post('/email/verification-notification');

    $response->assertRedirect();
    $response->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verification email is sent in french when locale is fr', function () {
    App::setLocale('fr');

    /** @var User $user */
    $user = User::factory()->unverified()->create();

    $mail = (new VerifyEmail)->toMail($user);

    expect($mail->subject)->toBe('Vérifiez votre adresse e-mail')
        ->and($mail->actionText)->toBe("Vérifier l'adresse e-mail");
});

test('password reset email is sent in french when locale is fr', function () {
    App::setLocale('fr');

    /** @var User $user */
    $user = User::factory()->create();

    $mail = (new ResetPassword('fake-token'))->toMail($user);

    expect($mail->subject)->toBe('Réinitialisez votre mot de passe')
        ->and($mail->actionText)->toBe('Réinitialiser le mot de passe');
});

test('verification email is sent in russian when locale is ru', function () {
    App::setLocale('ru');

    /** @var User $user */
    $user = User::factory()->unverified()->create();

    $mail = (new VerifyEmail)->toMail($user);

    expect($mail->subject)->toBe('Подтвердите свой email')
        ->and($mail->actionText)->toBe('Подтвердить email');
});

test('password reset email is sent in russian when locale is ru', function () {
    App::setLocale('ru');

    /** @var User $user */
    $user = User::factory()->create();

    $mail = (new ResetPassword('fake-token'))->toMail($user);

    expect($mail->subject)->toBe('Сбросьте пароль')
        ->and($mail->actionText)->toBe('Сбросить пароль');
});

test('login is rate limited after 5 consecutive attempts', function (): void {
    foreach (range(1, 5) as $_) {
        $this->post('/login', ['email' => 'any@test.com', 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => 'any@test.com', 'password' => 'wrong'])
        ->assertStatus(429);
});

test('register is rate limited after 5 consecutive attempts', function (): void {
    foreach (range(1, 5) as $_) {
        $this->post('/register', []);
    }

    $this->post('/register', [])
        ->assertStatus(429);
});

test('forgot password is rate limited after 5 consecutive attempts', function (): void {
    Notification::fake();

    foreach (range(1, 5) as $_) {
        $this->post('/forgot-password', ['email' => 'test@example.com']);
    }

    $this->post('/forgot-password', ['email' => 'test@example.com'])
        ->assertStatus(429);
});
