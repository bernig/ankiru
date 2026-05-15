<flux:modal class="md:w-xl" name="tts-player" x-on:close="
        ttsModalOpen = false;
        ttsModalAudioSrc = null;
        const player = document.getElementById('tts-modal-audio');
        if (player) { player.pause(); player.removeAttribute('src'); }
    ">
    <div class="flex flex-col gap-5">
        <flux:heading class="pr-8" size="lg" x-html="ttsModal.russianText"></flux:heading>

        <div x-show="ttsModal.audioExists">
            <div class="flex flex-col gap-2">
                <audio class="w-full rounded" id="tts-modal-audio" controls lang="ru" :src="ttsModalAudioSrc"></audio>
                <flux:text class="text-xs text-zinc-400" x-text="'{{ __('csv_editor.generated_at', ['date' => '%%DATE%%']) }}'.replace('%%DATE%%', ttsModal.createdAt || '')"></flux:text>
            </div>
        </div>

        <div x-show="!ttsModal.audioExists && ttsModal.rowIndex >= 0">
            <flux:callout variant="warning" icon="speaker-x-mark">
                <flux:callout.text>
                    {{ __('csv_editor.no_audio_yet') }}
                </flux:callout.text>
            </flux:callout>
        </div>

        {{-- Action buttons --}}
        <div class="flex items-center gap-2">
            <flux:spacer />

            <div x-show="ttsModal.rowIndex >= 0 && ttsModal.audioExists" class="flex gap-2">
                <flux:button class="hover:bg-red-50! text-red-600! hover:text-red-700! rounded-full!" variant="subtle" icon:variant="outline" icon="trash" @click="$wire.deleteTtsAudio(ttsModal.rowIndex)" wire:loading.attr="disabled" wire:target="deleteTtsAudio" x-bind:disabled="$wire.ttsBatchStatus === 'running'" />

                <flux:button class="rounded-full!" icon="sparkles" icon:variant="outline" @click="$wire.refreshTtsAudio(ttsModal.rowIndex)" wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="refreshTtsAudio" variant="primary" x-bind:disabled="$wire.ttsBatchStatus === 'running'">
                    {{ __('csv_editor.regenerate') }}
                </flux:button>
            </div>

            <div x-show="ttsModal.rowIndex >= 0 && !ttsModal.audioExists">
                <flux:button class="rounded-full!" variant="primary" icon="speaker-wave" @click="$wire.generateTtsAudio(ttsModal.rowIndex)" wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="generateTtsAudio" x-bind:disabled="$wire.ttsBatchStatus === 'running'">
                    {{ __('csv_editor.generate_audio') }}
                </flux:button>
            </div>

            <flux:modal.close>
                <flux:button class="rounded-full!" icon="check" variant="filled">{{ __('csv_editor.close') }}</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
