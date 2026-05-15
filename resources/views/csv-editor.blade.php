<x-layout>
    <div x-data="{
        ttsAudioUrl: null,
        ttsModalAudioSrc: null,
        ttsModalOpen: false,
        ttsModal: { rowIndex: -1, russianText: '', audioExists: false, createdAt: null }
    }"
    x-on:tts-audio-ready.window="
        ttsAudioUrl = $event.detail.audioUrl;
        ttsModalAudioSrc = $event.detail.audioUrl;
        ttsModal.audioExists = true;
        ttsModal.createdAt = $event.detail.createdAt || null;
        $nextTick(() => {
            if (ttsModalOpen) {
                const modalPlayer = document.getElementById('tts-modal-audio');
                if (modalPlayer) { modalPlayer.load(); modalPlayer.play(); }
            } else {
                if ($refs.ttsPlayer) { $refs.ttsPlayer.load(); $refs.ttsPlayer.play(); }
            }
        });
    "
    x-on:open-tts-modal.window="
        ttsModal.rowIndex = $event.detail.rowIndex;
        ttsModal.russianText = $event.detail.russianText;
        ttsModal.audioExists = $event.detail.audioExists;
        ttsModal.createdAt = $event.detail.createdAt || null;
        ttsModalAudioSrc = $event.detail.audioUrl || null;
        ttsModalOpen = true;
        $flux.modal('tts-player').show();
        if ($event.detail.audioUrl) {
            $nextTick(() => {
                const modalPlayer = document.getElementById('tts-modal-audio');
                if (modalPlayer) { modalPlayer.load(); modalPlayer.play(); }
            });
        }
    "
    x-on:tts-audio-deleted.window="
        if ($event.detail.rowIndex === ttsModal.rowIndex) {
            ttsModal.audioExists = false;
            ttsModal.createdAt = null;
            ttsModalAudioSrc = null;
        }
    ">

        {{-- Hidden audio element driven by Alpine.js when TTS audio is ready --}}
        <audio class="hidden" x-ref="ttsPlayer" :src="ttsAudioUrl"></audio>

        <livewire:csv-editor />

    </div>
</x-layout>
