<?php

namespace App\Enums;

enum CompanyType: string
{
    case Provider = 'provider';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Provider => 'Provider',
            self::Client => 'Client',
        };
    }
}
