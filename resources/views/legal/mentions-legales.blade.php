<x-layout title="Mentions légales">
    <x-site-header />

    <div class="mx-auto max-w-2xl py-8">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>Mentions légales</flux:breadcrumbs.item>
        </x-breadcrumbs>

        <flux:heading class="mb-2" size="xl">Mentions légales</flux:heading>
        <flux:subheading class="mb-8">Informations légales relatives à ce site.</flux:subheading>

        <div class="space-y-6 text-sm text-zinc-700">
            <section>
                <flux:heading class="mb-2" size="lg">Éditeur du site</flux:heading>
                <p>{{ config('app.name') }}<br>Le contenu de cette section sera complété ultérieurement.</p>
            </section>

            <flux:separator />

            <section>
                <flux:heading class="mb-2" size="lg">Hébergement</flux:heading>
                <p>Le contenu de cette section sera complété ultérieurement.</p>
            </section>
        </div>
    </div>
</x-layout>
