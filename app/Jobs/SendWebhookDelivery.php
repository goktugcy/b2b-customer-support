<?php

namespace App\Jobs;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookEndpointStatus;
use App\Events\WebhookDeliveryUpdated;
use App\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendWebhookDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public WebhookDelivery $delivery) {}

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function handle(): void
    {
        $this->delivery->loadMissing('endpoint');

        if (! $this->delivery->endpoint?->isActive()) {
            return;
        }

        $payload = json_encode($this->delivery->payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $this->delivery->endpoint->secret);

        $response = Http::timeout(10)
            ->withHeaders([
                'User-Agent' => config('app.name', 'Support Platform').'/webhooks',
                'Content-Type' => 'application/json',
                'X-Support-Event' => $this->delivery->event_type,
                'X-Support-Delivery' => $this->delivery->public_id,
                'X-Support-Timestamp' => $timestamp,
                'X-Support-Signature' => 'sha256='.$signature,
            ])
            ->post($this->delivery->endpoint->url, $this->delivery->payload);

        $this->delivery->forceFill([
            'attempts' => $this->delivery->attempts + 1,
            'response_status' => $response->status(),
            'response_body_excerpt' => str($response->body())->limit(1000)->toString(),
            'status' => $response->successful() ? WebhookDeliveryStatus::Success : WebhookDeliveryStatus::Failed,
            'delivered_at' => $response->successful() ? now() : null,
            'next_attempt_at' => $response->successful() ? null : now()->addMinutes(15),
        ])->save();
        WebhookDeliveryUpdated::dispatch($this->delivery->refresh());

        $this->delivery->endpoint->forceFill([
            'failure_count' => $response->successful() ? 0 : $this->delivery->endpoint->failure_count + 1,
            'last_success_at' => $response->successful() ? now() : $this->delivery->endpoint->last_success_at,
            'last_failure_at' => $response->successful() ? $this->delivery->endpoint->last_failure_at : now(),
            'status' => $this->delivery->endpoint->failure_count + 1 >= 10
                ? WebhookEndpointStatus::Disabled
                : $this->delivery->endpoint->status,
        ])->save();

        if (! $response->successful()) {
            $this->release(900);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->delivery->forceFill([
            'attempts' => $this->delivery->attempts + 1,
            'status' => WebhookDeliveryStatus::Failed,
            'response_body_excerpt' => $exception?->getMessage(),
            'next_attempt_at' => now()->addMinutes(15),
        ])->save();
        WebhookDeliveryUpdated::dispatch($this->delivery->refresh());
    }
}
