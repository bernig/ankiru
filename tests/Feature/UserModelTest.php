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

test('the header avatar shows local initials without sending the email to a third party', function (): void {
    $user = User::factory()->create(['name' => 'Marie Curie', 'email' => 'marie@example.com']);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('MC')
        ->assertDontSee('dicebear')
        ->assertDontSee(rawurlencode('marie@example.com'));
});
