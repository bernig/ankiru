<x-layout title="À propos">
    <x-site-header />

    <div class="mx-auto max-w-2xl py-8">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>À propos</flux:breadcrumbs.item>
        </x-breadcrumbs>

        <flux:heading class="mb-2" size="xl">À propos</flux:heading>
        <flux:subheading class="mb-8">Découvrez {{ config('app.name') }} et notre mission.</flux:subheading>

        <div class="prose prose-zinc max-w-none text-sm text-zinc-700">
            <p>{{ config('app.name') }} est un outil conçu pour simplifier la création et la gestion de cartes Anki. Le contenu de cette page sera complété ultérieurement.</p>
        </div>
    </div>
</x-layout>
