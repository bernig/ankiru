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

        if ($user && $user->openai_api_key) {
            config(['ai.providers.openai.key' => $user->openai_api_key]);
        }

        return $next($request);
    }
}
