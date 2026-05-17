<?php

use App\Console\Commands\SendDailySummaryEmail;
use App\Mail\DailySummaryMail;
use App\Mail\NewFileMail;
use App\Mail\NewUserNotificationMail;
use App\Models\ApiUsageLog;
use App\Models\CsvDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    RateLimiter::clear(md5('register127.0.0.1'));
    config(['contact.reception_email' => 'admin@example.com']);
});

test('admin receives an email when a new user registers', function () {
    Mail::fake();

    $this->post('/register', [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Mail::assertSent(NewUserNotificationMail::class, function (NewUserNotificationMail $mail) {
        return $mail->hasTo('admin@example.com')
            && $mail->user->email === 'alice@example.com';
    });
});

test('admin receives an email when a new CsvDraft is created', function () {
    Mail::fake();

    $user = User::factory()->create();
    CsvDraft::factory()->create(['user_id' => $user->id]);

    Mail::assertQueued(NewFileMail::class, function (NewFileMail $mail) use ($user) {
        return $mail->hasTo('admin@example.com')
            && $mail->draft->user_id === $user->id;
    });
});

test('daily summary command sends email when there is activity', function () {
    Mail::fake();

    $user = User::factory()->create(['created_at' => now()->subDay()]);
    CsvDraft::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDay(),
    ]);
    ApiUsageLog::factory()->create([
        'user_id' => $user->id,
        'operation' => 'translation',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan(SendDailySummaryEmail::class)
        ->assertSuccessful();

    Mail::assertSent(DailySummaryMail::class, function (DailySummaryMail $mail) {
        return $mail->hasTo('admin@example.com')
            && $mail->stats['new_users'] === 1
            && $mail->stats['new_files'] === 1
            && $mail->stats['translations'] === 1;
    });
});

test('daily summary command does not send email when there is no activity', function () {
    Mail::fake();

    $this->artisan(SendDailySummaryEmail::class)
        ->assertSuccessful();

    Mail::assertNothingSent();
});

test('daily summary command ignores activity from today', function () {
    Mail::fake();

    $user = User::factory()->create(['created_at' => now()]);

    $this->artisan(SendDailySummaryEmail::class)
        ->assertSuccessful();

    Mail::assertNothingSent();
});
