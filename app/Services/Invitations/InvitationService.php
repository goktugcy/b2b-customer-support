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
        $token = Str::random(64);

        $invitation = Invitation::create([
            'company_id' => $company->id,
            'email' => Str::lower($email),
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
