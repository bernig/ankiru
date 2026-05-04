<div class="mx-auto mt-16 max-w-lg">
    <flux:card class="p-8 text-center">
        <flux:icon.document-text class="mx-auto mb-4 size-12 text-zinc-400" />
        <flux:heading class="mb-1" size="lg">Upload a CSV file</flux:heading>
        <flux:text class="mb-6 text-zinc-500">Select a .csv file to start editing.</flux:text>

        <flux:file-upload wire:model="uploadedCsvFile" accept=".csv,text/csv">
            <flux:file-upload.dropzone heading="Drop your CSV here" text="or click to browse" with-progress />
        </flux:file-upload>

        <flux:error class="mt-2" name="uploadedCsvFile" />

        @if ($validationError)
            <flux:callout class="mt-4 text-left" variant="danger" icon="exclamation-triangle">
                {{ $validationError }}
            </flux:callout>
        @endif

        <div class="mt-3 text-center text-sm text-zinc-500" wire:loading wire:target="uploadedCsvFile">
            Parsing…
        </div>
    </flux:card>
</div>

