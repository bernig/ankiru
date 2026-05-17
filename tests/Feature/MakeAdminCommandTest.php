<?php

use App\Models\User;

test('grants admin to an existing user', function () {
    $user = User::factory()->create(['email' => 'alice@example.com', 'is_admin' => false]);

    $this->artisan('admin:grant', ['email' => 'alice@example.com'])
        ->assertSuccessful()
        ->expectsOutputToContain('alice@example.com');

    expect($user->fresh()->is_admin)->toBeTrue();
});

test('warns when user is already admin', function () {
    User::factory()->create(['email' => 'alice@example.com', 'is_admin' => true]);

    $this->artisan('admin:grant', ['email' => 'alice@example.com'])
        ->assertSuccessful()
        ->expectsOutputToContain('already an admin');
});

test('fails when user does not exist', function () {
    $this->artisan('admin:grant', ['email' => 'nobody@example.com'])
        ->assertFailed()
        ->expectsOutputToContain('No user found');
});
