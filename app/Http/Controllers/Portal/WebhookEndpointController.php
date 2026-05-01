<?php

namespace App\Http\Controllers\Portal;

use App\Enums\WebhookEndpointStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreWebhookEndpointRequest;
use App\Models\WebhookEndpoint;
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
                    'url' => $endpoint->url,
                    'status' => $endpoint->status->value,
                    'events' => $endpoint->events,
                    'failure_count' => $endpoint->failure_count,
                    'deliveries_count' => $endpoint->deliveries_count,
                    'last_success_at' => $endpoint->last_success_at?->toISOString(),
                    'last_failure_at' => $endpoint->last_failure_at?->toISOString(),
                ]),
            'events' => self::EVENTS,
        ]);
    }

    public function store(StoreWebhookEndpointRequest $request, WebhookUrlValidator $validator): RedirectResponse
    {
        $validator->validate($request->validated('url'));

        WebhookEndpoint::create([
            'company_id' => $request->user()->company_id,
            'url' => $request->validated('url'),
            'secret' => Str::random(48),
            'events' => $request->validated('events'),
            'status' => WebhookEndpointStatus::Active,
        ]);

        return back()->with('success', 'Webhook endpoint created.');
    }

    public function destroy(Request $request, WebhookEndpoint $webhookEndpoint): RedirectResponse
    {
        $this->authorize('manage', $webhookEndpoint);

        $webhookEndpoint->forceFill(['status' => WebhookEndpointStatus::Disabled])->save();

        return back()->with('success', 'Webhook endpoint disabled.');
    }
}
