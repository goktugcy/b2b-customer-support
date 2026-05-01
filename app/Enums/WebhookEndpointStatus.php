<?php

namespace App\Enums;

enum WebhookEndpointStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
