<div class="min-h-screen" x-data="{ ttsAudioUrl: null, ttsModalAudioSrc: null, ttsModalOpen: false }" x-on:tts-audio-ready.window="
        ttsAudioUrl = $event.detail.audioUrl;
        ttsModalAudioSrc = $event.detail.audioUrl;
        $nextTick(() => {
            if (ttsModalOpen) {
                // Modal is visible: play through the modal player only
                const modalPlayer = document.getElementById('tts-modal-audio');
                if (modalPlayer) { modalPlayer.load(); modalPlayer.play(); }
            } else {
                // No modal: play the hidden background player
                if ($refs.ttsPlayer) { $refs.ttsPlayer.load(); $refs.ttsPlayer.play(); }
            }
        });
    " x-on:open-tts-modal.window="
        ttsModalAudioSrc = $event.detail.audioUrl || null;
        ttsModalOpen = true;
        $flux.modal('tts-player').show();
        if ($event.detail.audioUrl) {
            $nextTick(() => {
                const modalPlayer = document.getElementById('tts-modal-audio');
                if (modalPlayer) { modalPlayer.load(); modalPlayer.play(); }
            });
        }
    ">

    {{-- Hidden audio element driven by Alpine.js when TTS audio is ready --}}
    <audio class="hidden" x-ref="ttsPlayer" :src="ttsAudioUrl"></audio>

    @include('livewire.csv-editor.header')

    @if ($hasCsvLoaded)
        @include('livewire.csv-editor.table')
        @include('livewire.csv-editor.tts-modal')
    @else
        @include('livewire.csv-editor.upload-panel')
    @endif

</div>
