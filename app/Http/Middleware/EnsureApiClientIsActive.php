<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiClientIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user();

        if (! $client instanceof ApiClient || ! $client->isActive()) {
            abort(401, 'Invalid or disabled API token.');
        }

        $client->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
