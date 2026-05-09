<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        /** @var array{name: string, email: string, password: string} $validatedData */
        $validatedData = $request->validated();

        $registeredUser = User::query()->create($validatedData);

        event(new Registered($registeredUser));

        Auth::login($registeredUser);

        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}
