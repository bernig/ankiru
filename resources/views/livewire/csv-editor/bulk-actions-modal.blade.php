{{--
    Bulk Actions modal.

    Two independent sections:
      A) Stress-mark correction: one MassOperationJob per qualifying row via GPT
      B) TTS audio generation:   one MassOperationJob per row missing an MP3

    Progress updates arrive primarily via Laravel Echo (Reverb), with a small
    Livewire polling fallback to cover missed events during deploy/reconnect windows.

    Section layout (when not running):
      1. Previous-run summary - shown only when status === 'done'
      2. Current estimates + precise run button, or an all-done checkmark
--}}
<flux:modal class="md:w-xl" name="bulk-actions" :dismissible="$stressBatchStatus !== 'running' && $ttsBatchStatus !== 'running'">
    <div class="flex flex-col gap-6">

        <flux:heading size="lg">{{ __('csv_editor.bulk_actions') }}</flux:heading>

        {{-- ── Infrastructure warnings ─────────────────────────────────────── --}}
        @if (config('queue.default') === 'sync')
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.text>{{ __('csv_editor.bulk_warning_queue') }}</flux:callout.text>
            </flux:callout>
        @endif

        @if (config('broadcasting.default') === 'log')
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.text>{{ __('csv_editor.bulk_warning_broadcast') }}</flux:callout.text>
            </flux:callout>
        @endif

        {{-- ── Section A: Stress-mark correction ──────────────────────────── --}}
        <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">

            {{-- Section header --}}
            <div class="flex items-center gap-2">
                <flux:icon.sparkles class="size-5 shrink-0 text-zinc-500" />
                <flux:text class="font-semibold">{{ __('csv_editor.bulk_stress_title') }}</flux:text>
                <flux:tooltip toggleable :content="__('csv_editor.bulk_stress_tooltip')">
                    <flux:button class="text-zinc-400!" icon="information-circle" variant="ghost" size="xs" />
                </flux:tooltip>
            </div>

            @if ($stressBatchStatus === 'running')

                {{-- Progress bar while the batch is executing --}}
                <flux:field>
                    <flux:label>
                        {{ __('csv_editor.bulk_running') }}
                        <x-slot name="trailing">
                            <span class="text-sm tabular-nums">
                                {{ $stressBatchProgress }} / {{ $stressBatchTotal }}
                            </span>
                        </x-slot>
                    </flux:label>

                    <flux:progress :value="$stressBatchTotal > 0 ? intval($stressBatchProgress / $stressBatchTotal * 100) : 0" color="amber" />

                    @if ($stressBatchFailed > 0)
                        <flux:description class="text-red-500">
                            {{ __('csv_editor.bulk_failed', ['count' => $stressBatchFailed]) }}
                        </flux:description>
                    @endif
                </flux:field>

                <flux:button wire:click="cancelStressBatch" wire:loading.attr="disabled" wire:target="cancelStressBatch" variant="ghost" size="sm" icon="stop">
                    {{ __('csv_editor.bulk_cancel') }}
                </flux:button>
            @else
                {{-- Skeleton while estimates are loading --}}
                <div class="flex flex-col gap-2" wire:loading wire:target="openBulkActionsModal">
                    <flux:skeleton.line class="w-3/4" animate="shimmer" />
                    <flux:skeleton.line class="w-1/2" animate="shimmer" />
                    <flux:skeleton class="mt-1 h-7 w-28 rounded-lg" animate="shimmer" />
                </div>

                {{-- Real content hidden while loading --}}
                <div class="flex flex-col gap-3" wire:loading.remove wire:target="openBulkActionsModal">
                    {{-- Previous-run summary (only visible after a batch has completed) --}}
                    @if ($stressBatchStatus === 'done')
                        <div class="rounded-md bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                            <span class="font-medium">{{ __('csv_editor.bulk_last_run') }} :</span>

                            @if ($stressBatchCorrectedCount > 0)
                                {{ __('csv_editor.bulk_report_corrected', ['count' => number_format($stressBatchCorrectedCount)]) }}
                            @endif

                            @if ($stressBatchFailed > 0)
                                <span class="text-red-400">
                                    · {{ __('csv_editor.bulk_failed', ['count' => $stressBatchFailed]) }}
                                </span>
                            @endif

                            @if ($stressBatchPromptTokens > 0 || $stressBatchCompletionTokens > 0)
                                · {{ __('csv_editor.bulk_report_tokens', [
                                    'input' => number_format($stressBatchPromptTokens),
                                    'output' => number_format($stressBatchCompletionTokens),
                                ]) }}
                            @endif
                        </div>
                    @endif

                    {{-- Current state: all done checkmark OR estimate + run button --}}
                    @if ($missingStressRowCount === 0)
                        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                            <flux:icon.check-circle class="size-4 shrink-0 text-green-500" />
                            {{ __('csv_editor.bulk_all_stress_done') }}
                        </div>
                    @else
                        <flux:text class="text-sm">
                            {{ trans_choice('csv_editor.bulk_rows_need_stress', $missingStressRowCount, ['count' => number_format($missingStressRowCount)]) }}
                        </flux:text>

                        <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">
                            {{ __('csv_editor.bulk_estimate_tokens', [
                                'input' => number_format($estimatedStressInputTokens),
                                'output' => number_format($estimatedStressOutputTokens),
                                'cost' => number_format($estimatedStressCost, 4),
                                'model' => config('services.openai.stress_model_label'),
                            ]) }}
                        </flux:text>

                        <flux:button wire:click="dispatchStressBatch" wire:loading.attr="disabled" wire:target="dispatchStressBatch" variant="primary" size="sm" icon="sparkles">
                            {{ trans_choice('csv_editor.bulk_run_stress', $missingStressRowCount, ['count' => number_format($missingStressRowCount)]) }}
                        </flux:button>
                    @endif
                </div>

            @endif

        </div>

        {{-- ── Section B: TTS audio generation ────────────────────────────── --}}
        <div class="flex flex-col gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">

            {{-- Section header --}}
            <div class="flex items-center gap-2">
                <flux:icon.musical-note class="size-5 shrink-0 text-zinc-500" />
                <flux:text class="font-semibold">{{ __('csv_editor.bulk_tts_title') }}</flux:text>
                <flux:tooltip toggleable :content="__('csv_editor.bulk_tts_tooltip')">
                    <flux:button class="text-zinc-400!" icon="information-circle" variant="ghost" size="xs" />
                </flux:tooltip>
            </div>

            @if ($ttsBatchStatus === 'running')

                {{-- Progress bar while the batch is executing --}}
                <flux:field>
                    <flux:label>
                        {{ __('csv_editor.bulk_running') }}
                        <x-slot name="trailing">
                            <span class="text-sm tabular-nums">
                                {{ $ttsBatchProgress }} / {{ $ttsBatchTotal }}
                            </span>
                        </x-slot>
                    </flux:label>

                    <flux:progress :value="$ttsBatchTotal > 0 ? intval($ttsBatchProgress / $ttsBatchTotal * 100) : 0" color="amber" />

                    @if ($ttsBatchFailed > 0)
                        <flux:description class="text-red-500">
                            {{ __('csv_editor.bulk_failed', ['count' => $ttsBatchFailed]) }}
                        </flux:description>
                    @endif
                </flux:field>

                <flux:button wire:click="cancelTtsBatch" wire:loading.attr="disabled" wire:target="cancelTtsBatch" variant="ghost" size="sm" icon="stop">
                    {{ __('csv_editor.bulk_cancel') }}
                </flux:button>
            @else
                {{-- Skeleton while estimates are loading --}}
                <div class="flex flex-col gap-2" wire:loading wire:target="openBulkActionsModal">
                    <flux:skeleton.line class="w-3/4" animate="shimmer" />
                    <flux:skeleton.line class="w-1/2" animate="shimmer" />
                    <flux:skeleton class="mt-1 h-7 w-28 rounded-lg" animate="shimmer" />
                </div>

                {{-- Real content hidden while loading --}}
                <div class="flex flex-col gap-3" wire:loading.remove wire:target="openBulkActionsModal">
                    {{-- Previous-run summary (only visible after a batch has completed) --}}
                    @if ($ttsBatchStatus === 'done')
                        <div class="rounded-md bg-zinc-50 px-3 py-2 text-xs text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400">
                            <span class="font-medium">{{ __('csv_editor.bulk_last_run') }} :</span>

                            @if ($ttsBatchGeneratedCount > 0)
                                {{ __('csv_editor.bulk_report_generated', ['count' => number_format($ttsBatchGeneratedCount)]) }}
                            @endif

                            @if ($ttsBatchFailed > 0)
                                <span class="text-red-400">
                                    · {{ __('csv_editor.bulk_failed', ['count' => $ttsBatchFailed]) }}
                                </span>
                            @endif

                            @if ($ttsBatchActualChars > 0)
                                · {{ __('csv_editor.bulk_report_chars', ['chars' => number_format($ttsBatchActualChars)]) }}
                            @endif
                        </div>
                    @endif

                    {{-- Current state: all done checkmark OR estimate + run button --}}
                    @if ($missingAudioRowCount === 0)
                        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                            <flux:icon.check-circle class="size-4 shrink-0 text-green-500" />
                            {{ __('csv_editor.bulk_all_audio_done') }}
                        </div>
                    @else
                        <flux:text class="text-sm">
                            {{ trans_choice('csv_editor.bulk_rows_missing_audio', $missingAudioRowCount, ['count' => number_format($missingAudioRowCount)]) }}
                        </flux:text>

                        <flux:text class="text-xs text-zinc-400 dark:text-zinc-500">
                            {{ __('csv_editor.bulk_estimate_chars', [
                                'chars' => number_format($estimatedTtsChars),
                                'cost' => number_format($estimatedTtsCost, 4),
                                'model' => config('services.openai.tts_model_label'),
                            ]) }}
                        </flux:text>

                        <flux:button wire:click="dispatchTtsBatch" wire:loading.attr="disabled" wire:target="dispatchTtsBatch" variant="primary" size="sm" icon="musical-note">
                            {{ trans_choice('csv_editor.bulk_run_tts', $missingAudioRowCount, ['count' => number_format($missingAudioRowCount)]) }}
                        </flux:button>
                    @endif
                </div>

            @endif

        </div>

        {{-- ── Footer ──────────────────────────────────────────────────────── --}}
        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('csv_editor.close') }}</flux:button>
            </flux:modal.close>
        </div>

    </div>
</flux:modal>
