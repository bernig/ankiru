<div>
    {{-- Apply the user's saved accent style to CSS vars on first paint. --}}
    <div class="hidden" x-data x-init="window.applyAccentStyle(@js($accentColor), @js($accentBold))"></div>

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
