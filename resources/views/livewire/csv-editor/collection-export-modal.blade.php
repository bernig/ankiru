<flux:modal class="md:w-96" name="collection-export">
    <div class="flex flex-col gap-6">
        <flux:heading size="lg">{{ __('csv_editor.collection_name_modal_title') }}</flux:heading>

        <flux:input
            wire:model="collectionExportName"
            wire:keydown.enter="downloadColpkg; $flux.modal('collection-export').close()"
            :label="__('csv_editor.collection_name_label')"
            :placeholder="__('csv_editor.collection_name_placeholder')"
            autofocus
        />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('csv_editor.close') }}</flux:button>
            </flux:modal.close>

            <flux:button
                variant="primary"
                wire:click="downloadColpkg"
                x-on:click="$flux.modal('collection-export').close()"
                wire:loading.attr="disabled"
                wire:target="downloadColpkg"
            >
                {{ __('csv_editor.export') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
