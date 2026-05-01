<?php

namespace App\Services\Tickets;

use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use App\Services\Webhooks\WebhookDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketEventRecorder
{
    public function record(
        Ticket $ticket,
        string $eventType,
        User|ApiClient|null $actor = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): TicketEvent {
        $event = TicketEvent::create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'actor_user_id' => $actor instanceof User ? $actor->id : null,
            'api_client_id' => $actor instanceof ApiClient ? $actor->id : null,
            'event_type' => $eventType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'occurred_at' => now(),
        ]);

        DB::afterCommit(fn () => app(WebhookDispatcher::class)->dispatch($event));

        return $event;
    }
}
