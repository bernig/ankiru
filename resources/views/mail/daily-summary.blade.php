{{-- blade-formatter-disable --}}
<x-mail::layout>
<x-slot:header>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>
</x-slot:header>

## {{ __('mail.daily_summary.heading', ['date' => $date]) }}

{{ __('mail.daily_summary.intro', ['app' => config('app.name'), 'date' => $date]) }}

<x-mail::table>
| {{ __('mail.daily_summary.col_event') }} | {{ __('mail.daily_summary.col_count') }} |
|:---|---:|
| {{ __('mail.daily_summary.new_users') }} | {{ $stats['new_users'] }} |
| {{ __('mail.daily_summary.new_files') }} | {{ $stats['new_files'] }} |
| {{ __('mail.daily_summary.translations') }} | {{ $stats['translations'] }} |
| {{ __('mail.daily_summary.stress_corrections') }} | {{ $stats['stress_corrections'] }} |
| {{ __('mail.daily_summary.tts_generations') }} | {{ $stats['tts_generations'] }} |
</x-mail::table>

<x-slot:footer>
    <x-mail::footer>
        © {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
    </x-mail::footer>
</x-slot:footer>
</x-mail::layout>
