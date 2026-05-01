<?php

namespace App\Policies;

use App\Models\SupportDepartment;
use App\Models\User;

class SupportDepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProviderUser() && $user->can('departments.manage');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SupportDepartment $supportDepartment): bool
    {
        return $this->viewAny($user) && $supportDepartment->company_id === $user->company_id;
    }

    public function delete(User $user, SupportDepartment $supportDepartment): bool
    {
        return $this->update($user, $supportDepartment);
    }
}
