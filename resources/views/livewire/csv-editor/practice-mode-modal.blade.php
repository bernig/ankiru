@php
    $totalCards = count($practiceQueue);
    $currentRowIndex = $practiceQueue[$practiceQueuePosition] ?? null;
    $currentSourceText = $currentRowIndex !== null ? $csvRows[$currentRowIndex][0] ?? '' : '';
    $currentRussianText = $currentRowIndex !== null ? $csvRows[$currentRowIndex][1] ?? '' : '';
@endphp

<flux:modal class="md:w-2xl" name="practice-mode" x-on:open-practice-mode.window="$flux.modal('practice-mode').show()" x-on:close="$wire.closePracticeMode()" closable>
    <div class="flex flex-col gap-5">

        @if ($practiceSessionDone || $totalCards === 0)

            {{-- ── Session terminée ── --}}
            <div class="flex flex-col items-center gap-4 py-6 text-center">
                @if ($totalCards === 0)
                    <flux:icon.check-circle class="size-14 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="lg">{{ __('csv_editor.practice_nothing_due') }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ __('csv_editor.practice_nothing_due_description') }}</flux:text>
                @else
                    <flux:icon.academic-cap class="size-14 text-green-500" />
                    <flux:heading size="lg">{{ __('csv_editor.practice_session_done') }}</flux:heading>
                    <flux:text class="text-zinc-500">
                        {{ trans_choice('csv_editor.practice_cards_reviewed', $practiceReviewedCount, ['count' => $practiceReviewedCount]) }}
                    </flux:text>
                @endif
            </div>
        @else
            {{-- ── Barre de progression + autoplay ── --}}
            <div class="mr-10 flex items-center gap-3">
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                    <div class="h-full rounded-full bg-purple-500 transition-all duration-300" style="width: {{ $totalCards > 0 ? round(($practiceQueuePosition / $totalCards) * 100) : 0 }}%"></div>
                </div>
                <span class="shrink-0 text-xs font-medium tabular-nums text-zinc-500">
                    {{ $practiceQueuePosition + 1 }} / {{ $totalCards }}
                </span>
                <flux:tooltip :content="__('csv_editor.practice_autoplay_tooltip')">
                    <button class="{{ $practiceAutoplay ? 'text-purple-600' : 'text-zinc-400 dark:text-zinc-500' }} shrink-0 rounded-full p-1 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-700" wire:click="$toggle('practiceAutoplay')">
                        @if ($practiceAutoplay)
                            <flux:icon.speaker-wave class="size-4" />
                        @else
                            <flux:icon.speaker-x-mark class="size-4" />
                        @endif
                    </button>
                </flux:tooltip>
            </div>

            {{-- ── Carte ── --}}
            <flux:card class="relative shadow-sm">

                {{-- Recto (texte source) --}}
                <div class="flex min-h-28 flex-col items-center justify-center gap-2 py-2 text-center">
                    <p class="text-xl font-medium text-zinc-900 sm:text-2xl dark:text-zinc-100">
                        {!! $currentSourceText !!}
                    </p>
                </div>

                @if ($practiceCardFlipped)
                    {{-- Séparateur --}}
                    <div class="my-4 border-t border-zinc-100 dark:border-zinc-700"></div>

                    {{-- Verso (texte russe avec accents cliquables) --}}
                    <div class="flex flex-col items-center gap-3 py-2 text-center" x-data x-init="if ($wire.practiceAutoplay && $wire.practiceCardAudioUrl) {
                        $nextTick(() => {
                            const audio = document.getElementById('practice-audio');
                            if (audio) { audio.play().catch(() => {}); }
                        });
                    }">
                        <div class="cursor-default text-xl font-medium text-zinc-900 sm:text-2xl dark:text-zinc-100" x-html="window.csvAccentMode.buildHtml($wire.csvRows[$wire.practiceQueue[$wire.practiceQueuePosition]]?.[1] ?? '')" @click="
                                const t = $wire.csvRows[$wire.practiceQueue[$wire.practiceQueuePosition]]?.[1] ?? '';
                                window.csvAccentMode.invalidateCache(t);
                                window.csvAccentMode.handleClick($event, $wire, 'cell', $wire.practiceQueue[$wire.practiceQueuePosition], 1)
                            "></div>

                        @if ($practiceCardAudioUrl)
                            <audio class="mt-1 w-full max-w-xs rounded" id="practice-audio" src="{{ $practiceCardAudioUrl }}" controls lang="ru"></audio>
                        @else
                            <audio class="hidden" id="practice-audio"></audio>
                        @endif
                    </div>
                @else
                    <audio class="hidden" id="practice-audio"></audio>
                @endif
            </flux:card>

            {{-- ── Boutons d'action ── --}}
            @if (!$practiceCardFlipped)
                <div class="flex justify-center">
                    <flux:button class="rounded-full! px-8!" variant="primary" wire:click="flipPracticeCard" wire:loading.attr="disabled" wire:target="flipPracticeCard">
                        <flux:icon.loading class="size-4" wire:loading wire:target="flipPracticeCard" />
                        <span wire:loading.remove wire:target="flipPracticeCard">{{ __('csv_editor.practice_flip') }}</span>
                    </flux:button>
                </div>
            @else
                <div class="grid grid-cols-4 gap-2">
                    {{-- Again (0) --}}
                    <flux:button class="flex-col! h-auto! gap-0.5! rounded-xl! py-3! text-red-600!" variant="subtle" wire:click="gradePracticeCard(0)" wire:loading.attr="disabled" wire:target="gradePracticeCard">
                        <span class="text-sm font-semibold">{{ __('csv_editor.practice_grade_again') }}</span>
                        <span class="hidden text-xs font-normal text-zinc-400 sm:inline">{{ __('csv_editor.practice_grade_again_interval') }}</span>
                    </flux:button>

                    {{-- Hard (3) --}}
                    <flux:button class="flex-col! h-auto! gap-0.5! rounded-xl! py-3! text-orange-600!" variant="subtle" wire:click="gradePracticeCard(3)" wire:loading.attr="disabled" wire:target="gradePracticeCard">
                        <span class="text-sm font-semibold">{{ __('csv_editor.practice_grade_hard') }}</span>
                        <span class="hidden text-xs font-normal text-zinc-400 sm:inline">{{ __('csv_editor.practice_grade_hard_interval') }}</span>
                    </flux:button>

                    {{-- Good (4) --}}
                    <flux:button class="flex-col! h-auto! gap-0.5! rounded-xl! py-3! text-green-600!" variant="subtle" wire:click="gradePracticeCard(4)" wire:loading.attr="disabled" wire:target="gradePracticeCard">
                        <span class="text-sm font-semibold">{{ __('csv_editor.practice_grade_good') }}</span>
                        <span class="hidden text-xs font-normal text-zinc-400 sm:inline">{{ __('csv_editor.practice_grade_good_interval') }}</span>
                    </flux:button>

                    {{-- Easy (5) --}}
                    <flux:button class="flex-col! h-auto! gap-0.5! rounded-xl! py-3! text-blue-600!" variant="subtle" wire:click="gradePracticeCard(5)" wire:loading.attr="disabled" wire:target="gradePracticeCard">
                        <span class="text-sm font-semibold">{{ __('csv_editor.practice_grade_easy') }}</span>
                        <span class="hidden text-xs font-normal text-zinc-400 sm:inline">{{ __('csv_editor.practice_grade_easy_interval') }}</span>
                    </flux:button>
                </div>
            @endif

        @endif

    </div>
</flux:modal>
