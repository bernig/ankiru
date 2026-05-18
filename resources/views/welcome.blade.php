<x-layout :title="config('app.name')" :description="__('welcome.seo_description')">
    <x-site-header />

    @php
        $mainGoals = [
            [
                'icon' => 'document-text',
                'iconClass' => 'bg-violet-100 text-violet-600',
                'title' => __('welcome.goal_input_title'),
                'description' => __('welcome.goal_input_description'),
            ],
            [
                'icon' => 'speaker-wave',
                'iconClass' => 'bg-sky-100 text-sky-600',
                'title' => __('welcome.goal_pronunciation_title'),
                'description' => __('welcome.goal_pronunciation_description'),
            ],
            [
                'icon' => 'arrow-down-tray',
                'iconClass' => 'bg-emerald-100 text-emerald-600',
                'title' => __('welcome.goal_export_title'),
                'description' => __('welcome.goal_export_description'),
            ],
        ];

        $workflowSteps = [
            [
                'number' => '1',
                'title' => __('welcome.workflow_step_1_title'),
                'description' => __('welcome.workflow_step_1_description'),
            ],
            [
                'number' => '2',
                'title' => __('welcome.workflow_step_2_title'),
                'description' => __('welcome.workflow_step_2_description'),
            ],
            [
                'number' => '3',
                'title' => __('welcome.workflow_step_3_title'),
                'description' => __('welcome.workflow_step_3_description'),
            ],
            [
                'number' => '4',
                'title' => __('welcome.workflow_step_4_title'),
                'description' => __('welcome.workflow_step_4_description'),
            ],
        ];

        $featureCards = [
            [
                'icon' => 'sparkles',
                'iconClass' => 'bg-purple-100 text-purple-600',
                'title' => __('welcome.feature_generation_title'),
                'description' => __('welcome.feature_generation_description'),
            ],
            [
                'icon' => 'language',
                'iconClass' => 'bg-violet-100 text-violet-600',
                'title' => __('welcome.feature_translation_title'),
                'description' => __('welcome.feature_translation_description'),
            ],
            [
                'icon' => 'pencil-square',
                'iconClass' => 'bg-amber-100 text-amber-600',
                'title' => __('welcome.feature_stress_title'),
                'description' => __('welcome.feature_stress_description'),
            ],
            [
                'icon' => 'speaker-wave',
                'iconClass' => 'bg-sky-100 text-sky-600',
                'title' => __('welcome.feature_tts_title'),
                'description' => __('welcome.feature_tts_description'),
            ],
            [
                'icon' => 'arrow-down-tray',
                'iconClass' => 'bg-emerald-100 text-emerald-600',
                'title' => __('welcome.feature_export_title'),
                'description' => __('welcome.feature_export_description'),
            ],
            [
                'icon' => 'play',
                'iconClass' => 'bg-violet-100 text-violet-600',
                'title' => __('welcome.feature_practice_title'),
                'description' => __('welcome.feature_practice_description'),
            ],
        ];
    @endphp

    <div class="mx-auto max-w-5xl space-y-20 py-12 sm:py-16">
        {{-- Hero --}}
        <section class="mx-auto max-w-3xl text-center">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-sm font-medium text-violet-700">
                <flux:icon.sparkles class="size-4" />
                <span>{{ __('welcome.hero_badge') }}</span>
            </div>

            <img class="mx-auto mb-6 size-16" src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" />

            <h1 class="mb-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                {{ __('welcome.hero_title') }}
            </h1>

            <p class="mx-auto mb-10 max-w-2xl text-lg leading-8 text-zinc-600">
                {{ __('welcome.hero_description') }}
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <flux:button class="rounded-full! px-6!" href="{{ route('register') }}" variant="primary" wire:navigate>
                    {{ __('welcome.cta_register') }}
                </flux:button>
                <flux:button class="rounded-full! px-6!" href="{{ route('login') }}" variant="ghost" wire:navigate>
                    {{ __('welcome.cta_login') }}
                </flux:button>
            </div>

            <div class="mx-auto mt-8 max-w-2xl rounded-2xl border border-emerald-200 bg-emerald-50/80 p-5 text-left">
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <flux:icon.banknotes class="size-5" />
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-emerald-900">
                            {{ __('welcome.pricing_title') }}
                        </p>
                        <p class="mt-1 text-sm leading-6 text-emerald-800">
                            {{ __('welcome.pricing_description') }}
                        </p>
                    </div>
                </div>
            </div>

            <a class="shadow-xs mt-6 inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-4 py-1.5 text-xs text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-800" href="https://github.com/bernig/ankiru" target="_blank" rel="noopener noreferrer">
                <svg class="size-3.5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 4.418 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.009-.868-.013-1.703-2.782.604-3.369-1.341-3.369-1.341-.454-1.154-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0 1 12 6.836a9.59 9.59 0 0 1 2.504.337c1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.202 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.163 22 16.418 22 12c0-5.523-4.477-10-10-10z" />
                </svg>
                <span class="font-medium text-zinc-700">{{ __('welcome.open_source_badge') }}</span>
                <span>·</span>
                <span>{{ __('welcome.open_source_label') }}</span>
            </a>
        </section>

        {{-- Main goals --}}
        <section class="space-y-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">
                    {{ __('welcome.goals_title') }}
                </h2>
                <p class="mt-3 text-base leading-7 text-zinc-600">
                    {{ __('welcome.goals_description') }}
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($mainGoals as $mainGoal)
                    <div class="shadow-xs rounded-2xl border border-zinc-200 bg-white p-6">
                        <div class="{{ $mainGoal['iconClass'] }} mb-4 flex size-11 items-center justify-center rounded-xl">
                            <flux:icon class="size-5" :name="$mainGoal['icon']" />
                        </div>

                        <h3 class="mb-2 text-base font-semibold text-zinc-900">{{ $mainGoal['title'] }}</h3>
                        <p class="text-sm leading-6 text-zinc-500">{{ $mainGoal['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Workflow --}}
        <section class="space-y-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">
                    {{ __('welcome.workflow_title') }}
                </h2>
                <p class="mt-3 text-base leading-7 text-zinc-600">
                    {{ __('welcome.workflow_description') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($workflowSteps as $workflowStep)
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6">
                        <div class="mb-4 flex size-10 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white">
                            {{ $workflowStep['number'] }}
                        </div>

                        <h3 class="mb-2 text-base font-semibold text-zinc-900">{{ $workflowStep['title'] }}</h3>
                        <p class="text-sm leading-6 text-zinc-600">{{ $workflowStep['description'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Anki callout --}}
            <div class="rounded-2xl border border-blue-200 bg-blue-50/80 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                        <flux:icon.rectangle-stack class="size-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-blue-900">{{ __('welcome.anki_callout_title') }}</p>
                        <p class="mt-1 text-sm leading-6 text-blue-800">{{ __('welcome.anki_callout_body') }}</p>
                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1">
                            <a class="text-xs font-medium text-blue-700 underline-offset-2 hover:underline" href="https://apps.ankiweb.net" target="_blank" rel="noopener noreferrer">{{ __('welcome.anki_callout_desktop') }} ↗</a>
                            <a class="text-xs font-medium text-blue-700 underline-offset-2 hover:underline" href="https://play.google.com/store/apps/details?id=com.ichi2.anki" target="_blank" rel="noopener noreferrer">{{ __('welcome.anki_callout_android') }} ↗</a>
                            <a class="text-xs font-medium text-blue-700 underline-offset-2 hover:underline" href="https://apps.apple.com/us/app/ankimobile-flashcards/id373493387" target="_blank" rel="noopener noreferrer">{{ __('welcome.anki_callout_ios') }} ↗</a>
                            <a class="text-xs font-medium text-blue-700 underline-offset-2 hover:underline" href="{{ route('faq') }}#anki-basics" wire:navigate>{{ __('welcome.anki_callout_learn_more') }} →</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Included tools --}}
        <section class="space-y-8 pb-4">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">
                    {{ __('welcome.features_title') }}
                </h2>
                <p class="mt-3 text-base leading-7 text-zinc-600">
                    {{ __('welcome.features_description') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($featureCards as $featureCard)
                    <div class="shadow-xs rounded-2xl border border-zinc-200 bg-white p-6">
                        <div class="{{ $featureCard['iconClass'] }} mb-3 flex size-10 items-center justify-center rounded-lg">
                            <flux:icon class="size-5" :name="$featureCard['icon']" />
                        </div>

                        <h3 class="mb-1 font-semibold text-zinc-900">{{ $featureCard['title'] }}</h3>
                        <p class="text-sm leading-6 text-zinc-500">{{ $featureCard['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layout>
