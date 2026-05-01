<?php

namespace App\Enums;

enum RoleName: string
{
    case ProviderAdmin = 'provider_admin';
    case Agent = 'agent';
    case CustomerAdmin = 'customer_admin';
    case CustomerUser = 'customer_user';
}
