@php
    $totalCards = count($testQueue);
@endphp

<flux:modal class="md:w-2xl" name="test-mode" x-on:open-test-mode.window="$flux.modal('test-mode').show()" x-on:close="$wire.closeTestMode()" closable>
    <div class="flex flex-col gap-5" x-data="{
        flip() {
                $wire.testCardFlipped = true;
                $wire.testCardAudioUrl = $wire.testCardAudioUrls[$wire.testQueue[$wire.testQueuePosition]] ?? null;
            },
            next() {
                $wire.testCardFlipped = false;
                $wire.testCardAudioUrl = null;
                const nextPos = $wire.testQueuePosition + 1;
                if (nextPos >= $wire.testQueue.length) {
                    $wire.testSessionDone = true;
                } else {
                    $wire.testQueuePosition = nextPos;
                }
            },
            init() {
                this.$wire.$watch('testCardAudioUrl', (url) => {
                    if (url && this.$wire.testCardFlipped && this.$wire.testAutoplay) {
                        this.$nextTick(() => window.playAudioWhenReady(document.getElementById('test-audio')));
                    }
                });
            },
    }" @keydown.space.window.prevent="
            if ($wire.testModeOpen && !$wire.testSessionDone && !$wire.testCardFlipped && $wire.testQueue.length > 0) {
                flip();
            }
        " @keydown.enter.window.prevent="
            if ($wire.testModeOpen && !$wire.testSessionDone && $wire.testCardFlipped) {
                next();
            }
        ">

        @if ($totalCards === 0)

            {{-- ── Deck vide ── --}}
            <div class="flex flex-col items-center gap-4 py-6 text-center">
                <flux:icon.rectangle-stack class="size-14 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg">{{ __('csv_editor.practice_empty') }}</flux:heading>
                <flux:text class="text-zinc-500">{{ __('csv_editor.practice_empty_description') }}</flux:text>
            </div>
        @else
            {{-- ── Session terminée ── --}}
            <div class="flex flex-col items-center gap-4 py-6 text-center" x-show="$wire.testSessionDone" x-cloak>
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

            {{-- ── Jeu de cartes ── --}}
            <div class="flex flex-col gap-5" x-show="!$wire.testSessionDone">

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
                        <p class="text-xl font-medium text-zinc-900 sm:text-2xl dark:text-zinc-100" x-html="$wire.csvRows[$wire.testQueue[$wire.testQueuePosition]]?.[0] ?? ''"></p>
                    </div>

                    {{-- Séparateur + verso (affiché quand retourné) --}}
                    <div x-show="$wire.testCardFlipped" x-cloak>
                        <div class="my-4 border-t border-zinc-100 dark:border-zinc-700"></div>

                        <div class="flex flex-col items-center gap-3 py-2 text-center">
                            <div class="cursor-default text-xl font-medium text-zinc-900 sm:text-2xl dark:text-zinc-100" x-html="window.csvAccentMode.buildHtml($wire.csvRows[$wire.testQueue[$wire.testQueuePosition]]?.[1] ?? '')" @click="
                                    const t = $wire.csvRows[$wire.testQueue[$wire.testQueuePosition]]?.[1] ?? '';
                                    window.csvAccentMode.invalidateCache(t);
                                    window.csvAccentMode.handleClick($event, $wire, 'cell', $wire.testQueue[$wire.testQueuePosition], 1)
                                "></div>

                            <audio class="mt-1 w-full max-w-xs rounded" id="test-audio" :src="$wire.testCardAudioUrl ?? ''" :class="{ 'hidden': !$wire.testCardAudioUrl }" controls lang="ru"></audio>

                            <flux:button class="rounded-full!" x-show="!$wire.testCardAudioUrl && $wire.testCardFlipped" x-cloak icon="speaker-wave" icon:variant="outline" size="sm" variant="primary" wire:click="generateTestCardAudio" wire:loading.attr="disabled" wire:target="generateTestCardAudio">
                                {{ __('csv_editor.generate_audio') }}
                            </flux:button>
                        </div>
                    </div>
                </flux:card>

                {{-- ── Boutons d'action ── --}}
                <div class="flex justify-center">
                    <flux:button class="rounded-full! px-8!" x-show="!$wire.testCardFlipped" variant="primary" @click="flip()">
                        {{ __('csv_editor.practice_flip') }}
                    </flux:button>
                    <flux:button class="rounded-full! px-8!" x-show="$wire.testCardFlipped" x-cloak variant="primary" @click="next()">
                        {{ __('csv_editor.practice_next') }}
                    </flux:button>
                </div>

            </div>

        @endif

    </div>
</flux:modal>
