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
use App\Http\Requests\Admin\StoreTicketWatcherRequest;
use App\Http\Requests\Admin\UpdateTicketTargetsRequest;
use App\Models\Company;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketAssignmentService;
use App\Services\Tickets\TicketAttachmentService;
use App\Services\Tickets\TicketCommentService;
use App\Services\Tickets\TicketCreationService;
use App\Services\Tickets\TicketTargetService;
use App\Services\Tickets\TicketWatcherService;
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
            'departments' => $this->providerDepartments(),
            'providerUsers' => $this->providerUsers(),
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
            'target_department_ids' => $request->validated('target_department_ids', []),
            'target_user_ids' => $request->validated('target_user_ids', []),
            'attachments' => (array) $request->file('attachments', []),
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
            'comments' => fn ($query) => $query->with(['user', 'apiClient', 'attachments'])->oldest(),
            'attachments' => fn ($query) => $query->whereNull('comment_id'),
            'targetDepartments',
            'targetUsers',
            'watcherUsers',
            'events' => fn ($query) => $query->with(['actor', 'apiClient'])->orderBy('occurred_at'),
        ]);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $this->ticketDetail($ticket),
            'statuses' => $this->statusOptions(),
            'priorities' => $this->priorityOptions(),
            'transitions' => $workflow->availableTransitions($ticket, $request->user()),
            'agents' => $this->providerUsers(),
            'departments' => $this->providerDepartments(),
            'providerUsers' => $this->providerUsers(),
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
            (array) $request->file('attachments', []),
        );

        return back()->with('success', 'Comment added.');
    }

    public function updateTargets(UpdateTicketTargetsRequest $request, Ticket $ticket, TicketTargetService $targets): RedirectResponse
    {
        $this->authorize('manageTargets', $ticket);

        $targets->sync(
            $ticket,
            $request->validated('target_department_ids', []),
            $request->validated('target_user_ids', []),
            $request->user(),
            $request,
        );

        return back()->with('success', 'Targets updated.');
    }

    public function addWatcher(StoreTicketWatcherRequest $request, Ticket $ticket, TicketWatcherService $watchers): RedirectResponse
    {
        $this->authorize('addWatcher', $ticket);

        $watcher = User::where('public_id', $request->validated('user_id'))->firstOrFail();

        $watchers->add($ticket, $watcher, $request->user());

        return back()->with('success', 'Watcher added.');
    }

    public function removeWatcher(Request $request, Ticket $ticket, User $user, TicketWatcherService $watchers): RedirectResponse
    {
        $this->authorize('addWatcher', $ticket);

        $watchers->remove($ticket, $user, $request->user());

        return back()->with('success', 'Watcher removed.');
    }

    public function attachment(Request $request, Ticket $ticket, TicketAttachmentService $attachments): RedirectResponse
    {
        $this->authorize('attach', $ticket);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'visibility' => ['required', 'in:public,internal'],
        ]);

        $attachments->store($ticket, $request->file('file'), $request->user(), TicketVisibility::from($validated['visibility']), $request);

        return back()->with('success', 'Attachment uploaded.');
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
            'assignee_id' => $ticket->assignee?->public_id,
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
            'targets' => [
                'departments' => $ticket->targetDepartments->map(fn (SupportDepartment $department): array => [
                    'id' => $department->public_id,
                    'name' => $department->name,
                ])->values(),
                'users' => $ticket->targetUsers->map(fn (User $user): array => [
                    'id' => $user->public_id,
                    'name' => $user->name,
                ])->values(),
            ],
            'watchers' => $ticket->watcherUsers->map(fn (User $user): array => [
                'id' => $user->public_id,
                'name' => $user->name,
                'side' => $user->pivot->side,
            ])->values(),
            'attachments' => $ticket->attachments->map(fn ($attachment): array => $this->attachmentPayload($attachment))->values(),
            'comments' => $ticket->comments->map(fn ($comment): array => [
                'id' => $comment->public_id,
                'body' => $comment->body,
                'visibility' => $comment->visibility->value,
                'author' => $comment->user?->name ?? $comment->apiClient?->name,
                'created_at' => $comment->created_at?->toISOString(),
                'attachments' => $comment->attachments->map(fn ($attachment): array => $this->attachmentPayload($attachment))->values(),
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

    private function attachmentPayload($attachment): array
    {
        return [
            'id' => $attachment->public_id,
            'filename' => $attachment->original_name,
            'size' => $attachment->size,
            'visibility' => $attachment->visibility->value,
            'url' => route('attachments.download', $attachment),
        ];
    }

    private function providerDepartments(): array
    {
        return SupportDepartment::query()
            ->where('status', 'active')
            ->whereHas('company', fn ($query) => $query->provider())
            ->with('users')
            ->orderBy('name')
            ->get()
            ->map(fn (SupportDepartment $department): array => [
                'id' => $department->public_id,
                'name' => $department->name,
                'user_ids' => $department->users->pluck('public_id')->values(),
            ])
            ->all();
    }

    private function providerUsers(): array
    {
        return User::query()
            ->whereHas('company', fn ($query) => $query->provider())
            ->where('status', 'active')
            ->with('supportDepartments:id,public_id')
            ->orderBy('name')
            ->get(['id', 'public_id', 'name'])
            ->map(fn (User $user): array => [
                'public_id' => $user->public_id,
                'id' => $user->public_id,
                'name' => $user->name,
                'department_ids' => $user->supportDepartments->pluck('public_id')->values(),
            ])
            ->all();
    }
}
