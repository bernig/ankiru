<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    public const array SUPPORTED_LOCALES = ['en', 'fr'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $selectedLocale = $request->session()->get('locale');

        if (is_string($selectedLocale) && in_array($selectedLocale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($selectedLocale);
        }

        return $next($request);
    }
}
