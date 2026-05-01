<?php

namespace App\Enums;

enum ApiClientStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
