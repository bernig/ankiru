<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class DebugAiErrorSwitch extends Component
{
    public bool $forceAiError = false;

    public function mount(): void
    {
        $this->forceAiError = (bool) session('debug_force_ai_error', false);
    }

    public function updatedForceAiError(): void
    {
        session()->put('debug_force_ai_error', $this->forceAiError);
    }

    public function render(): View
    {
        return view('livewire.debug-ai-error-switch');
    }
}
