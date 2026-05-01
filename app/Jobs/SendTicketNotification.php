<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\TicketWatcher;
use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use App\Services\Tickets\TicketNotificationRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendTicketNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $event,
        public ?int $subjectUserId = null,
    ) {}

    public function handle(TicketNotificationRecipientResolver $recipients): void
    {
        $ticket = $this->ticket->fresh();

        if (! $ticket) {
            return;
        }

        $subjectUser = $this->subjectUserId ? User::find($this->subjectUserId) : null;
        $users = $recipients->recipients($ticket, $this->event, $subjectUser);

        Notification::send($users, new TicketUpdatedNotification($ticket, $this->event, $subjectUser));

        if ($this->event === 'ticket.watcher_added' && $subjectUser) {
            TicketWatcher::query()
                ->where('ticket_id', $ticket->id)
                ->where('user_id', $subjectUser->id)
                ->whereNull('notified_at')
                ->update(['notified_at' => now()]);
        }
    }
}
