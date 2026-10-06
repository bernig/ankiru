<?php

use App\Enums\OperationType;
use App\Jobs\MassOperationJob;
use App\Livewire\Admin\JobMonitor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::actingAs(User::factory()->create(['is_admin' => true]));
});

function insertFailedJob(string $uuid, string $exception = "RuntimeException: boom\n#0 trace line"): void
{
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode([
            'uuid' => $uuid,
            'displayName' => 'App\\Jobs\\MassOperationJob',
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => ['commandName' => 'App\\Jobs\\MassOperationJob', 'command' => 'serialized'],
        ]),
        'exception' => $exception,
        'failed_at' => now(),
    ]);
}

test('pending jobs show the short job name and whether a worker reserved them', function (): void {
    DB::table('jobs')->insert([
        ['queue' => 'default', 'payload' => json_encode(['displayName' => 'App\\Jobs\\MassOperationJob']), 'attempts' => 0, 'reserved_at' => now()->timestamp, 'available_at' => 1, 'created_at' => 1],
        ['queue' => 'default', 'payload' => json_encode([]), 'attempts' => 0, 'reserved_at' => null, 'available_at' => 2, 'created_at' => 2],
    ]);

    $jobs = Livewire::test(JobMonitor::class)->instance()->pendingJobs();

    expect($jobs->pluck('display_name')->all())->toBe(['MassOperationJob', 'Unknown'])
        ->and($jobs->pluck('is_reserved')->all())->toBe([true, false]);
});

test('failed jobs only show the first line of the exception', function (): void {
    insertFailedJob('job-1', "RuntimeException: Service unavailable.\n#0 /app/Jobs/MassOperationJob.php(80)");

    $job = Livewire::test(JobMonitor::class)->instance()->failedJobs()->sole();

    expect($job->display_name)->toBe('MassOperationJob')
        ->and($job->exception_summary)->toBe('RuntimeException: Service unavailable.');
});

test('batches report their completion percentage and state', function (): void {
    DB::table('job_batches')->insert([
        ['id' => 'running', 'name' => 'tts', 'total_jobs' => 3, 'pending_jobs' => 2, 'failed_jobs' => 0, 'failed_job_ids' => '[]', 'options' => null, 'cancelled_at' => null, 'created_at' => 3, 'finished_at' => null],
        ['id' => 'empty', 'name' => 'stress', 'total_jobs' => 0, 'pending_jobs' => 0, 'failed_jobs' => 0, 'failed_job_ids' => '[]', 'options' => null, 'cancelled_at' => 5, 'created_at' => 2, 'finished_at' => 4],
    ]);

    $batches = Livewire::test(JobMonitor::class)->instance()->batches()->keyBy('id');

    expect($batches['running'])
        ->progress->toBe(33)
        ->completed_jobs->toBe(1)
        ->is_finished->toBeFalse()
        ->is_cancelled->toBeFalse()
        ->and($batches['empty'])
        ->progress->toBe(100)
        ->is_finished->toBeTrue()
        ->is_cancelled->toBeTrue();
});

test('retrying a failed job puts it back on its queue', function (): void {
    Queue::connection('database')->push(new MassOperationJob(OperationType::Tts, 'session', 0, 1, '', 'Привет'));
    $queuedJob = DB::table('jobs')->sole();
    DB::table('jobs')->delete();
    DB::table('failed_jobs')->insert([
        'uuid' => 'job-to-retry',
        'connection' => 'database',
        'queue' => $queuedJob->queue,
        'payload' => $queuedJob->payload,
        'exception' => 'RuntimeException: boom',
        'failed_at' => now(),
    ]);

    Livewire::test(JobMonitor::class)->call('retryJob', 'job-to-retry');

    expect(DB::table('failed_jobs')->count())->toBe(0)
        ->and(DB::table('jobs')->where('queue', 'default')->count())->toBe(1);
});

test('deleting a failed job only removes that job', function (): void {
    insertFailedJob('job-to-delete');
    insertFailedJob('job-to-keep');

    Livewire::test(JobMonitor::class)->call('deleteFailedJob', 'job-to-delete');

    expect(DB::table('failed_jobs')->pluck('uuid')->all())->toBe(['job-to-keep']);
});
