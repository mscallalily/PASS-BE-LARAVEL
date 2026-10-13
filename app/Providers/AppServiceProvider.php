<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

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
        ResetPassword::createUrlUsing(
            function (object $notifiable, string $token): string {
                $query = http_build_query([
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]);

                return rtrim(
                    config('app.frontend_url', 'http://localhost:5173'),
                    '/'
                ) . '/reset-password?' . $query;
            }
        );
    }
}
