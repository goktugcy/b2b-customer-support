<?php

namespace App\Enums;

enum TicketWatcherSide: string
{
    case Provider = 'provider';
    case Client = 'client';
}
