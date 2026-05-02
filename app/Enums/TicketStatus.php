<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingOnCustomer = 'waiting_on_customer';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Merged = 'merged';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::WaitingOnCustomer => 'Waiting on customer',
            self::Pending => 'Pending',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Merged => 'Merged',
        };
    }
}
