<div>
    @include('livewire.csv-editor.header')

    @if ($hasCsvLoaded)
        @include('livewire.csv-editor.table')
        @include('livewire.csv-editor.tts-modal')
        @include('livewire.csv-editor.bulk-actions-modal')
    @else
        @include('livewire.csv-editor.upload-panel')
    @endif

    @include('livewire.csv-editor.batch-progress-widget')
</div>
