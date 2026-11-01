<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserOpenAiKey
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Always written (empty when unusable) so no key from a previous
        // request or the platform default is ever left in place.
        config(['ai.providers.openai.key' => $request->user()?->resolveOpenAiKey() ?? '']);

        return $next($request);
    }
}
