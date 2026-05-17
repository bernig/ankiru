{{-- blade-formatter-disable --}}
<x-mail::layout>
<x-slot:header>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>
</x-slot:header>

## Résumé du {{ $date }}

Voici l'activité enregistrée sur **{{ config('app.name') }}** le {{ $date }}.

<x-mail::table>
| Événement | Nombre |
|:---|---:|
| Nouveaux utilisateurs | {{ $stats['new_users'] }} |
| Nouveaux fichiers créés | {{ $stats['new_files'] }} |
| Traductions effectuées | {{ $stats['translations'] }} |
| Corrections d'accents | {{ $stats['stress_corrections'] }} |
| Générations audio (TTS) | {{ $stats['tts_generations'] }} |
</x-mail::table>

<x-slot:footer>
    <x-mail::footer>
        © {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
    </x-mail::footer>
</x-slot:footer>
</x-mail::layout>
