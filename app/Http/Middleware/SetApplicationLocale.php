<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $selectedLocale = $request->session()->get('locale');

        if (is_string($selectedLocale) && in_array($selectedLocale, Config::array('app.supported_locales'), true)) {
            App::setLocale($selectedLocale);
        }

        return $next($request);
    }
}
