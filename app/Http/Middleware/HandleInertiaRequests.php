<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->public_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company' => $user->company ? [
                        'id' => $user->company->public_id,
                        'name' => $user->company->name,
                        'type' => $user->company->type->value,
                    ] : null,
                    'roles' => $user->roles->pluck('name')->values(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                    'is_provider' => $user->isProviderUser(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'invitation_url' => fn () => $request->session()->get('invitation_url'),
                'plain_text_token' => fn () => $request->session()->get('plain_text_token'),
            ],
        ];
    }
}
