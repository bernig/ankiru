<?php

namespace App\Console\Commands;

use App\Mail\DailySummaryMail;
use App\Models\ApiUsageLog;
use App\Models\CsvDraft;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-daily-summary-email')]
#[Description('Send a daily activity summary email to the admin (skipped if no activity).')]
class SendDailySummaryEmail extends Command
{
    public function handle(): int
    {
        $yesterday = now()->subDay()->startOfDay();
        $endOfYesterday = now()->subDay()->endOfDay();

        $stats = [
            'new_users' => User::query()
                ->whereBetween('created_at', [$yesterday, $endOfYesterday])
                ->count(),

            'new_files' => CsvDraft::query()
                ->whereBetween('created_at', [$yesterday, $endOfYesterday])
                ->count(),

            'translations' => ApiUsageLog::query()
                ->where('operation', 'translation')
                ->whereBetween('created_at', [$yesterday, $endOfYesterday])
                ->count(),

            'stress_corrections' => ApiUsageLog::query()
                ->where('operation', 'stress_correction')
                ->whereBetween('created_at', [$yesterday, $endOfYesterday])
                ->count(),

            'tts_generations' => ApiUsageLog::query()
                ->where('operation', 'tts')
                ->whereBetween('created_at', [$yesterday, $endOfYesterday])
                ->count(),
        ];

        $totalActivity = array_sum($stats);

        if ($totalActivity === 0) {
            $this->info('No activity yesterday. Skipping email.');

            return self::SUCCESS;
        }

        $date = $yesterday->isoFormat('dddd D MMMM YYYY');

        Mail::to(config('contact.reception_email'))
            ->send(new DailySummaryMail($stats, $date));

        $this->info("Daily summary sent for {$date}.");

        return self::SUCCESS;
    }
}
