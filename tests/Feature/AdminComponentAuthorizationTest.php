<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\JobMonitor;
use App\Livewire\Admin\LogViewer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

dataset('adminComponents', [
    'dashboard' => [Dashboard::class],
    'job monitor' => [JobMonitor::class],
    'log viewer' => [LogViewer::class],
]);

test('admin components refuse non-admin users', function (string $component): void {
    Livewire::actingAs(User::factory()->create(['is_admin' => false]))
        ->test($component)
        ->assertForbidden();
})->with('adminComponents');

test('admin components refuse guests', function (string $component): void {
    Livewire::test($component)->assertForbidden();
})->with('adminComponents');

test('a demoted admin can no longer delete failed jobs from an open page', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    DB::table('failed_jobs')->insert([
        'uuid' => 'failed-job-uuid',
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\MassOperationJob']),
        'exception' => 'RuntimeException: boom',
        'failed_at' => now(),
    ]);

    $page = Livewire::actingAs($admin)->test(JobMonitor::class)->assertOk();

    $admin->forceFill(['is_admin' => false])->save();

    $page->call('deleteFailedJob', 'failed-job-uuid')->assertForbidden();

    expect(DB::table('failed_jobs')->where('uuid', 'failed-job-uuid')->exists())->toBeTrue();
});
