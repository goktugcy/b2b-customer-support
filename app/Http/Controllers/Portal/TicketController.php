<?php

namespace App\Http\Controllers\Portal;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Enums\TicketVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreTicketCommentRequest;
use App\Http\Requests\Portal\StoreTicketRequest;
use App\Models\Ticket;
use App\Services\Tickets\TicketCommentService;
use App\Services\Tickets\TicketCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Ticket::class);

        return Inertia::render('Portal/Tickets/Index', [
            'tickets' => Ticket::query()
                ->visibleTo($request->user())
                ->with('assignee')
                ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Ticket $ticket): array => [
                    'id' => $ticket->public_id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status->value,
                    'priority' => $ticket->priority->value,
                    'assignee' => $ticket->assignee?->name,
                    'created_at' => $ticket->created_at?->toISOString(),
                ]),
            'filters' => $request->only(['status']),
            'statuses' => array_map(fn (TicketStatus $status): array => ['value' => $status->value, 'label' => $status->label()], TicketStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Ticket::class);

        return Inertia::render('Portal/Tickets/Create', [
            'priorities' => array_map(fn (TicketPriority $priority): array => ['value' => $priority->value, 'label' => $priority->label()], TicketPriority::cases()),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketCreationService $tickets): RedirectResponse
    {
        $ticket = $tickets->create([
            'company_id' => $request->user()->company_id,
            'subject' => $request->validated('subject'),
            'description' => $request->validated('description'),
            'priority' => $request->validated('priority'),
        ], $request->user(), TicketSource::Portal, $request);

        return redirect()->route('portal.tickets.show', $ticket)->with('success', 'Ticket created.');
    }

    public function show(Request $request, Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'assignee',
            'comments' => fn ($query) => $query->visibleTo($request->user())->with(['user', 'apiClient'])->oldest(),
            'attachments' => fn ($query) => $query->where('visibility', TicketVisibility::Public->value),
        ]);

        return Inertia::render('Portal/Tickets/Show', [
            'ticket' => [
                'id' => $ticket->public_id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status->value,
                'priority' => $ticket->priority->value,
                'assignee' => $ticket->assignee?->name,
                'created_at' => $ticket->created_at?->toISOString(),
                'comments' => $ticket->comments->map(fn ($comment): array => [
                    'id' => $comment->public_id,
                    'body' => $comment->body,
                    'author' => $comment->user?->name ?? $comment->apiClient?->name,
                    'created_at' => $comment->created_at?->toISOString(),
                ]),
            ],
        ]);
    }

    public function comment(StoreTicketCommentRequest $request, Ticket $ticket, TicketCommentService $comments): RedirectResponse
    {
        $this->authorize('comment', $ticket);

        $comments->create($ticket, $request->user(), $request->validated('body'), TicketVisibility::Public, $request);

        return back()->with('success', 'Comment added.');
    }
}
