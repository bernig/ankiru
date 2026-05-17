{{-- blade-formatter-disable --}}
<x-mail::layout>
<x-slot:header>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>
</x-slot:header>

## {{ __('mail.new_file.heading') }}

{{ __('mail.new_file.intro', ['app' => config('app.name')]) }}

<x-mail::table>
| | |
|:---|:---|
| **{{ __('mail.new_file.col_file') }}** | {{ $draft->original_file_name ?: __('mail.new_file.unnamed') }} |
| **{{ __('mail.new_file.col_user') }}** | {{ $draft->user->name }} |
| **{{ __('mail.new_file.col_email') }}** | [{{ $draft->user->email }}](mailto:{{ $draft->user->email }}) |
| **{{ __('mail.new_file.col_date') }}** | {{ $draft->created_at->format('d/m/Y à H:i') }} |
</x-mail::table>

<x-slot:footer>
    <x-mail::footer>
        © {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
    </x-mail::footer>
</x-slot:footer>
</x-mail::layout>
