<div>
    {{-- Apply the user's saved accent style to CSS vars on first paint. --}}
    <div class="hidden" x-data x-init="window.applyAccentStyle(@js($accentColor), @js($accentBold), @js($accentUnicode))"></div>

    @if ($stressBatchStatus === 'running' || $ttsBatchStatus === 'running')
        {{-- Fallback for missed Reverb updates during deploy / reconnect windows. --}}
        <div class="hidden" wire:poll.3s="refreshRunningBatchProgress"></div>
    @endif

    @include('livewire.csv-editor.header')

    @if ($hasCsvLoaded)
        @include('livewire.csv-editor.table')
        @include('livewire.csv-editor.tts-modal')
        @include('livewire.csv-editor.bulk-actions-modal')
        @include('livewire.csv-editor.collection-export-modal')
        @include('livewire.csv-editor.accent-style-modal')
    @else
        @include('livewire.csv-editor.upload-panel')
    @endif

    @include('livewire.csv-editor.batch-progress-widget')
</div>
