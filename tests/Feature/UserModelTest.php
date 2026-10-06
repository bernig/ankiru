<?php

use App\Models\User;

test('admin rights and credit balance cannot be mass assigned', function (): void {
    $user = User::factory()->create(['is_admin' => false, 'credits' => 0]);

    $user->update(['is_admin' => true, 'credits' => 1_000_000, 'name' => 'Renamed']);

    expect($user->fresh())
        ->is_admin->toBeFalse()
        ->credits->toBe(0)
        ->name->toBe('Renamed');
});

test('the avatar follows the current email address', function (): void {
    $user = User::factory()->create(['email' => 'first@example.com']);
    $firstAvatar = $user->avatarUrl();

    $user->update(['email' => 'second+tag@example.com']);

    expect($firstAvatar)->toBe('https://api.dicebear.com/9.x/identicon/svg?seed=first%40example.com')
        ->and($user->avatarUrl())->toBe('https://api.dicebear.com/9.x/identicon/svg?seed=second%2Btag%40example.com');
});
