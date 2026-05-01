<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Models\WebhookEndpoint;

class WebhookEndpointPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('webhooks.manage');
    }

    public function create(User $user, Company $company): bool
    {
        return $user->can('webhooks.manage')
            && ($user->isProviderUser() || $user->company_id === $company->id);
    }

    public function manage(User $user, WebhookEndpoint $endpoint): bool
    {
        return $user->can('webhooks.manage')
            && ($user->isProviderUser() || $user->company_id === $endpoint->company_id);
    }
}
