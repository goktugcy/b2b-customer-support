<?php

namespace App\Events;

use App\Models\TicketEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketLiveUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly TicketEvent $event) {}

    public function broadcastOn(): array
    {
        $this->event->loadMissing('ticket.company');

        return [
            new PrivateChannel('tickets.'.$this->event->ticket->public_id),
            new PrivateChannel('companies.'.$this->event->ticket->company->public_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.updated';
    }

    public function broadcastWith(): array
    {
        $this->event->loadMissing('ticket.company');

        return [
            'event' => $this->event->event_type,
            'ticket_id' => $this->event->ticket->public_id,
            'ticket_number' => $this->event->ticket->ticket_number,
            'display_id' => $this->event->ticket->displayId(),
            'subject' => $this->event->ticket->subject,
            'status' => $this->event->ticket->status->value,
            'priority' => $this->event->ticket->priority->value,
            'occurred_at' => $this->event->occurred_at?->toISOString(),
        ];
    }
}
