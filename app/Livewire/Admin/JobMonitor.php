<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class JobMonitor extends Component
{
    /**
     * The route middleware only guards the initial page load; Livewire update
     * requests must re-check admin rights on their own.
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    /** @return Collection<int, object> */
    public function pendingJobs(): Collection
    {
        return DB::table('jobs')
            ->orderBy('available_at')
            ->get()
            ->map(function (object $job): object {
                $payload = json_decode($job->payload, true);
                $job->display_name = class_basename($payload['displayName'] ?? 'Unknown');
                $job->is_reserved = ! is_null($job->reserved_at);

                return $job;
            });
    }

    /** @return Collection<int, object> */
    public function failedJobs(): Collection
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(50)
            ->get()
            ->map(function (object $job): object {
                $payload = json_decode($job->payload, true);
                $job->display_name = class_basename($payload['displayName'] ?? 'Unknown');
                $job->exception_summary = strtok($job->exception, "\n");

                return $job;
            });
    }

    /** @return Collection<int, object> */
    public function batches(): Collection
    {
        return DB::table('job_batches')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function (object $batch): object {
                $completed = $batch->total_jobs - $batch->pending_jobs;
                $batch->progress = $batch->total_jobs > 0
                    ? (int) round($completed / $batch->total_jobs * 100)
                    : 100;
                $batch->completed_jobs = $completed;
                $batch->is_finished = ! is_null($batch->finished_at);
                $batch->is_cancelled = ! is_null($batch->cancelled_at);

                return $batch;
            });
    }

    public function retryJob(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
    }

    public function deleteFailedJob(string $uuid): void
    {
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();
    }

    public function render(): View
    {
        return view('livewire.admin.job-monitor');
    }
}
