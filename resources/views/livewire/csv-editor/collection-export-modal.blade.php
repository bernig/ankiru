<flux:modal class="min-w-0 md:w-96" name="collection-export">
    <div class="flex flex-col gap-6" x-data="{ name: 'Collection', download(event) { window.followExportLink(event, '{{ route('exports.collection') }}', { name: this.name }); this.$flux.modal('collection-export').close(); } }">
        <flux:heading size="lg">{{ __('csv_editor.collection_name_modal_title') }}</flux:heading>

        <flux:input x-model="name" x-on:keydown.enter="download($event)" :label="__('csv_editor.collection_name_label')" :placeholder="__('csv_editor.collection_name_placeholder')" autofocus />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('csv_editor.close') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="primary" href="{{ route('exports.collection') }}" x-on:click="download($event)">
                {{ __('csv_editor.export') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
