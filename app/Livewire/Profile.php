<?php

namespace App\Livewire;

use App\Models\ApiUsageLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $openai_api_key = '';

    public bool $profileSaved = false;

    public bool $passwordSaved = false;

    public bool $apiKeySaved = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    #[Computed]
    public function hasOpenAiKey(): bool
    {
        return (bool) Auth::user()->fresh()->openai_api_key;
    }

    /**
     * Aggregated usage stats per operation, with estimated cost in USD.
     * Cached for 1 hour; automatically invalidated when a new ApiUsageLog is created.
     *
     * @return Collection<int, array{operation: string, calls: int, prompt_tokens: int, completion_tokens: int, characters: int, estimated_cost: float}>
     */
    #[Computed]
    public function usageStats(): Collection
    {
        $inputPricePerMillion = (float) config('services.openai.gpt_5_4_input_price_per_million', 3.00);
        $outputPricePerMillion = (float) config('services.openai.gpt_5_4_output_price_per_million', 15.00);
        $ttsPricePerMillion = (float) config('services.openai.tts_price_per_million_chars', 30.00);

        return Cache::remember(
            'user_usage_stats:'.Auth::id(),
            ttl: 3600,
            callback: function () use ($inputPricePerMillion, $outputPricePerMillion, $ttsPricePerMillion): Collection {
                $rows = ApiUsageLog::query()
                    ->where('user_id', Auth::id())
                    ->selectRaw('operation, COUNT(*) as calls, COALESCE(SUM(prompt_tokens), 0) as prompt_tokens, COALESCE(SUM(completion_tokens), 0) as completion_tokens, COALESCE(SUM(characters), 0) as characters')
                    ->groupBy('operation')
                    ->orderBy('operation')
                    ->get();

                return $rows->map(function (ApiUsageLog $row) use ($inputPricePerMillion, $outputPricePerMillion, $ttsPricePerMillion): array {
                    $cost = match ($row->operation) {
                        'tts' => $row->characters / 1_000_000 * $ttsPricePerMillion,
                        default => $row->prompt_tokens / 1_000_000 * $inputPricePerMillion
                            + $row->completion_tokens / 1_000_000 * $outputPricePerMillion,
                    };

                    return [
                        'operation' => $row->operation,
                        'calls' => (int) $row->calls,
                        'prompt_tokens' => (int) $row->prompt_tokens,
                        'completion_tokens' => (int) $row->completion_tokens,
                        'characters' => (int) $row->characters,
                        'estimated_cost' => $cost,
                    ];
                });
            },
        );
    }

    public function updateProfile(): void
    {
        $this->profileSaved = false;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.Auth::id()],
        ]);

        Auth::user()->update($validated);

        $this->profileSaved = true;
    }

    public function updatePassword(): void
    {
        $this->passwordSaved = false;

        $validated = $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';

        $this->passwordSaved = true;
    }

    public function saveApiKey(): void
    {
        $this->apiKeySaved = false;

        $this->validate([
            'openai_api_key' => ['required', 'string', 'min:20'],
        ]);

        Auth::user()->update(['openai_api_key' => $this->openai_api_key]);

        $this->openai_api_key = '';
        $this->apiKeySaved = true;

        unset($this->hasOpenAiKey);
    }

    public function clearApiKey(): void
    {
        Auth::user()->update(['openai_api_key' => null]);
        $this->apiKeySaved = false;

        unset($this->hasOpenAiKey);
    }

    public function render(): View
    {
        return view('livewire.profile');
    }
}
