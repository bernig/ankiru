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
        $user = $request->user();

        config(['ai.providers.openai.key' => $user && $user->openai_api_key && ! $user->usesPlatformCredits()
            ? $user->openai_api_key
            : config('ai.providers.openai.platform_key'),
        ]);

        return $next($request);
    }
}
