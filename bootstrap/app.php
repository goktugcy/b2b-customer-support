<?php

use App\Http\Middleware\EnsureApiClientIsActive;
use App\Http\Middleware\EnsureClientUser;
use App\Http\Middleware\EnsureProviderUser;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'active.user' => EnsureUserIsActive::class,
            'active.api_client' => EnsureApiClientIsActive::class,
            'tenant' => ResolveTenantContext::class,
            'provider.user' => EnsureProviderUser::class,
            'client.user' => EnsureClientUser::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'inbound-email/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
