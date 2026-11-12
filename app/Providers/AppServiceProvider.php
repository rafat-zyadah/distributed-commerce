<?php

namespace App\Providers;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Policies\OrderPolicy;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Observers\ProductObserver;
use App\Support\Replication\MysqlReplicaPosition;
use App\Support\Replication\ReplicaPosition;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReplicaPosition::class, MysqlReplicaPosition::class);
    }

    public function boot(): void
    {
        // Policies live inside the modules, so they are registered here
        // (Laravel only auto-discovers policies for models in App\Models).
        Gate::policy(Order::class, OrderPolicy::class);

        // N+1 guard: outside production, reading a relation that was not eager loaded
        // throws an exception, so a new N+1 query fails in development and in tests.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Every product write invalidates the catalog cache and records the replica position.
        Product::observe(ProductObserver::class);

        // Login brute-force protection: 5 attempts per minute for each
        // (email, IP) pair. Used as throttle:login on the login route.
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
