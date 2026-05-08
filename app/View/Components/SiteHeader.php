<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteHeader extends Component
{
    public ?string $displayName;

    public ?string $avatarUrl;

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $authenticatedUser = auth()->user();
        $this->displayName = $authenticatedUser?->name ?: ($authenticatedUser?->email ?: 'User');
        $this->avatarUrl = $authenticatedUser?->avatarUrl() ?? null;

        return view('components.site-header', [
            'displayName' => $this->displayName,
            'avatarUrl' => $this->avatarUrl,
        ]);
    }
}
