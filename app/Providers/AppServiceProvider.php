<?php

namespace App\Providers;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Policies\OrderPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Policies live inside the modules, so they are registered here
        // (Laravel only auto-discovers policies for models in App\Models).
        Gate::policy(Order::class, OrderPolicy::class);

        // Login brute-force protection: 5 attempts per minute for each
        // (email, IP) pair. Used as throttle:login on the login route.
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
