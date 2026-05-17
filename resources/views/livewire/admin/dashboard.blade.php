<div class="space-y-6">

    {{-- Stats globales --}}
    <div class="grid grid-cols-1 gap-3 min-[400px]:grid-cols-2 sm:grid-cols-3 sm:gap-4 lg:grid-cols-6">
        <flux:card class="text-center">
            <flux:heading size="xl">{{ number_format($this->globalStats['users']) }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('admin.stat_users') }}</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:heading size="xl">{{ number_format($this->globalStats['drafts']) }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('admin.stat_files') }}</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:heading size="xl">{{ number_format($this->globalStats['operations']) }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('admin.stat_ai_operations') }}</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:heading size="xl">{{ number_format($this->globalStats['prompt_tokens']) }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('admin.stat_prompt_tokens') }}</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:heading size="xl">{{ number_format($this->globalStats['completion_tokens']) }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('admin.stat_completion_tokens') }}</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <flux:heading size="xl">{{ number_format($this->globalStats['tts_characters']) }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('admin.stat_tts_characters') }}</flux:text>
        </flux:card>
    </div>

    {{-- Onglets --}}
    <flux:tab.group>
        <flux:tabs wire:model.live="tab" scrollable>
            <flux:tab name="users" icon="users">{{ __('admin.tab_users') }}</flux:tab>
            <flux:tab name="drafts" icon="document-text">{{ __('admin.tab_files') }}</flux:tab>
            <flux:tab name="usage" icon="chart-bar">{{ __('admin.tab_ai_usage') }}</flux:tab>
            <flux:tab name="logs" icon="command-line">{{ __('admin.tab_logs') }}</flux:tab>
            <flux:tab name="jobs" icon="queue-list">{{ __('admin.tab_jobs') }}</flux:tab>
        </flux:tabs>

        {{-- Tab: Utilisateurs --}}
        <flux:tab.panel name="users">
            <div class="space-y-4">
                <flux:input wire:model.live.debounce.300ms="userSearch" :placeholder="__('admin.search_users')" icon="magnifying-glass" clearable />

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('admin.col_name') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_email') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_verified') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_files') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_ai_operations') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_admin') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_registered') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" :name="$user->name" circle />
                                        <span class="font-medium">{{ $user->name }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $user->email }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($user->email_verified_at)
                                        <flux:badge color="green" size="sm">{{ __('admin.badge_verified') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('admin.badge_unverified') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ $user->csv_drafts_count }}</flux:table.cell>
                                <flux:table.cell>{{ number_format($user->api_usage_logs_count) }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($user->is_admin)
                                        <flux:badge color="amber" size="sm">{{ __('admin.badge_admin') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500">{{ $user->created_at->format('d/m/Y') }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell class="text-center text-zinc-400" colspan="7">{{ __('admin.no_users') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                <div>{{ $this->users->links() }}</div>
            </div>
        </flux:tab.panel>

        {{-- Tab: Fichiers --}}
        <flux:tab.panel name="drafts">
            <div class="space-y-4">
                <flux:input wire:model.live.debounce.300ms="draftSearch" :placeholder="__('admin.search_files')" icon="magnifying-glass" clearable />

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('admin.col_file') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_user') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_rows') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_loaded') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_last_access') }}</flux:table.column>
                        <flux:table.column>{{ __('admin.col_created') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->drafts as $draft)
                            <flux:table.row :key="$draft->id">
                                <flux:table.cell class="font-medium">{{ $draft->original_file_name ?: __('admin.unnamed') }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-col">
                                        <span>{{ $draft->user->name }}</span>
                                        <span class="text-xs text-zinc-400">{{ $draft->user->email }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ count($draft->csv_rows ?? []) }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($draft->has_csv_loaded)
                                        <flux:badge color="green" size="sm">{{ __('admin.badge_yes') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('admin.badge_no') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500">
                                    {{ $draft->last_accessed_at?->format('d/m/Y H:i') ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500">{{ $draft->created_at->format('d/m/Y') }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell class="text-center text-zinc-400" colspan="6">{{ __('admin.no_files') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                <div>{{ $this->drafts->links() }}</div>
            </div>
        </flux:tab.panel>

        {{-- Tab: Usage IA --}}
        <flux:tab.panel name="usage">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('admin.col_user') }}</flux:table.column>
                    <flux:table.column>{{ __('admin.col_operations') }}</flux:table.column>
                    <flux:table.column>{{ __('admin.col_prompt_tokens') }}</flux:table.column>
                    <flux:table.column>{{ __('admin.col_completion_tokens') }}</flux:table.column>
                    <flux:table.column>{{ __('admin.col_tts_characters') }}</flux:table.column>
                    <flux:table.column>{{ __('admin.col_last_used') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->usageStats as $stat)
                        <flux:table.row :key="$stat->id">
                            <flux:table.cell>
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ $stat->name }}</span>
                                    <span class="text-xs text-zinc-400">{{ $stat->email }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ number_format($stat->total_operations) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($stat->total_prompt_tokens ?? 0) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($stat->total_completion_tokens ?? 0) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($stat->total_characters ?? 0) }}</flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">
                                {{ $stat->last_used_at ? \Carbon\Carbon::parse($stat->last_used_at)->format('d/m/Y H:i') : '—' }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell class="text-center text-zinc-400" colspan="6">{{ __('admin.no_usage') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:tab.panel>

        {{-- Tab: Logs --}}
        <flux:tab.panel name="logs">
            <livewire:admin.log-viewer />
        </flux:tab.panel>

        {{-- Tab: Jobs --}}
        <flux:tab.panel name="jobs">
            <livewire:admin.job-monitor />
        </flux:tab.panel>
    </flux:tab.group>

</div>
