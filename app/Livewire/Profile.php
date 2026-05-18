<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $learning_context = '';

    public ?string $accentColor = null;

    public bool $accentBold = true;

    public bool $accentUnicode = false;

    public bool $profileSaved = false;

    public bool $passwordSaved = false;

    public bool $learningContextSaved = false;

    public bool $accentStyleSaved = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->learning_context = $user->learning_context ?? '';
        $this->accentColor = $user->accent_color;
        $this->accentBold = (bool) ($user->accent_bold ?? true);
        $this->accentUnicode = (bool) ($user->accent_unicode ?? false);
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

    public function saveAccentStyle(?string $color, bool $bold, bool $unicode = false): void
    {
        if ($color !== null && ! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return;
        }

        $this->accentStyleSaved = false;
        $this->accentColor = $color;
        $this->accentBold = $bold;
        $this->accentUnicode = $unicode;

        Auth::user()->update([
            'accent_color' => $color,
            'accent_bold' => $bold,
            'accent_unicode' => $unicode,
        ]);

        $this->accentStyleSaved = true;
    }

    public function saveLearningContext(): void
    {
        $this->learningContextSaved = false;

        $validated = $this->validate([
            'learning_context' => ['nullable', 'string', 'max:2000'],
        ]);

        Auth::user()->update($validated);

        $this->learningContextSaved = true;
    }

    public function render(): View
    {
        return view('livewire.profile');
    }
}
