<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteHeader extends Component
{
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $authenticatedUser = auth()->user();

        return view('components.site-header', [
            'displayName' => $authenticatedUser?->name ?: ($authenticatedUser?->email ?: 'User'),
            'email' => $authenticatedUser?->email ?? null,
            'avatarUrl' => $authenticatedUser?->avatarUrl() ?? null,
        ]);
    }
}
