{{--
    Batch Progress Widget.

    A small fixed card in the bottom-right corner that tracks active bulk
    operations even when the Bulk Actions modal is closed.

    Visibility rules:
      - Rendered server-side whenever either batch is not 'idle'.
      - Client side: hidden when the user dismisses it (x-data: dismissed).
      - Auto-show: Alpine x-effect resets 'dismissed' to false the moment
        either batch transitions to 'running', ensuring a new batch is never
        silently hidden.
--}}
@if ($stressBatchStatus !== 'idle' || $ttsBatchStatus !== 'idle')
    <div class="fixed bottom-4 right-4 z-50 w-72 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900" x-data="{ dismissed: false }" x-effect="if ($wire.stressBatchStatus === 'running' || $wire.ttsBatchStatus === 'running') dismissed = false" x-show="! dismissed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-4 opacity-0">

        {{-- Widget header row --}}
        <div class="flex items-center justify-between border-b border-zinc-100 px-3 py-2 dark:border-zinc-800">
            <flux:text class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                {{ __('csv_editor.bulk_actions') }}
            </flux:text>

            <div class="flex items-center gap-3">
                {{-- "Details" link — re-opens the Bulk Actions modal --}}
                <button class="text-xs text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300" type="button" x-on:click="$flux.modal('bulk-actions').show()">
                    {{ __('csv_editor.bulk_widget_details') }}
                </button>

                {{-- Dismiss button — only available when nothing is running --}}
                <button class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" type="button" aria-label="Dismiss" x-show="$wire.stressBatchStatus !== 'running' && $wire.ttsBatchStatus !== 'running'" x-on:click="dismissed = true">
                    <flux:icon.x-mark class="size-3.5" />
                </button>
            </div>
        </div>

        {{-- Operation rows --}}
        <div class="flex flex-col gap-3 p-3">

            {{-- Stress-correction row --}}
            @if ($stressBatchStatus !== 'idle')
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400">
                            <flux:icon.sparkles class="size-3.5 shrink-0" />
                            <span class="truncate">{{ __('csv_editor.bulk_stress_title') }}</span>
                        </div>

                        @if ($stressBatchStatus === 'running')
                            <span class="shrink-0 text-xs tabular-nums text-zinc-400">
                                {{ $stressBatchProgress }}&thinsp;/&thinsp;{{ $stressBatchTotal }}
                            </span>
                        @else
                            <flux:icon.check-circle class="size-4 shrink-0 text-green-500" />
                        @endif
                    </div>

                    @if ($stressBatchStatus === 'running')
                        <flux:progress class="h-1.5" :value="$stressBatchTotal > 0 ? intval($stressBatchProgress / $stressBatchTotal * 100) : 0" color="blue" />
                        @if ($stressBatchFailed > 0)
                            <span class="text-xs text-red-400">
                                {{ __('csv_editor.bulk_failed', ['count' => $stressBatchFailed]) }}
                            </span>
                        @endif
                    @else
                        <div class="text-xs text-zinc-400 dark:text-zinc-500">
                            @if ($stressBatchCorrectedCount > 0)
                                {{ __('csv_editor.bulk_report_corrected', ['count' => number_format($stressBatchCorrectedCount)]) }}
                            @endif
                            @if ($stressBatchFailed > 0)
                                &middot; <span class="text-red-400">{{ __('csv_editor.bulk_failed', ['count' => $stressBatchFailed]) }}</span>
                            @endif
                            @if ($stressBatchPromptTokens > 0)
                                &middot; {{ number_format($stressBatchPromptTokens + $stressBatchCompletionTokens) }} {{ __('csv_editor.bulk_widget_tokens') }}
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- TTS generation row --}}
            @if ($ttsBatchStatus !== 'idle')
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400">
                            <flux:icon.musical-note class="size-3.5 shrink-0" />
                            <span class="truncate">{{ __('csv_editor.bulk_tts_title') }}</span>
                        </div>

                        @if ($ttsBatchStatus === 'running')
                            <span class="shrink-0 text-xs tabular-nums text-zinc-400">
                                {{ $ttsBatchProgress }}&thinsp;/&thinsp;{{ $ttsBatchTotal }}
                            </span>
                        @else
                            <flux:icon.check-circle class="size-4 shrink-0 text-green-500" />
                        @endif
                    </div>

                    @if ($ttsBatchStatus === 'running')
                        <flux:progress class="h-1.5" :value="$ttsBatchTotal > 0 ? intval($ttsBatchProgress / $ttsBatchTotal * 100) : 0" color="blue" />
                        @if ($ttsBatchFailed > 0)
                            <span class="text-xs text-red-400">
                                {{ __('csv_editor.bulk_failed', ['count' => $ttsBatchFailed]) }}
                            </span>
                        @endif
                    @else
                        <div class="text-xs text-zinc-400 dark:text-zinc-500">
                            @if ($ttsBatchGeneratedCount > 0)
                                {{ __('csv_editor.bulk_report_generated', ['count' => number_format($ttsBatchGeneratedCount)]) }}
                            @endif
                            @if ($ttsBatchFailed > 0)
                                &middot; <span class="text-red-400">{{ __('csv_editor.bulk_failed', ['count' => $ttsBatchFailed]) }}</span>
                            @endif
                            @if ($ttsBatchActualChars > 0)
                                &middot; {{ number_format($ttsBatchActualChars) }} {{ __('csv_editor.bulk_widget_chars') }}
                            @endif
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
@endif
