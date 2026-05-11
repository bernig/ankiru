{{--@formatter:off--}}
<x-mail::layout>
<x-slot:header>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>
</x-slot:header>

## {{ __('contact.mail_heading') }}

<x-mail::table>
| | |
|:---|:---|
| **{{ __('contact.name') }}** | {{ $senderName }} |
| **{{ __('contact.email') }}** | [{{ $senderEmail }}](mailto:{{ $senderEmail }}) |
| **{{ __('contact.subject') }}** | {{ $emailSubject }} |
</x-mail::table>

<x-mail::panel>
{{ $messageBody }}
</x-mail::panel>

<x-slot:subcopy>
    <x-mail::subcopy>
        {{ __('contact.mail_reply', ['email' => $senderEmail]) }}
    </x-mail::subcopy>
</x-slot:subcopy>

<x-slot:footer>
    <x-mail::footer>
        © {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
    </x-mail::footer>
</x-slot:footer>
</x-mail::layout>
