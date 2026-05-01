<?php

namespace App\Http\Controllers\Portal;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Invitations\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
                    'revoked_at' => $invitation->revoked_at?->toISOString(),
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

    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        abort_unless($user->company_id === $request->user()->company_id, 404);
        $this->authorize('manage', $user);

        $validated = $request->validate([
            'role_name' => ['required', Rule::in([RoleName::CustomerAdmin->value, RoleName::CustomerUser->value])],
            'status' => ['required', Rule::in([UserStatus::Active->value, UserStatus::Disabled->value])],
        ]);

        if ($user->id === $request->user()->id && $validated['status'] === UserStatus::Disabled->value) {
            throw ValidationException::withMessages(['status' => 'You cannot disable your own account.']);
        }

        $before = [
            'status' => $user->status->value,
            'roles' => $user->roles->pluck('name')->values()->all(),
        ];

        $user->forceFill(['status' => UserStatus::from($validated['status'])])->save();
        $user->syncRoles([$validated['role_name']]);

        $audit->log('user.updated', $user, $request->user(), before: $before, after: [
            'status' => $user->status->value,
            'roles' => $user->roles()->pluck('name')->values()->all(),
        ], request: $request);

        return back()->with('success', 'User updated.');
    }

    public function revokeInvitation(Request $request, Invitation $invitation, InvitationService $invitations): RedirectResponse
    {
        abort_unless($invitation->company_id === $request->user()->company_id, 404);
        $this->authorize('revoke', $invitation);

        $invitations->revoke($invitation, $request->user(), $request);

        return back()->with('success', 'Invitation revoked.');
    }

    public function resendInvitation(Request $request, Invitation $invitation, InvitationService $invitations): RedirectResponse
    {
        abort_unless($invitation->company_id === $request->user()->company_id, 404);
        $this->authorize('create', [Invitation::class, $request->user()->company]);

        $result = $invitations->resend($invitation, $request->user(), $request);

        return back()
            ->with('success', 'Invitation resent.')
            ->with('invitation_url', route('invitations.accept', ['token' => $result['token']]));
    }
}
