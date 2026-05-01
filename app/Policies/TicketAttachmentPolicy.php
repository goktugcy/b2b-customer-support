<?php

namespace App\Policies;

use App\Enums\TicketVisibility;
use App\Models\TicketAttachment;
use App\Models\User;

class TicketAttachmentPolicy
{
    public function view(User $user, TicketAttachment $ticketAttachment): bool
    {
        if ($user->isProviderUser() && $user->can('tickets.view_any')) {
            return true;
        }

        return $user->company_id === $ticketAttachment->company_id
            && $ticketAttachment->visibility === TicketVisibility::Public
            && $user->can('tickets.view_company');
    }
}
