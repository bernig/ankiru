<flux:breadcrumbs {{ $attributes->merge(['class' => 'mb-6']) }}>
    <flux:breadcrumbs.item href="{{ route('csv-editor') }}" icon="table-cells" icon:variant="outline" />
    {{ $slot }}
</flux:breadcrumbs>
