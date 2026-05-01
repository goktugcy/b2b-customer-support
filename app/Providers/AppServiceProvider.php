<?php

namespace App\Providers;

use App\Services\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $actor = $request->user();

            return Limit::perMinute(120)->by(
                $actor ? $actor::class.':'.$actor->getAuthIdentifier() : $request->ip()
            );
        });

        Vite::prefetch(concurrency: 3);
    }
}
