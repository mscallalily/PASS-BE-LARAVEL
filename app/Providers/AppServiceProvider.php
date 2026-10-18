<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\RateLimiter::for('registration-id-read', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute($request->filled('id_check_receipt') ? 30 : 6)->by(($request->filled('id_check_receipt') ? 'id-evaluate:' : 'id-read:').$request->ip()));
        \Illuminate\Support\Facades\RateLimiter::for('regular-registration', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('regular-register:'.$request->ip()));
    }
}
