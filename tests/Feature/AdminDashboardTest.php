<?php

use App\Livewire\Admin\Dashboard;
use App\Models\ApiUsageLog;
use App\Models\CsvDraft;
use App\Models\User;
use Livewire\Livewire;

test('admin route is inaccessible to guests', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('admin route is inaccessible to regular users', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});

test('admin route is accessible to admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
});

test('admin dashboard renders the livewire component', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSeeLivewire(Dashboard::class);
});

test('admin dashboard shows users tab with user list', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $regular = User::factory()->create(['name' => 'Jean Dupont']);

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->assertSee('Jean Dupont');
});

test('admin dashboard shows drafts tab', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    CsvDraft::factory()->create(['user_id' => $user->id, 'original_file_name' => 'mon_fichier.csv']);

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->set('tab', 'drafts')
        ->assertSee('mon_fichier.csv');
});

test('admin dashboard shows usage stats tab', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['name' => 'Alice']);
    ApiUsageLog::factory()->create(['user_id' => $user->id, 'operation' => 'translation', 'prompt_tokens' => 100]);

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->set('tab', 'usage')
        ->assertSee('Alice');
});

test('user search filters results', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['name' => 'Alice Martin']);
    User::factory()->create(['name' => 'Bob Dupont']);

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->set('userSearch', 'Alice')
        ->assertSee('Alice Martin')
        ->assertDontSee('Bob Dupont');
});
