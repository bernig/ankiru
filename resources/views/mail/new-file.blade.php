{{-- blade-formatter-disable --}}
<x-mail::layout>
<x-slot:header>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>
</x-slot:header>

## Nouveau fichier créé

Un utilisateur vient de créer ou importer un nouveau fichier sur **{{ config('app.name') }}**.

<x-mail::table>
| | |
|:---|:---|
| **Fichier** | {{ $draft->original_file_name ?: '(sans nom)' }} |
| **Utilisateur** | {{ $draft->user->name }} |
| **Email** | [{{ $draft->user->email }}](mailto:{{ $draft->user->email }}) |
| **Date** | {{ $draft->created_at->format('d/m/Y à H:i') }} |
</x-mail::table>

<x-slot:footer>
    <x-mail::footer>
        © {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
    </x-mail::footer>
</x-slot:footer>
</x-mail::layout>
