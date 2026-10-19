<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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
        RateLimiter::for('registration-id-read', fn ($request) => Limit::perMinute($request->filled('id_check_receipt') ? 30 : 6)->by(($request->filled('id_check_receipt') ? 'id-evaluate:' : 'id-read:').$request->ip()));
        RateLimiter::for('regular-registration', fn ($request) => Limit::perMinute(5)->by('regular-register:'.$request->ip()));
        foreach (['license-submit' => 10, 'license-recheck' => 10, 'vehicle-photo-read' => 6] as $name => $maximum) {
            RateLimiter::for($name, fn ($request) => Limit::perMinute($maximum)
                ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()))
                ->response(function ($request, array $headers) use ($name) {
                    $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));

                    return response()->json(['message' => 'Please wait '.$seconds.' seconds before trying this action again.', 'code' => 'rate_limited', 'action' => $name, 'retry_after' => $seconds], 429, $headers);
                }));
        }

    }
}
