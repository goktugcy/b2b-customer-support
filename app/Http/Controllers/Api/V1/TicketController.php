<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketSource;
use App\Enums\TicketVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketAttachmentRequest;
use App\Http\Requests\Api\V1\StoreTicketCommentRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Resources\Api\V1\TicketAttachmentResource;
use App\Http\Resources\Api\V1\TicketCommentResource;
use App\Http\Resources\Api\V1\TicketResource;
use App\Models\Ticket;
use App\Services\Api\ApiIdempotencyService;
use App\Services\Tickets\TicketAttachmentService;
use App\Services\Tickets\TicketCommentService;
use App\Services\Tickets\TicketCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->tokenCan('tickets:read'), 403);

        return TicketResource::collection(
            Ticket::query()
                ->visibleTo($request->user())
                ->with(['company', 'assignee'])
                ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate((int) min($request->integer('per_page', 25), 100))
        );
    }

    public function store(StoreTicketRequest $request, TicketCreationService $tickets, ApiIdempotencyService $idempotency): JsonResponse
    {
        $client = $request->user();

        if ($replay = $idempotency->findReplay($request, $client)) {
            return $replay;
        }

        $ticket = $tickets->create([
            'company_id' => $client->company_id,
            'subject' => $request->validated('subject'),
            'description' => $request->validated('description'),
            'priority' => $request->validated('priority') ?? null,
        ], $client, TicketSource::Api, $request);

        $response = (new TicketResource($ticket->load(['company', 'assignee'])))
            ->response()
            ->setStatusCode(201);

        $idempotency->store($request, $client, $response);

        return $response;
    }

    public function show(Request $request, Ticket $ticket): TicketResource
    {
        abort_unless(
            $request->user()->company_id === $ticket->company_id && $request->user()->tokenCan('tickets:read'),
            403
        );

        return new TicketResource($ticket->load([
            'company',
            'assignee',
            'comments' => fn ($query) => $query->visibleTo($request->user())->with(['user', 'apiClient'])->oldest(),
            'attachments' => fn ($query) => $query->where('visibility', TicketVisibility::Public->value),
        ]));
    }

    public function comment(StoreTicketCommentRequest $request, Ticket $ticket, TicketCommentService $comments): JsonResponse
    {
        abort_unless($request->user()->company_id === $ticket->company_id, 403);

        $comment = $comments->create($ticket, $request->user(), $request->validated('body'), TicketVisibility::Public, $request);

        return (new TicketCommentResource($comment->load(['user', 'apiClient'])))
            ->response()
            ->setStatusCode(201);
    }

    public function attachment(StoreTicketAttachmentRequest $request, Ticket $ticket, TicketAttachmentService $attachments): JsonResponse
    {
        abort_unless($request->user()->company_id === $ticket->company_id, 404);

        $attachment = $attachments->store($ticket, $request->file('file'), $request->user(), TicketVisibility::Public, $request);

        return (new TicketAttachmentResource($attachment))
            ->response()
            ->setStatusCode(201);
    }
}
