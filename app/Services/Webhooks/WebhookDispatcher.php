<?php

namespace App\Services\Webhooks;

use App\Enums\WebhookEndpointStatus;
use App\Jobs\SendWebhookDelivery;
use App\Models\TicketEvent;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;

class WebhookDispatcher
{
    public function dispatch(TicketEvent $event): void
    {
        $subscriptionTypes = $this->subscriptionTypes($event->event_type);

        WebhookEndpoint::query()
            ->where('company_id', $event->company_id)
            ->where('status', WebhookEndpointStatus::Active->value)
            ->where(function ($query) use ($subscriptionTypes): void {
                foreach ($subscriptionTypes as $type) {
                    $query->orWhereJsonContains('events', $type);
                }

                $query->orWhereJsonContains('events', '*');
            })
            ->each(function (WebhookEndpoint $endpoint) use ($event): void {
                $payload = $this->payload($event);

                $delivery = WebhookDelivery::create([
                    'company_id' => $endpoint->company_id,
                    'webhook_endpoint_id' => $endpoint->id,
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'payload' => $payload,
                    'next_attempt_at' => now(),
                ]);

                SendWebhookDelivery::dispatch($delivery)->onQueue('webhooks');
            });
    }

    private function payload(TicketEvent $event): array
    {
        $event->loadMissing('ticket.company');

        return [
            'id' => 'evt_'.$event->id,
            'type' => $event->event_type,
            'occurred_at' => $event->occurred_at?->toISOString(),
            'data' => [
                'ticket' => [
                    'id' => $event->ticket->public_id,
                    'company_id' => $event->ticket->company->public_id,
                    'subject' => $event->ticket->subject,
                    'status' => $event->ticket->status->value,
                    'priority' => $event->ticket->priority->value,
                ],
                'old_values' => $event->old_values,
                'new_values' => $event->new_values,
                'metadata' => $event->metadata,
            ],
        ];
    }

    private function subscriptionTypes(string $eventType): array
    {
        return in_array($eventType, ['ticket.resolved', 'ticket.closed', 'ticket.reopened'], true)
            ? [$eventType, 'ticket.status_changed']
            : [$eventType];
    }
}
