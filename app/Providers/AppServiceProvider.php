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
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($user, string $token) {
            $origin = rtrim((string) config('app.frontend_url'), '/');
            $parts = parse_url($origin);
            if (!filter_var($origin, FILTER_VALIDATE_URL) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
                || (!app()->environment(['local', 'testing']) && ($parts['scheme'] ?? '') !== 'https')
                || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
                throw new \RuntimeException('Configure FRONTEND_URL with the trusted web origin.');
            }
            return $origin.'/reset-password?'.http_build_query(['token' => $token, 'email' => $user->getEmailForPasswordReset()]);
        });
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
