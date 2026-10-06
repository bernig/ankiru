<?php

use App\Http\Middleware\SetUserOpenAiKey;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    config([
        'ai.providers.openai.key' => 'sk-platform',
        'ai.providers.openai.platform_key' => 'sk-platform',
    ]);
});

function runOpenAiKeyMiddleware(?User $user): void
{
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    (new SetUserOpenAiKey)->handle($request, fn () => new Response);
}

test('applies the personal key when the user has no platform credits', function (): void {
    runOpenAiKeyMiddleware(User::factory()->create(['openai_api_key' => 'sk-personal-key-1234567890', 'credits' => 0]));

    expect(config('ai.providers.openai.key'))->toBe('sk-personal-key-1234567890');
});

test('uses the platform key when the user has credits, even with a personal key', function (): void {
    config(['ai.providers.openai.key' => 'sk-stale-personal-key']);

    runOpenAiKeyMiddleware(User::factory()->create(['openai_api_key' => 'sk-personal-key-1234567890', 'credits' => 500]));

    expect(config('ai.providers.openai.key'))->toBe('sk-platform');
});

test('uses the platform key for guests', function (): void {
    config(['ai.providers.openai.key' => 'sk-stale-personal-key']);

    runOpenAiKeyMiddleware(null);

    expect(config('ai.providers.openai.key'))->toBe('sk-platform');
});
