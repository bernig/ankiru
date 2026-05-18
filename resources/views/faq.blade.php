<x-layout :title="__('faq.title')" :description="__('faq.seo_description')">
    <x-site-header />

    <div class="mx-auto max-w-2xl space-y-10">
        <div>
            <x-breadcrumbs>
                <flux:breadcrumbs.item>{{ __('faq.title') }}</flux:breadcrumbs.item>
            </x-breadcrumbs>

            <flux:heading class="mb-2" size="xl">{{ __('faq.title') }}</flux:heading>
            <flux:subheading class="mb-8">{{ __('faq.subtitle') }}</flux:subheading>
        </div>

        @php
            $faqSections = [
                [
                    'title' => __('faq.section_anki_title'),
                    'icon' => 'rectangle-stack',
                    'iconClass' => 'bg-blue-100 text-blue-600',
                    'id' => 'anki-basics',
                    'items' => [['q' => __('faq.q_what_is_anki'), 'a' => __('faq.a_what_is_anki')], ['q' => __('faq.q_download_anki'), 'a' => __('faq.a_download_anki')], ['q' => __('faq.q_need_anki'), 'a' => __('faq.a_need_anki')], ['q' => __('faq.q_import_apkg'), 'a' => __('faq.a_import_apkg')]],
                ],
                [
                    'title' => __('faq.section_ai_title'),
                    'icon' => 'sparkles',
                    'iconClass' => 'bg-purple-100 text-purple-600',
                    'items' => [['q' => __('faq.q_ai_reliable'), 'a' => __('faq.a_ai_reliable')], ['q' => __('faq.q_stress_unreliable'), 'a' => __('faq.a_stress_unreliable')], ['q' => __('faq.q_translation_quality'), 'a' => __('faq.a_translation_quality')], ['q' => __('faq.q_tts_quality'), 'a' => __('faq.a_tts_quality')]],
                ],
                [
                    'title' => __('faq.section_api_title'),
                    'icon' => 'key',
                    'iconClass' => 'bg-amber-100 text-amber-600',
                    'items' => [['q' => __('faq.q_why_own_key'), 'a' => __('faq.a_why_own_key')], ['q' => __('faq.q_cost_estimate'), 'a' => __('faq.a_cost_estimate')], ['q' => __('faq.q_key_security'), 'a' => __('faq.a_key_security')], ['q' => __('faq.q_api_credits'), 'a' => __('faq.a_api_credits')]],
                ],
                [
                    'title' => __('faq.section_usage_title'),
                    'icon' => 'cog-6-tooth',
                    'iconClass' => 'bg-sky-100 text-sky-600',
                    'items' => [['q' => __('faq.q_offline'), 'a' => __('faq.a_offline')], ['q' => __('faq.q_other_languages'), 'a' => __('faq.a_other_languages')], ['q' => __('faq.q_data_privacy'), 'a' => __('faq.a_data_privacy')]],
                ],
                [
                    'title' => __('faq.section_export_title'),
                    'icon' => 'arrow-down-tray',
                    'iconClass' => 'bg-emerald-100 text-emerald-600',
                    'items' => [['q' => __('faq.q_how_export_works'), 'a' => __('faq.a_how_export_works')], ['q' => __('faq.q_export_all_at_once'), 'a' => __('faq.a_export_all_at_once')], ['q' => __('faq.q_missing_audio_in_anki'), 'a' => __('faq.a_missing_audio_in_anki')]],
                ],
            ];
        @endphp

        @foreach ($faqSections as $sectionIndex => $faqSection)
            @if ($sectionIndex > 0)
                <flux:separator />
            @endif

            <div class="space-y-4" @isset($faqSection['id']) id="{{ $faqSection['id'] }}" @endisset>
                {{-- Section heading --}}
                <div class="flex items-center gap-3">
                    <div class="{{ $faqSection['iconClass'] }} flex size-8 shrink-0 items-center justify-center rounded-lg">
                        <flux:icon class="size-4" :name="$faqSection['icon']" />
                    </div>
                    <flux:heading size="lg">{{ $faqSection['title'] }}</flux:heading>
                </div>

                {{-- Accordion items for this section --}}
                <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white">
                    @foreach ($faqSection['items'] as $faqItem)
                        <div class="group" x-data="{ isOpen: false }">
                            <button class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left" type="button" @click="isOpen = !isOpen" :aria-expanded="isOpen">
                                <span class="text-sm font-medium text-zinc-800">{{ $faqItem['q'] }}</span>
                                <flux:icon class="size-4 shrink-0 text-zinc-400 transition-transform duration-200" name="chevron-down" ::class="{ 'rotate-180': isOpen }" />
                            </button>

                            <div class="px-5 pb-4" x-show="isOpen" x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1">
                                <p class="text-sm leading-relaxed text-zinc-600">{!! $faqItem['a'] !!}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-layout>
