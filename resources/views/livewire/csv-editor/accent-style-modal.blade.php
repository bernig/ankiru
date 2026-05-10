{{--
    Accent Style modal.

    State is sourced from the Livewire component ($accentColor / $accentBold),
    which are loaded from the database on mount and persisted by saveAccentStyle().

    CSS vars are updated immediately on save (via window.applyAccentStyle) so the
    visual change is instant without waiting for the Livewire round-trip.

    Constraint: at least one of color or bold must be active so that stressed
    vowels remain visually distinguishable. When no color is selected the bold
    toggle is locked on automatically.
--}}
<flux:modal class="md:w-sm" name="accent-style">
    <div class="flex flex-col gap-6" x-data="{
        color: '',
        bold: true,
        init() {
            this.color = $wire.accentColor || '';
            this.bold = $wire.accentBold;
            this.$watch('color', (val) => {
                if (!val) { this.bold = true; }
            });
        },
        save() {
            const color = this.color || null;
            const bold = this.bold;
            window.applyAccentStyle(color, bold);
            $wire.saveAccentStyle(color, bold);
        },
    }">
        <flux:heading size="lg">{{ __('csv_editor.accent_style_title') }}</flux:heading>

        <div class="flex flex-col gap-5">
            {{-- Color picker --}}
            <div class="flex flex-col gap-2">
                <flux:label>{{ __('csv_editor.accent_color') }}</flux:label>

                <flux:color-picker type="button" clearable wire:ignore x-model="color" />

                <flux:text class="text-xs" x-show="!color">
                    {{ __('csv_editor.accent_no_color_note') }}
                </flux:text>
            </div>

            {{-- Bold toggle --}}
            <flux:switch wire:ignore :label="__('csv_editor.accent_bold')" x-model="bold" x-bind:disabled="!color" />
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
