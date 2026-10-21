<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class PrivilegedAccountServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (['privileged-invite' => 10, 'privileged-code' => 3, 'privileged-verify' => 10, 'privileged-complete' => 5] as $name => $maximum) {
            RateLimiter::for($name, fn ($request) => Limit::perMinute($maximum)
                ->by($name.':'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
        }
    }
}
