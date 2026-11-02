<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        RateLimiter::for('booking', function (Request $request) {
            $limits = [Limit::perMinute(10)->by('ip:'.$request->ip())];

            // The limiter runs before validation, so the email may be missing or not a string.
            $email = $request->input('customer_email');

            if (is_string($email) && trim($email) !== '') {
                $limits[] = Limit::perMinute(5)->by('email:'.strtolower(trim($email)));
            }

            return $limits;
        });
    }
}
