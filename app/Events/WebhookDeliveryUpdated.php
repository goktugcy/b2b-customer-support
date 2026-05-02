<?php

namespace App\Events;

use App\Models\WebhookDelivery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookDeliveryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly WebhookDelivery $delivery) {}

    public function broadcastOn(): PrivateChannel
    {
        $this->delivery->loadMissing('endpoint.company');

        return new PrivateChannel('companies.'.$this->delivery->endpoint->company->public_id);
    }

    public function broadcastAs(): string
    {
        return 'webhook-delivery.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->delivery->public_id,
            'event_type' => $this->delivery->event_type,
            'status' => $this->delivery->status->value,
            'attempts' => $this->delivery->attempts,
            'response_status' => $this->delivery->response_status,
            'delivered_at' => $this->delivery->delivered_at?->toISOString(),
        ];
    }
}
