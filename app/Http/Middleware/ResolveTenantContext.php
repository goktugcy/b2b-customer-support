<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        if ($actor instanceof ApiClient) {
            $this->tenant->set($actor->company, true);

            return $next($request);
        }

        if (! $actor instanceof User) {
            $this->tenant->clear();

            return $next($request);
        }

        $isProviderAdminArea = $actor->isProviderUser() && $request->is('admin*');

        $this->tenant->set($actor->company, ! $isProviderAdminArea);

        return $next($request);
    }
}
