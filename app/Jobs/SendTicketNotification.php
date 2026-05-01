<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTicketNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $event,
    ) {}

    public function handle(): void
    {
        Log::info('Ticket notification queued', [
            'ticket_id' => $this->ticket->public_id,
            'event' => $this->event,
        ]);
    }
}
