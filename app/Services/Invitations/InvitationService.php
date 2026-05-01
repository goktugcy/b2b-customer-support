<?php

namespace App\Services\Invitations;

use App\Enums\UserStatus;
use App\Jobs\SendInvitationEmail;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return array{invitation: Invitation, token: string}
     */
    public function create(Company $company, string $email, string $name, string $roleName, User $invitedBy, ?Request $request = null): array
    {
        $email = Str::lower($email);

        if (Invitation::query()
            ->where('company_id', $company->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists()) {
            throw ValidationException::withMessages(['email' => 'This email already has a pending invitation.']);
        }

        $token = Str::random(64);

        $invitation = Invitation::create([
            'company_id' => $company->id,
            'email' => $email,
            'name' => $name,
            'role_name' => $roleName,
            'token_hash' => hash('sha256', $token),
            'invited_by_user_id' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
            'metadata' => [],
        ]);

        $this->audit->log('invitation.created', $invitation, $invitedBy, after: [
            'email' => $invitation->email,
            'role_name' => $roleName,
        ], request: $request);

        SendInvitationEmail::dispatch($invitation, $token)->afterCommit()->onQueue('notifications');

        return compact('invitation', 'token');
    }

    public function revoke(Invitation $invitation, User $actor, ?Request $request = null): void
    {
        if ($invitation->accepted_at !== null) {
            throw ValidationException::withMessages(['invitation' => 'Accepted invitations cannot be revoked.']);
        }

        $before = $invitation->only(['revoked_at']);

        $invitation->forceFill(['revoked_at' => now()])->save();

        $this->audit->log('invitation.revoked', $invitation, $actor, before: $before, after: [
            'revoked_at' => $invitation->revoked_at?->toISOString(),
        ], request: $request);
    }

    /**
     * @return array{invitation: Invitation, token: string}
     */
    public function resend(Invitation $invitation, User $actor, ?Request $request = null): array
    {
        if ($invitation->accepted_at !== null) {
            throw ValidationException::withMessages(['invitation' => 'Accepted invitations cannot be resent.']);
        }

        $token = Str::random(64);
        $before = $invitation->only(['expires_at', 'revoked_at']);

        $invitation->forceFill([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
            'revoked_at' => null,
        ])->save();

        $this->audit->log('invitation.resent', $invitation, $actor, before: $before, after: [
            'expires_at' => $invitation->expires_at?->toISOString(),
            'revoked_at' => null,
        ], request: $request);

        SendInvitationEmail::dispatch($invitation, $token)->afterCommit()->onQueue('notifications');

        return ['invitation' => $invitation, 'token' => $token];
    }

    public function findAcceptableByToken(string $token): Invitation
    {
        $invitation = Invitation::query()
            ->with('company')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $invitation?->isAcceptable()) {
            throw ValidationException::withMessages(['token' => 'This invitation is invalid, expired, or already used.']);
        }

        return $invitation;
    }

    /**
     * @param  array{name:string,password:string}  $data
     */
    public function accept(string $token, array $data, ?Request $request = null): User
    {
        return DB::transaction(function () use ($token, $data, $request): User {
            $invitation = $this->findAcceptableByToken($token);

            $existing = User::query()->where('email', $invitation->email)->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'email' => 'A user with this email already exists. Ask an administrator to invite another email address.',
                ]);
            }

            $user = User::create([
                'company_id' => $invitation->company_id,
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => $data['password'],
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
            ]);

            $user->assignRole($invitation->role_name);

            $invitation->forceFill([
                'accepted_by_user_id' => $user->id,
                'accepted_at' => now(),
            ])->save();

            $this->audit->log('invitation.accepted', $invitation, $user, after: [
                'accepted_by_user_id' => $user->public_id,
            ], request: $request);

            Auth::login($user);

            return $user;
        });
    }
}
