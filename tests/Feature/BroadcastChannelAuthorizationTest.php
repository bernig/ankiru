<?php

use App\Models\User;

beforeEach(function (): void {
    // The test environment uses the null broadcaster, which skips channel
    // callbacks. Reverb signs the auth response locally, no server needed.
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
    ]);

    // Channel callbacks are attached to the driver that was default at boot.
    require base_path('routes/channels.php');
});

test('a user can subscribe to their own private channel', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$user->id}"])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test("a user cannot subscribe to another user's private channel", function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)
        ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-App.Models.User.{$otherUser->id}"])
        ->assertForbidden();
});
