<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ApiClientStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreApiClientRequest;
use App\Models\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public const DEFAULT_ABILITIES = [
        'tickets:create',
        'tickets:read',
        'tickets:comment',
        'attachments:create',
    ];

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('api_tokens.manage'), 403);

        return Inertia::render('Portal/ApiTokens/Index', [
            'clients' => ApiClient::query()
                ->where('company_id', $request->user()->company_id)
                ->with('tokens')
                ->latest()
                ->get()
                ->map(fn (ApiClient $client): array => [
                    'id' => $client->public_id,
                    'name' => $client->name,
                    'status' => $client->status->value,
                    'last_used_at' => $client->last_used_at?->toISOString(),
                    'expires_at' => $client->expires_at?->toISOString(),
                    'token_count' => $client->tokens->count(),
                ]),
            'abilities' => self::DEFAULT_ABILITIES,
        ]);
    }

    public function store(StoreApiClientRequest $request): RedirectResponse
    {
        $client = ApiClient::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->validated('name'),
            'status' => ApiClientStatus::Active,
            'created_by_user_id' => $request->user()->id,
            'expires_at' => $request->validated('expires_at'),
        ]);

        $abilities = $request->validated('abilities') ?: self::DEFAULT_ABILITIES;
        $token = $client->createToken($client->name, $abilities, $client->expires_at)->plainTextToken;

        return back()
            ->with('success', 'API token created.')
            ->with('plain_text_token', $token);
    }

    public function destroy(Request $request, ApiClient $apiClient): RedirectResponse
    {
        $this->authorize('manage', $apiClient);

        $apiClient->tokens()->delete();
        $apiClient->forceFill(['status' => ApiClientStatus::Disabled])->save();

        return back()->with('success', 'API client disabled.');
    }
}
