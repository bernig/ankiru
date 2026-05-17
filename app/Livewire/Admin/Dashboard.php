<?php

namespace App\Livewire\Admin;

use App\Models\ApiUsageLog;
use App\Models\CsvDraft;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public string $tab = 'users';

    public string $userSearch = '';

    public string $draftSearch = '';

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function updatedUserSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDraftSearch(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<User> */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->userSearch, fn ($q) => $q->where(function ($q): void {
                $q->where('name', 'like', "%{$this->userSearch}%")
                    ->orWhere('email', 'like', "%{$this->userSearch}%");
            }))
            ->withCount(['csvDrafts', 'apiUsageLogs'])
            ->latest()
            ->paginate(20);
    }

    /** @return LengthAwarePaginator<CsvDraft> */
    #[Computed]
    public function drafts(): LengthAwarePaginator
    {
        return CsvDraft::query()
            ->with('user:id,name,email')
            ->when($this->draftSearch, fn ($q) => $q->where('original_file_name', 'like', "%{$this->draftSearch}%"))
            ->latest('last_accessed_at')
            ->paginate(20);
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function usageStats(): Collection
    {
        return ApiUsageLog::query()
            ->join('users', 'users.id', '=', 'api_usage_logs.user_id')
            ->selectRaw('
                users.id,
                users.name,
                users.email,
                COUNT(*) as total_operations,
                SUM(prompt_tokens) as total_prompt_tokens,
                SUM(completion_tokens) as total_completion_tokens,
                SUM(characters) as total_characters,
                MAX(api_usage_logs.created_at) as last_used_at
            ')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_operations')
            ->get();
    }

    /** @return array<string, int> */
    #[Computed]
    public function globalStats(): array
    {
        return [
            'users' => User::count(),
            'drafts' => CsvDraft::count(),
            'operations' => ApiUsageLog::count(),
            'prompt_tokens' => (int) ApiUsageLog::sum('prompt_tokens'),
            'completion_tokens' => (int) ApiUsageLog::sum('completion_tokens'),
            'tts_characters' => (int) ApiUsageLog::where('operation', 'tts')->sum('characters'),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard');
    }
}
