<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiters();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
    }

    /**
     * Register named rate limiters for AI translation and TTS generation actions.
     *
     * Both limiters are keyed by session ID so each browser session has its own
     * independent quota, regardless of IP address or authentication state.
     *
     * Limits per minute:
     *   - ai-translation : 30 requests (translate + stress-correct actions combined)
     *   - tts-generation : 10 requests  (generate audio action)
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('ai-translation', function () {
            return Limit::perMinute(30)->by(session()->getId());
        });

        RateLimiter::for('tts-generation', function () {
            return Limit::perMinute(10)->by(session()->getId());
        });
    }
}
