<?php

namespace App\Enums;

enum TicketSource: string
{
    case Portal = 'portal';
    case Api = 'api';
    case Admin = 'admin';
    case Email = 'email';
}
