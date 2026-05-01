<?php

namespace App\Policies;

use App\Models\ApiClient;
use App\Models\Company;
use App\Models\User;

class ApiClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('api_tokens.manage');
    }

    public function create(User $user, Company $company): bool
    {
        return $user->can('api_tokens.manage')
            && ($user->isProviderUser() || $user->company_id === $company->id);
    }

    public function manage(User $user, ApiClient $client): bool
    {
        return $user->can('api_tokens.manage')
            && ($user->isProviderUser() || $user->company_id === $client->company_id);
    }
}
