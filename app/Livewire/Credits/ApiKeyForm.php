<?php

namespace App\Livewire\Credits;

use App\Models\ApiUsageLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ApiKeyForm extends Component
{
    public string $openai_api_key = '';

    public bool $apiKeySaved = false;

    #[Computed]
    public function hasOpenAiKey(): bool
    {
        return (bool) Auth::user()->openai_api_key;
    }

    /**
     * Aggregated usage stats per operation with estimated cost.
     * Cached for 1 hour; invalidated on every new ApiUsageLog.
     *
     * @return Collection<int, array{operation: string, calls: int, prompt_tokens: int, completion_tokens: int, characters: int, estimated_cost: float}>
     */
    #[Computed]
    public function usageStats(): Collection
    {
        $inputPricePerMillion = (float) config('services.openai.gpt_5_4_input_price_per_million', 3.00);
        $outputPricePerMillion = (float) config('services.openai.gpt_5_4_output_price_per_million', 15.00);
        $ttsPricePerMillion = (float) config('services.openai.tts_price_per_million_chars', 30.00);

        $cacheKey = 'user_usage_stats:'.Auth::id();

        $rows = Cache::get($cacheKey);

        if (! is_array($rows)) {
            $rows = ApiUsageLog::query()
                ->where('user_id', Auth::id())
                ->selectRaw('operation, COUNT(*) as calls, COALESCE(SUM(prompt_tokens), 0) as prompt_tokens, COALESCE(SUM(completion_tokens), 0) as completion_tokens, COALESCE(SUM(characters), 0) as characters')
                ->groupBy('operation')
                ->orderBy('operation')
                ->get()
                ->map(function (ApiUsageLog $row) use ($inputPricePerMillion, $outputPricePerMillion, $ttsPricePerMillion): array {
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
                })
                ->all();

            Cache::put($cacheKey, $rows, 3600);
        }

        return collect($rows);
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
        return view('livewire.credits.api-key-form');
    }
}
