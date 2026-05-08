<div class="mx-auto mt-16 max-w-lg">
    <flux:card class="p-8 text-center">
        <flux:icon.document-text class="mx-auto mb-4 size-12 text-zinc-400" />
        <flux:heading class="mb-1" size="lg">{{ __('csv_editor.upload_heading') }}</flux:heading>
        <flux:text class="mb-6 text-zinc-500">{{ __('csv_editor.upload_subtext') }}</flux:text>

        <flux:file-upload wire:model="uploadedCsvFile" accept=".csv,text/csv">
            <flux:file-upload.dropzone heading="{{ __('csv_editor.dropzone_heading') }}" text="{{ __('csv_editor.dropzone_text') }}" with-progress />
        </flux:file-upload>

        <flux:error class="mt-2" name="uploadedCsvFile" />

        @if ($validationError)
            <flux:callout class="mt-4 text-left" variant="danger" icon="exclamation-triangle">
                {{ $validationError }}
            </flux:callout>
        @endif

        <div class="mt-3 text-center text-sm text-zinc-500" wire:loading wire:target="uploadedCsvFile">
            {{ __('csv_editor.parsing') }}
        </div>

        <div class="mt-6 flex items-center gap-3">
            <flux:separator class="flex-1" />
            <flux:text class="text-zinc-400">{{ __('csv_editor.or') }}</flux:text>
            <flux:separator class="flex-1" />
        </div>

        <flux:button class="mt-4 w-full" icon:variant="outline" wire:click="createNewFile" icon="document-plus">
            {{ __('csv_editor.create_new_file') }}
        </flux:button>
    </flux:card>
</div>
