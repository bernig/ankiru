{{--
    Accent Style modal.

    State is sourced from the Livewire component ($accentColor / $accentBold /
    $accentUnicode), which are loaded from the database on mount and persisted
    by saveAccentStyle().

    All combinations are valid, including "none" (no color, no bold, no unicode),
    in which case accented vowels are rendered as plain <span data-vowel-pos>
    elements: invisible but still clickable so the stored accent position is kept.
--}}
<flux:modal class="md:w-sm" name="accent-style">
    <div class="flex flex-col gap-6" x-data="{
        color: '',
        bold: true,
        unicode: false,
        get noneActive() { return !this.color && !this.bold && !this.unicode; },
        init() {
            this.color = $wire.accentColor || '';
            this.bold = $wire.accentBold;
            this.unicode = $wire.accentUnicode;
        },
        save() {
            const color = this.color || null;
            const bold = this.bold;
            const unicode = this.unicode;
            window.applyAccentStyle(color, bold, unicode);
            $wire.saveAccentStyle(color, bold, unicode);
        },
    }">
        <flux:heading size="lg">{{ __('csv_editor.accent_style_title') }}</flux:heading>

        <div class="flex flex-col gap-5">
            {{-- Unicode combining accent toggle --}}
            <flux:switch wire:ignore :label="__('csv_editor.accent_unicode')" :description="__('csv_editor.accent_unicode_description')" x-model="unicode" />

            {{-- Color picker --}}
            <div class="flex flex-col gap-2">
                <flux:label>{{ __('csv_editor.accent_color') }}</flux:label>

                <flux:color-picker type="button" clearable wire:ignore x-model="color" />
            </div>

            {{-- Bold toggle --}}
            <flux:switch wire:ignore :label="__('csv_editor.accent_bold')" x-model="bold" />

            {{-- "None active" hint --}}
            <flux:callout x-show="noneActive" variant="warning" icon="eye-slash">
                <flux:callout.text>{{ __('csv_editor.accent_none_note') }}</flux:callout.text>
            </flux:callout>
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button>{{ __('csv_editor.close') }}</flux:button>
            </flux:modal.close>
            <flux:modal.close>
                <flux:button variant="primary" x-on:click="save()">
                    {{ __('csv_editor.accent_style_save') }}
                </flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
