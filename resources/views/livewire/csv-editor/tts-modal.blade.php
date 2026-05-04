@php
    $ttsModalRussianText = $ttsModalRowIndex >= 0 && isset($csvRows[$ttsModalRowIndex])
        ? $csvRows[$ttsModalRowIndex][1] ?? ''
        : '';

    $ttsModalAudioExists = false;
    $ttsModalCreatedAt = null;

    if ($ttsModalRowIndex >= 0 && !empty(trim($ttsModalRussianText))) {
        /** @var \App\Services\RussianTextToSpeechService $ttsServiceForModal */
        $ttsServiceForModal = app(\App\Services\RussianTextToSpeechService::class);
        $ttsModalAudioExists = $ttsServiceForModal->audioFileExists($ttsModalRussianText);

        if ($ttsModalAudioExists) {
            $ttsModalCacheKey = $ttsServiceForModal->hashRawString($ttsModalRussianText);
            $ttsModalLastModified = Storage::disk('local')->lastModified("tts/{$ttsModalCacheKey}.mp3");
            $ttsModalCreatedAt = now()->setTimestamp($ttsModalLastModified)->format('j M Y, H:i');
        }
    }
@endphp

<flux:modal class="md:w-xl" name="tts-player" x-on:close="
        ttsModalOpen = false;
        ttsModalAudioSrc = null;
        const player = document.getElementById('tts-modal-audio');
        if (player) { player.pause(); player.removeAttribute('src'); }
    ">
    <div class="flex flex-col gap-5">
        <flux:heading size="lg">{!! $ttsModalRussianText !!}</flux:heading>

        @if ($ttsModalAudioExists)
            <div class="flex flex-col gap-2">
                {{-- Native audio player; src is driven by Alpine to stay reactive across generate/refresh --}}
                <audio class="w-full rounded" id="tts-modal-audio" controls lang="ru" :src="ttsModalAudioSrc"></audio>

                {{-- File creation date in muted text --}}
                <flux:text class="text-xs text-zinc-400">
                    Generated {{ $ttsModalCreatedAt }}
                </flux:text>
            </div>
        @elseif ($ttsModalRowIndex >= 0)
            <flux:callout variant="warning" icon="speaker-x-mark">
                <flux:callout.text>
                    No audio file generated yet for this phrase.
                </flux:callout.text>
            </flux:callout>
        @endif

        {{-- Action buttons — inside default slot since flux:modal has no footer slot --}}
        <div class="flex items-center gap-2">
            <flux:spacer />

            @if ($ttsModalRowIndex >= 0 && $ttsModalAudioExists)
                {{-- Delete: removes the cached file; modal stays open showing the "no audio" state --}}
                <flux:button variant="danger" icon="trash" wire:click="deleteTtsAudio({{ $ttsModalRowIndex }})" wire:loading.attr="disabled" wire:target="deleteTtsAudio({{ $ttsModalRowIndex }})" />

                {{-- Refresh: deletes + regenerates; tts-audio-ready updates the audio player src --}}
                <flux:button icon="sparkles" wire:click="refreshTtsAudio({{ $ttsModalRowIndex }})" wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="refreshTtsAudio({{ $ttsModalRowIndex }})" variant="primary">
                    Regenerate
                </flux:button>
            @elseif ($ttsModalRowIndex >= 0)
                {{-- Generate: creates audio for the first time --}}
                <flux:button variant="primary" icon="speaker-wave" wire:click="generateTtsAudio({{ $ttsModalRowIndex }})" wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="generateTtsAudio({{ $ttsModalRowIndex }})">
                    Generate Audio
                </flux:button>
            @endif

            <flux:modal.close>
                <flux:button variant="filled">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

