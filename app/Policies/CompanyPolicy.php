<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProviderUser() && $user->can('companies.manage');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->isProviderUser() && $user->can('companies.manage')
            || $user->company_id === $company->id;
    }

    public function manage(User $user): bool
    {
        return $user->isProviderUser() && $user->can('companies.manage');
    }
}
