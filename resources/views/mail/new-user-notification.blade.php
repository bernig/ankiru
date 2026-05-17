{{-- blade-formatter-disable --}}
<x-mail::layout>
<x-slot:header>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>
</x-slot:header>

## {{ __('mail.new_user.heading') }}

{{ __('mail.new_user.intro', ['app' => config('app.name')]) }}

<x-mail::table>
| | |
|:---|:---|
| **{{ __('mail.new_user.col_name') }}** | {{ $user->name }} |
| **{{ __('mail.new_user.col_email') }}** | [{{ $user->email }}](mailto:{{ $user->email }}) |
| **{{ __('mail.new_user.col_registered_at') }}** | {{ $user->created_at->format('d/m/Y à H:i') }} |
</x-mail::table>

<x-slot:footer>
    <x-mail::footer>
        © {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
    </x-mail::footer>
</x-slot:footer>
</x-mail::layout>
