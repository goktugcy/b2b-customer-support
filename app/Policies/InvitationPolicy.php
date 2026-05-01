<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.invite');
    }

    public function create(User $user, Company $company): bool
    {
        if (! $user->can('users.invite')) {
            return false;
        }

        return $user->isProviderUser() || $user->company_id === $company->id;
    }

    public function revoke(User $user, Invitation $invitation): bool
    {
        return $user->can('users.invite')
            && ($user->isProviderUser() || $user->company_id === $invitation->company_id);
    }
}
