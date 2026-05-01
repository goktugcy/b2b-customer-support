<?php

namespace App\Http\Controllers\Portal;

use App\Enums\WebhookEndpointStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreWebhookEndpointRequest;
use App\Jobs\SendWebhookDelivery;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\Audit\AuditLogger;
use App\Services\Webhooks\WebhookUrlValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebhookEndpointController extends Controller
{
    public const EVENTS = [
        'ticket.created',
        'ticket.status_changed',
        'ticket.assigned',
        'ticket.comment.created',
        'ticket.attachment.created',
    ];

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('webhooks.manage'), 403);

        return Inertia::render('Portal/Webhooks/Index', [
            'endpoints' => WebhookEndpoint::query()
                ->where('company_id', $request->user()->company_id)
                ->withCount('deliveries')
                ->latest()
                ->get()
                ->map(fn (WebhookEndpoint $endpoint): array => [
                    'id' => $endpoint->id,
                    'public_id' => $endpoint->public_id,
                    'url' => $endpoint->url,
                    'status' => $endpoint->status->value,
                    'events' => $endpoint->events,
                    'failure_count' => $endpoint->failure_count,
                    'deliveries_count' => $endpoint->deliveries_count,
                    'last_success_at' => $endpoint->last_success_at?->toISOString(),
                    'last_failure_at' => $endpoint->last_failure_at?->toISOString(),
                    'deliveries' => $endpoint->deliveries()
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(fn (WebhookDelivery $delivery): array => $this->deliveryPayload($delivery))
                        ->values(),
                ]),
            'events' => self::EVENTS,
        ]);
    }

    public function store(StoreWebhookEndpointRequest $request, WebhookUrlValidator $validator): RedirectResponse
    {
        $validator->validate($request->validated('url'));

        $secret = Str::random(48);

        WebhookEndpoint::create([
            'company_id' => $request->user()->company_id,
            'url' => $request->validated('url'),
            'secret' => $secret,
            'events' => $request->validated('events'),
            'status' => WebhookEndpointStatus::Active,
        ]);

        return back()
            ->with('success', 'Webhook endpoint created.')
            ->with('webhook_secret', $secret);
    }

    public function destroy(Request $request, WebhookEndpoint $webhookEndpoint): RedirectResponse
    {
        $this->authorize('manage', $webhookEndpoint);

        $webhookEndpoint->forceFill(['status' => WebhookEndpointStatus::Disabled])->save();

        return back()->with('success', 'Webhook endpoint disabled.');
    }

    public function rotateSecret(Request $request, WebhookEndpoint $webhookEndpoint, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manage', $webhookEndpoint);

        $secret = Str::random(48);
        $webhookEndpoint->forceFill(['secret' => $secret])->save();

        $audit->log('webhook.secret_rotated', $webhookEndpoint, $request->user(), request: $request);

        return back()
            ->with('success', 'Webhook secret rotated.')
            ->with('webhook_secret', $secret);
    }

    public function test(Request $request, WebhookEndpoint $webhookEndpoint, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manage', $webhookEndpoint);

        $delivery = WebhookDelivery::create([
            'company_id' => $webhookEndpoint->company_id,
            'webhook_endpoint_id' => $webhookEndpoint->id,
            'event_type' => 'webhook.test',
            'payload' => [
                'id' => 'evt_test_'.Str::lower(Str::random(8)),
                'type' => 'webhook.test',
                'occurred_at' => now()->toISOString(),
                'data' => ['message' => 'Test delivery from '.config('app.name')],
            ],
            'next_attempt_at' => now(),
        ]);

        SendWebhookDelivery::dispatch($delivery)->onQueue('webhooks');
        $audit->log('webhook.test_dispatched', $webhookEndpoint, $request->user(), request: $request);

        return back()->with('success', 'Webhook test delivery queued.');
    }

    public function retry(Request $request, WebhookEndpoint $webhookEndpoint, WebhookDelivery $delivery, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manage', $webhookEndpoint);
        abort_unless($delivery->webhook_endpoint_id === $webhookEndpoint->id, 404);

        $delivery->forceFill(['next_attempt_at' => now()])->save();
        SendWebhookDelivery::dispatch($delivery)->onQueue('webhooks');

        $audit->log('webhook.delivery_retried', $webhookEndpoint, $request->user(), after: [
            'delivery_id' => $delivery->public_id,
        ], request: $request);

        return back()->with('success', 'Webhook delivery queued for retry.');
    }

    private function deliveryPayload(WebhookDelivery $delivery): array
    {
        return [
            'id' => $delivery->public_id,
            'event_type' => $delivery->event_type,
            'status' => $delivery->status->value,
            'attempts' => $delivery->attempts,
            'response_status' => $delivery->response_status,
            'response_body_excerpt' => $delivery->response_body_excerpt,
            'next_attempt_at' => $delivery->next_attempt_at?->toISOString(),
            'delivered_at' => $delivery->delivered_at?->toISOString(),
            'created_at' => $delivery->created_at?->toISOString(),
        ];
    }
}
