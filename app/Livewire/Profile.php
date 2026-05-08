<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
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
