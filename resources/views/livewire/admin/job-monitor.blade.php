<div class="space-y-8" wire:poll.5s>

    {{-- Jobs en attente --}}
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <flux:heading size="lg">{{ __('admin.jobs_pending_title') }}</flux:heading>
            @if ($this->pendingJobs()->isNotEmpty())
                <flux:badge color="blue" size="sm">{{ $this->pendingJobs()->count() }}</flux:badge>
            @endif
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('admin.col_job') }}</flux:table.column>
                <flux:table.column>{{ __('admin.col_queue') }}</flux:table.column>
                <flux:table.column>{{ __('admin.col_status') }}</flux:table.column>
                <flux:table.column>{{ __('admin.col_attempts') }}</flux:table.column>
                <flux:table.column>{{ __('admin.col_queued_at') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->pendingJobs() as $job)
                    <flux:table.row :key="$job->id">
                        <flux:table.cell class="font-mono font-medium">{{ $job->display_name }}</flux:table.cell>
                        <flux:table.cell>{{ $job->queue }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($job->is_reserved)
                                <flux:badge color="blue" size="sm">{{ __('admin.job_status_running') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('admin.job_status_waiting') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $job->attempts }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ \Carbon\Carbon::createFromTimestamp($job->created_at)->format('d/m/Y H:i:s') }}
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell class="text-center text-zinc-400" colspan="5">{{ __('admin.jobs_pending_empty') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Batches --}}
    @if ($this->batches()->isNotEmpty())
        <div class="space-y-3">
            <flux:heading size="lg">{{ __('admin.jobs_batches_title') }}</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('admin.col_batch_name') }}</flux:table.column>
                    <flux:table.column>{{ __('admin.col_progress') }}</flux:table.column>
                    <flux:table.column>{{ __('admin.col_status') }}</flux:table.column>
                    <flux:table.column>{{ __('admin.col_queued_at') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->batches() as $batch)
                        <flux:table.row :key="$batch->id">
                            <flux:table.cell class="font-medium">{{ $batch->name ?: '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-24 rounded-full bg-zinc-200">
                                        <div class="{{ $batch->failed_jobs > 0 ? 'bg-red-500' : 'bg-green-500' }} h-1.5 rounded-full" style="width: {{ $batch->progress }}%"></div>
                                    </div>
                                    <span class="text-sm text-zinc-500">
                                        {{ $batch->completed_jobs }}/{{ $batch->total_jobs }}
                                        @if ($batch->failed_jobs > 0)
                                            · <span class="text-red-500">{{ $batch->failed_jobs }} {{ __('admin.jobs_failed_count') }}</span>
                                        @endif
                                    </span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($batch->is_cancelled)
                                    <flux:badge color="zinc" size="sm">{{ __('admin.job_status_cancelled') }}</flux:badge>
                                @elseif ($batch->is_finished)
                                    <flux:badge color="green" size="sm">{{ __('admin.job_status_finished') }}</flux:badge>
                                @else
                                    <flux:badge color="blue" size="sm">{{ __('admin.job_status_running') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">
                                {{ \Carbon\Carbon::createFromTimestamp($batch->created_at)->format('d/m/Y H:i:s') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    {{-- Jobs échoués --}}
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <flux:heading size="lg">{{ __('admin.jobs_failed_title') }}</flux:heading>
            @if ($this->failedJobs()->isNotEmpty())
                <flux:badge color="red" size="sm">{{ $this->failedJobs()->count() }}</flux:badge>
            @endif
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('admin.col_job') }}</flux:table.column>
                <flux:table.column>{{ __('admin.col_queue') }}</flux:table.column>
                <flux:table.column>{{ __('admin.col_exception') }}</flux:table.column>
                <flux:table.column>{{ __('admin.col_failed_at') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->failedJobs() as $job)
                    <flux:table.row :key="$job->id">
                        <flux:table.cell class="font-mono font-medium">{{ $job->display_name }}</flux:table.cell>
                        <flux:table.cell>{{ $job->queue }}</flux:table.cell>
                        <flux:table.cell class="max-w-xs">
                            <span class="block truncate text-sm text-red-600" title="{{ $job->exception_summary }}">
                                {{ $job->exception_summary }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ \Carbon\Carbon::parse($job->failed_at)->format('d/m/Y H:i:s') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="xs" icon="arrow-path" wire:click="retryJob('{{ $job->uuid }}')" wire:confirm="{{ __('admin.job_retry_confirm') }}">
                                    {{ __('admin.job_retry') }}
                                </flux:button>
                                <flux:button size="xs" variant="danger" icon="trash" wire:click="deleteFailedJob('{{ $job->uuid }}')" wire:confirm="{{ __('admin.job_delete_confirm') }}" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell class="text-center text-zinc-400" colspan="5">{{ __('admin.jobs_failed_empty') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

</div>
