<div class="space-y-3" wire:poll.2s>

    <div class="flex items-center justify-between">
        <flux:text class="text-sm text-zinc-400">{{ __('admin.log_refresh_info', ['count' => count($this->entries())]) }}</flux:text>
        <flux:select class="w-32" wire:model.live="lines" size="sm">
            <flux:select.option value="50">{{ __('admin.log_lines', ['count' => 50]) }}</flux:select.option>
            <flux:select.option value="100">{{ __('admin.log_lines', ['count' => 100]) }}</flux:select.option>
            <flux:select.option value="250">{{ __('admin.log_lines', ['count' => 250]) }}</flux:select.option>
            <flux:select.option value="500">{{ __('admin.log_lines', ['count' => 500]) }}</flux:select.option>
        </flux:select>
    </div>

    <div class="max-h-[70vh] overflow-auto rounded-lg border border-zinc-200 bg-zinc-950 font-mono text-xs leading-relaxed">
        @forelse ($this->entries() as $entry)
            @php
                $color = match ($entry['level']) {
                    'emergency', 'alert', 'critical', 'error' => 'text-red-400',
                    'warning' => 'text-amber-400',
                    'notice', 'info' => 'text-sky-400',
                    'debug' => 'text-zinc-500',
                    default => 'text-zinc-300',
                };
            @endphp
            <div class="{{ $color }} border-b border-zinc-800 px-4 py-1.5 last:border-0 hover:bg-zinc-900">
                <span class="select-none text-zinc-500">{{ $entry['datetime'] }}</span>
                @if ($entry['level'])
                    <span class="ml-2 font-bold uppercase">{{ $entry['level'] }}</span>
                @endif
                <span class="ml-2 break-all text-zinc-200">{{ $entry['message'] }}</span>
            </div>
        @empty
            <div class="px-4 py-6 text-center text-zinc-500">{{ __('admin.log_empty') }}</div>
        @endforelse
    </div>

</div>
