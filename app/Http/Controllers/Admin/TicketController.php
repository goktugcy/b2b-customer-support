<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Enums\TicketVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignTicketRequest;
use App\Http\Requests\Admin\ChangeTicketStatusRequest;
use App\Http\Requests\Admin\StoreTicketCommentRequest;
use App\Http\Requests\Admin\StoreTicketRequest;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketAssignmentService;
use App\Services\Tickets\TicketCommentService;
use App\Services\Tickets\TicketCreationService;
use App\Services\Tickets\TicketWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = Ticket::query()
            ->visibleTo($request->user())
            ->with(['company', 'assignee'])
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->string('priority')->isNotEmpty(), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->string('company')->isNotEmpty(), fn ($query) => $query->whereHas('company', fn ($company) => $company->where('public_id', $request->string('company'))))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Ticket $ticket): array => $this->ticketSummary($ticket));

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'priority', 'company']),
            'companies' => Company::clients()->orderBy('name')->get(['public_id', 'name']),
            'statuses' => $this->statusOptions(),
            'priorities' => $this->priorityOptions(),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketCreationService $tickets, TicketAssignmentService $assignments): RedirectResponse
    {
        $company = Company::where('public_id', $request->validated('company_id'))->firstOrFail();
        $assignee = $request->validated('assigned_to_user_id')
            ? User::where('public_id', $request->validated('assigned_to_user_id'))->firstOrFail()
            : null;

        $ticket = $tickets->create([
            'company_id' => $company->id,
            'subject' => $request->validated('subject'),
            'description' => $request->validated('description'),
            'priority' => $request->validated('priority'),
        ], $request->user(), TicketSource::Admin, $request);

        if ($assignee) {
            $assignments->assign($ticket, $assignee, $request->user(), $request);
        }

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Ticket created.');
    }

    public function show(Request $request, Ticket $ticket, TicketWorkflowService $workflow): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'company',
            'assignee',
            'createdBy',
            'requester',
            'comments' => fn ($query) => $query->with(['user', 'apiClient'])->oldest(),
            'attachments',
            'events' => fn ($query) => $query->with(['actor', 'apiClient'])->orderBy('occurred_at'),
        ]);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $this->ticketDetail($ticket),
            'statuses' => $this->statusOptions(),
            'priorities' => $this->priorityOptions(),
            'transitions' => $workflow->availableTransitions($ticket, $request->user()),
            'agents' => User::query()
                ->whereHas('company', fn ($query) => $query->provider())
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['public_id', 'name']),
        ]);
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);

        $ticket->update($validated + ['last_agent_activity_at' => now()]);

        return back()->with('success', 'Ticket updated.');
    }

    public function changeStatus(ChangeTicketStatusRequest $request, Ticket $ticket, TicketWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('changeStatus', $ticket);

        $workflow->transition($ticket, TicketStatus::from($request->validated('status')), $request->user(), $request);

        return back()->with('success', 'Status updated.');
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket, TicketAssignmentService $assignments): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $assignee = $request->validated('assigned_to_user_id')
            ? User::where('public_id', $request->validated('assigned_to_user_id'))->firstOrFail()
            : null;

        $assignments->assign($ticket, $assignee, $request->user(), $request);

        return back()->with('success', $assignee ? 'Ticket assigned.' : 'Ticket unassigned.');
    }

    public function comment(StoreTicketCommentRequest $request, Ticket $ticket, TicketCommentService $comments): RedirectResponse
    {
        $this->authorize('comment', $ticket);

        $comments->create(
            $ticket,
            $request->user(),
            $request->validated('body'),
            TicketVisibility::from($request->validated('visibility')),
            $request,
        );

        return back()->with('success', 'Comment added.');
    }

    private function ticketSummary(Ticket $ticket): array
    {
        return [
            'id' => $ticket->public_id,
            'subject' => $ticket->subject,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'company' => $ticket->company?->name,
            'assignee' => $ticket->assignee?->name,
            'created_at' => $ticket->created_at?->toISOString(),
        ];
    }

    private function ticketDetail(Ticket $ticket): array
    {
        return [
            ...$this->ticketSummary($ticket),
            'description' => $ticket->description,
            'source' => $ticket->source->value,
            'requester' => $ticket->requester?->name,
            'created_by' => $ticket->createdBy?->name,
            'comments' => $ticket->comments->map(fn ($comment): array => [
                'id' => $comment->public_id,
                'body' => $comment->body,
                'visibility' => $comment->visibility->value,
                'author' => $comment->user?->name ?? $comment->apiClient?->name,
                'created_at' => $comment->created_at?->toISOString(),
            ]),
            'events' => $ticket->events->map(fn ($event): array => [
                'id' => $event->id,
                'type' => $event->event_type,
                'actor' => $event->actor?->name ?? $event->apiClient?->name,
                'old_values' => $event->old_values,
                'new_values' => $event->new_values,
                'occurred_at' => $event->occurred_at?->toISOString(),
            ]),
        ];
    }

    private function statusOptions(): array
    {
        return array_map(fn (TicketStatus $status): array => ['value' => $status->value, 'label' => $status->label()], TicketStatus::cases());
    }

    private function priorityOptions(): array
    {
        return array_map(fn (TicketPriority $priority): array => ['value' => $priority->value, 'label' => $priority->label()], TicketPriority::cases());
    }
}
