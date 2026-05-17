{{-- blade-formatter-disable --}}
<x-mail::layout>
<x-slot:header>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>
</x-slot:header>

## Nouvel utilisateur inscrit

Un nouveau compte vient d'être créé sur **{{ config('app.name') }}**.

<x-mail::table>
| | |
|:---|:---|
| **Nom** | {{ $user->name }} |
| **Email** | [{{ $user->email }}](mailto:{{ $user->email }}) |
| **Date d'inscription** | {{ $user->created_at->format('d/m/Y à H:i') }} |
</x-mail::table>

<x-slot:footer>
    <x-mail::footer>
        © {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
    </x-mail::footer>
</x-slot:footer>
</x-mail::layout>
