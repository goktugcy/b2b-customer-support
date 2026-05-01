<?php

namespace App\Policies;

use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view_any') || $user->can('tickets.view_company');
    }

    public function view(User|ApiClient $actor, Ticket $ticket): bool
    {
        if ($actor instanceof ApiClient) {
            return $actor->company_id === $ticket->company_id && $actor->tokenCan('tickets:read');
        }

        if ($actor->isProviderUser() && $actor->can('tickets.view_any')) {
            return true;
        }

        return $actor->can('tickets.view_company') && $actor->company_id === $ticket->company_id;
    }

    public function create(User|ApiClient $actor): bool
    {
        if ($actor instanceof ApiClient) {
            return $actor->tokenCan('tickets:create');
        }

        return $actor->can('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isProviderUser() && $user->can('tickets.update')) {
            return true;
        }

        return $user->can('tickets.update') && $user->company_id === $ticket->company_id;
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isProviderUser() && $user->can('tickets.assign');
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->isProviderUser() && $user->can('tickets.change_status');
    }

    public function closeOwn(User $user, Ticket $ticket): bool
    {
        return $user->isCustomerUser()
            && $user->company_id === $ticket->company_id
            && in_array($user->id, [$ticket->created_by_user_id, $ticket->requester_user_id], true)
            && $user->can('tickets.close_own');
    }

    public function addWatcher(User $user, Ticket $ticket): bool
    {
        if (! $user->can('tickets.add_watcher')) {
            return false;
        }

        return $user->isProviderUser() || $user->company_id === $ticket->company_id;
    }

    public function manageTargets(User $user, Ticket $ticket): bool
    {
        return $user->isProviderUser() && $user->can('tickets.manage_targets');
    }

    public function attach(User|ApiClient $actor, Ticket $ticket): bool
    {
        return $this->comment($actor, $ticket);
    }

    public function comment(User|ApiClient $actor, Ticket $ticket): bool
    {
        if ($actor instanceof ApiClient) {
            return $actor->company_id === $ticket->company_id && $actor->tokenCan('tickets:comment');
        }

        if ($actor->isProviderUser() && $actor->can('tickets.comment_public')) {
            return true;
        }

        return $actor->company_id === $ticket->company_id && $actor->can('tickets.comment_public');
    }
}
