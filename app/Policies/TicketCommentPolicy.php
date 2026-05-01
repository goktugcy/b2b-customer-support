<?php

namespace App\Policies;

use App\Enums\TicketVisibility;
use App\Models\TicketComment;
use App\Models\User;

class TicketCommentPolicy
{
    public function view(User $user, TicketComment $comment): bool
    {
        if ($user->isProviderUser() && $user->can('tickets.view_any')) {
            return true;
        }

        return $comment->visibility === TicketVisibility::Public
            && $user->company_id === $comment->company_id;
    }
}
