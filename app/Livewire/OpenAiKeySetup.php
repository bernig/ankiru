<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class OpenAiKeySetup extends Component
{
    public function mount(): void
    {
        if (! Auth::check()) {
            return;
        }

        if (! Auth::user()->canUseAi() && ! session()->has('openai_key_prompt_shown')) {
            session()->put('openai_key_prompt_shown', true);
            $this->dispatch('open-openai-key-setup');
        }
    }

    public function render(): View
    {
        return view('livewire.open-ai-key-setup');
    }
}
