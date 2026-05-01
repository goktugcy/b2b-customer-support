<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage') || $user->can('users.invite');
    }

    public function manage(User $user, ?User $target = null): bool
    {
        if (! $user->can('users.manage')) {
            return false;
        }

        return $user->isProviderUser() || $target === null || $user->company_id === $target->company_id;
    }

    public function invite(User $user): bool
    {
        return $user->can('users.invite');
    }
}
