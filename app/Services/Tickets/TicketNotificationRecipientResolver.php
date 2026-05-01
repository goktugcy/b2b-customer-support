<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class TicketNotificationRecipientResolver
{
    public function __construct(private readonly TicketTargetService $targets) {}

    public function recipients(Ticket $ticket, string $event, ?User $subjectUser = null): Collection
    {
        $ticket->loadMissing([
            'assignee',
            'createdBy',
            'requester',
            'watcherUsers',
            'targetUsers',
            'targetDepartments.users',
        ]);

        $users = collect();

        if ($event === 'ticket.created') {
            $users = $users
                ->merge($this->targets->notificationRecipients($ticket))
                ->when($ticket->assignee, fn (Collection $collection) => $collection->push($ticket->assignee));
        } elseif ($event === 'ticket.watcher_added') {
            $users = $users
                ->when($subjectUser, fn (Collection $collection) => $collection->push($subjectUser))
                ->when($ticket->requester, fn (Collection $collection) => $collection->push($ticket->requester))
                ->when($ticket->createdBy, fn (Collection $collection) => $collection->push($ticket->createdBy));
        } else {
            $users = $users
                ->merge($ticket->watcherUsers)
                ->when($ticket->assignee, fn (Collection $collection) => $collection->push($ticket->assignee))
                ->when($ticket->requester, fn (Collection $collection) => $collection->push($ticket->requester));
        }

        return $users
            ->filter(fn (User $user): bool => $user->isActive())
            ->unique('id')
            ->values();
    }
}
