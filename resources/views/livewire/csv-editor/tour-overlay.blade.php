@php
    $tourSteps = [
        ['target' => 'file-menu', 'title' => __('csv_editor.tour_file_menu_title'), 'text' => __('csv_editor.tour_file_menu_text')],
        ['target' => 'search', 'title' => __('csv_editor.tour_search_title'), 'text' => __('csv_editor.tour_search_text')],
        ['target' => 'legend', 'title' => __('csv_editor.tour_legend_title'), 'text' => __('csv_editor.tour_legend_text')],
        ['target' => 'practice-mode', 'title' => __('csv_editor.tour_practice_title'), 'text' => __('csv_editor.tour_practice_text')],
        ['target' => 'add-row', 'title' => __('csv_editor.tour_add_row_title'), 'text' => __('csv_editor.tour_add_row_text')],
        ['target' => 'bulk-actions', 'title' => __('csv_editor.tour_bulk_title'), 'text' => __('csv_editor.tour_bulk_text')],
        ['target' => 'export', 'title' => __('csv_editor.tour_export_title'), 'text' => __('csv_editor.tour_export_text')],
    ];

    $newFileTipSteps = [
        ['target' => 'add-row', 'title' => __('csv_editor.tour_new_file_tip_title'), 'text' => __('csv_editor.tour_new_file_tip_text')],
    ];

    $firstRowTipSteps = [
        ['target' => 'first-row', 'title' => __('csv_editor.tour_first_row_tip_title'), 'text' => __('csv_editor.tour_first_row_tip_text')],
    ];
@endphp

<div x-data x-init="$store.tour.init(@js($tourSteps))" x-on:start-main-tour.window="$store.tour.start()" x-on:start-new-file-tip.window="$store.tour.startWith(@js($newFileTipSteps))" x-on:start-first-row-tip.window="$store.tour.startWith(@js($firstRowTipSteps))" x-cloak>
    {{-- Click-blocking backdrop — clicking it also stops the tour --}}
    <div class="fixed inset-0 z-[9997]" x-show="$store.tour.active" x-transition.opacity @click="$store.tour.stop()" @keydown.escape.window="$store.tour.stop()"></div>

    {{-- Spotlight ring around the highlighted element (dimming + ring box-shadow computed in spotlightStyle, see tour.js) --}}
    <div class="pointer-events-none fixed z-[9998] rounded-lg transition-all duration-300 ease-out" x-show="$store.tour.active && $store.tour.rect" :style="$store.tour.spotlightStyle"></div>

    {{-- Instruction card --}}
    <div class="fixed inset-x-4 bottom-4 z-[9999] mx-auto max-w-md rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-800" x-show="$store.tour.active" x-transition @click.stop>
        <template x-if="$store.tour.current">
            <div class="flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <flux:heading size="sm" x-text="$store.tour.current.title"></flux:heading>
                    <button class="shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" type="button" title="{{ __('csv_editor.tour_skip') }}" @click="$store.tour.stop()">
                        <flux:icon.x-mark class="size-4" />
                    </button>
                </div>

                <flux:text x-text="$store.tour.current.text"></flux:text>

                <div class="flex items-center justify-between gap-2 pt-1">
                    <span class="text-xs text-zinc-400" x-text="($store.tour.stepIndex + 1) + ' / ' + $store.tour.steps.length"></span>

                    <div class="flex items-center gap-3">
                        <button class="cursor-pointer text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300" type="button" @click="$store.tour.stop()">
                            {{ __('csv_editor.tour_skip') }}
                        </button>
                        <flux:button size="sm" variant="ghost" x-show="$store.tour.stepIndex > 0" @click="$store.tour.prev()">
                            {{ __('csv_editor.tour_previous') }}
                        </flux:button>
                        <flux:button class="rounded-full!" size="sm" variant="primary" x-show="!$store.tour.isLastStep" @click="$store.tour.next()">
                            {{ __('csv_editor.tour_next') }}
                        </flux:button>
                        <flux:button class="rounded-full!" size="sm" variant="primary" x-show="$store.tour.isLastStep" @click="$store.tour.next()">
                            {{ __('csv_editor.tour_finish') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
