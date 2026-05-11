<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class OpenAiKeySetup extends Component
{
    public string $openai_api_key = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            return;
        }

        if (! Auth::user()->openai_api_key && ! session()->has('openai_key_prompt_shown')) {
            session()->put('openai_key_prompt_shown', true);
            $this->dispatch('open-openai-key-setup');
        }
    }

    public function saveApiKey(): void
    {
        $this->validate([
            'openai_api_key' => ['required', 'string', 'min:20'],
        ]);

        Auth::user()->update(['openai_api_key' => $this->openai_api_key]);
        $this->openai_api_key = '';

        $this->dispatch('openai-key-saved');
    }

    public function render(): View
    {
        return view('livewire.open-ai-key-setup');
    }
}
