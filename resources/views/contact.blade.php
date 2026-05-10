<x-layout title="Contact">
    <x-site-header />

    <div class="mx-auto max-w-2xl py-8">
        <x-breadcrumbs>
            <flux:breadcrumbs.item>Contact</flux:breadcrumbs.item>
        </x-breadcrumbs>

        <flux:heading class="mb-2" size="xl">Contact</flux:heading>
        <flux:subheading class="mb-8">Nous sommes disponibles pour répondre à vos questions.</flux:subheading>

        <flux:card class="space-y-4">
            <div class="flex items-center gap-3">
                <flux:icon.envelope-open class="size-5 text-zinc-500" variant="outline" />
                <a class="text-sm hover:underline" href="mailto:contact@example.com">contact@example.com</a>
            </div>
        </flux:card>
    </div>
</x-layout>
