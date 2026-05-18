@php
    $totalCards = count($testQueue);
    $currentRowIndex = $testQueue[$testQueuePosition] ?? null;
    $currentSourceText = $currentRowIndex !== null ? $csvRows[$currentRowIndex][0] ?? '' : '';
@endphp

<flux:modal class="md:w-2xl" name="test-mode" x-on:open-test-mode.window="$flux.modal('test-mode').show()" x-on:close="$wire.closeTestMode()" closable>
    <div class="flex flex-col gap-5" x-data @keydown.space.window.prevent="
            if ($wire.testModeOpen && !$wire.testSessionDone && !$wire.testCardFlipped && {{ $totalCards }} > 0) {
                $wire.flipTestCard();
            }
        " @keydown.enter.window.prevent="
            if ($wire.testModeOpen && !$wire.testSessionDone && $wire.testCardFlipped) {
                $wire.nextTestCard();
            }
        ">

        @if ($totalCards === 0)

            {{-- ── Deck vide ── --}}
            <div class="flex flex-col items-center gap-4 py-6 text-center">
                <flux:icon.rectangle-stack class="size-14 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg">{{ __('csv_editor.practice_empty') }}</flux:heading>
                <flux:text class="text-zinc-500">{{ __('csv_editor.practice_empty_description') }}</flux:text>
            </div>
        @elseif ($testSessionDone)
            {{-- ── Session terminée ── --}}
            <div class="flex flex-col items-center gap-4 py-6 text-center">
                <flux:icon.check-circle class="size-14 text-green-500" />
                <div>
                    <flux:heading size="lg">{{ __('csv_editor.practice_session_done') }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500">
                        {{ trans_choice('csv_editor.practice_session_done_description', $totalCards, ['count' => $totalCards]) }}
                    </flux:text>
                </div>
                <flux:button class="rounded-full! px-6!" variant="primary" wire:click="restartTest">
                    {{ __('csv_editor.practice_restart') }}
                </flux:button>
            </div>
        @else
            {{-- ── Autoplay toggle ── --}}
            <div class="mr-10 flex justify-end">
                <flux:tooltip :content="__('csv_editor.practice_autoplay_tooltip')">
                    <button class="{{ $testAutoplay ? 'text-purple-600' : 'text-zinc-400 dark:text-zinc-500' }} shrink-0 rounded-full p-1 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-700" wire:click="$toggle('testAutoplay')">
                        @if ($testAutoplay)
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

                @if ($testCardFlipped)
                    {{-- Séparateur --}}
                    <div class="my-4 border-t border-zinc-100 dark:border-zinc-700"></div>

                    {{-- Verso (texte russe avec accents cliquables) --}}
                    <div class="flex flex-col items-center gap-3 py-2 text-center" x-data x-init="if ($wire.testAutoplay && $wire.testCardAudioUrl) {
                        $nextTick(() => window.playAudioWhenReady(document.getElementById('test-audio')));
                    }">
                        <div class="cursor-default text-xl font-medium text-zinc-900 sm:text-2xl dark:text-zinc-100" x-html="window.csvAccentMode.buildHtml($wire.csvRows[$wire.testQueue[$wire.testQueuePosition]]?.[1] ?? '')" @click="
                                const t = $wire.csvRows[$wire.testQueue[$wire.testQueuePosition]]?.[1] ?? '';
                                window.csvAccentMode.invalidateCache(t);
                                window.csvAccentMode.handleClick($event, $wire, 'cell', $wire.testQueue[$wire.testQueuePosition], 1)
                            "></div>

                        @if ($testCardAudioUrl)
                            <audio class="mt-1 w-full max-w-xs rounded" id="test-audio" src="{{ $testCardAudioUrl }}" controls lang="ru"></audio>
                        @else
                            <audio class="hidden" id="test-audio"></audio>
                        @endif
                    </div>
                @else
                    <audio class="hidden" id="test-audio"></audio>
                @endif
            </flux:card>

            {{-- ── Boutons d'action ── --}}
            @if (!$testCardFlipped)
                <div class="flex justify-center">
                    <flux:button class="rounded-full! px-8!" variant="primary" wire:click="flipTestCard" wire:loading.attr="disabled" wire:target="flipTestCard">
                        <flux:icon.loading class="size-4" wire:loading wire:target="flipTestCard" />
                        <span wire:loading.remove wire:target="flipTestCard">{{ __('csv_editor.practice_flip') }}</span>
                    </flux:button>
                </div>
            @else
                <div class="flex justify-center">
                    <flux:button class="rounded-full! px-8!" variant="primary" wire:click="nextTestCard" wire:loading.attr="disabled" wire:target="nextTestCard">
                        <flux:icon.loading class="size-4" wire:loading wire:target="nextTestCard" />
                        <span wire:loading.remove wire:target="nextTestCard">{{ __('csv_editor.practice_next') }}</span>
                    </flux:button>
                </div>
            @endif

        @endif

    </div>
</flux:modal>
