<?php

namespace App\Http\Controllers\Portal;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Invitations\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Portal/Users/Index', [
            'users' => $request->user()->company->users()
                ->with('roles')
                ->orderBy('name')
                ->get()
                ->map(fn ($user): array => [
                    'id' => $user->public_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status->value,
                    'roles' => $user->roles->pluck('name'),
                ]),
            'invitations' => Invitation::query()
                ->where('company_id', $request->user()->company_id)
                ->latest()
                ->get()
                ->map(fn (Invitation $invitation): array => [
                    'id' => $invitation->id,
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'role_name' => $invitation->role_name,
                    'accepted_at' => $invitation->accepted_at?->toISOString(),
                    'expires_at' => $invitation->expires_at?->toISOString(),
                ]),
            'roles' => [RoleName::CustomerAdmin->value, RoleName::CustomerUser->value],
        ]);
    }

    public function invite(StoreInvitationRequest $request, InvitationService $invitations): RedirectResponse
    {
        $company = $request->user()->company;
        $this->authorize('create', [Invitation::class, $company]);

        if (! in_array($request->validated('role_name'), [RoleName::CustomerAdmin->value, RoleName::CustomerUser->value], true)) {
            throw ValidationException::withMessages(['role_name' => 'Portal invitations can only use customer roles.']);
        }

        $result = $invitations->create(
            $company,
            $request->validated('email'),
            $request->validated('name'),
            $request->validated('role_name'),
            $request->user(),
            $request,
        );

        return back()
            ->with('success', 'Invitation sent.')
            ->with('invitation_url', route('invitations.accept', ['token' => $result['token']]));
    }
}
