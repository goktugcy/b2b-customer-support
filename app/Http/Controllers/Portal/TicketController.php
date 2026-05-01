<?php

namespace App\Http\Controllers\Portal;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Enums\TicketVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ChangeOwnTicketStatusRequest;
use App\Http\Requests\Portal\StoreTicketCommentRequest;
use App\Http\Requests\Portal\StoreTicketRequest;
use App\Http\Requests\Portal\StoreTicketWatcherRequest;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketAttachmentService;
use App\Services\Tickets\TicketCommentService;
use App\Services\Tickets\TicketCreationService;
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
            'departments' => $this->providerDepartments(),
            'providerUsers' => $this->providerUsers(),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketCreationService $tickets): RedirectResponse
    {
        $ticket = $tickets->create([
            'company_id' => $request->user()->company_id,
            'subject' => $request->validated('subject'),
            'description' => $request->validated('description'),
            'priority' => $request->validated('priority'),
            'target_department_ids' => $request->validated('target_department_ids', []),
            'target_user_ids' => $request->validated('target_user_ids', []),
            'attachments' => (array) $request->file('attachments', []),
        ], $request->user(), TicketSource::Portal, $request);

        return redirect()->route('portal.tickets.show', $ticket)->with('success', 'Ticket created.');
    }

    public function show(Request $request, Ticket $ticket, TicketWorkflowService $workflow): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'assignee',
            'targetDepartments',
            'targetUsers',
            'watcherUsers',
            'comments' => fn ($query) => $query->visibleTo($request->user())->with([
                'user',
                'apiClient',
                'attachments' => fn ($attachments) => $attachments->where('visibility', TicketVisibility::Public->value),
            ])->oldest(),
            'attachments' => fn ($query) => $query->whereNull('comment_id')->where('visibility', TicketVisibility::Public->value),
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
                'watchers' => $ticket->watcherUsers
                    ->filter(fn (User $user): bool => $user->company_id === $request->user()->company_id)
                    ->map(fn (User $user): array => [
                        'id' => $user->public_id,
                        'name' => $user->name,
                        'side' => $user->pivot->side,
                    ])->values(),
                'attachments' => $ticket->attachments->map(fn ($attachment): array => $this->attachmentPayload($attachment))->values(),
                'comments' => $ticket->comments->map(fn ($comment): array => [
                    'id' => $comment->public_id,
                    'body' => $comment->body,
                    'author' => $comment->user?->name ?? $comment->apiClient?->name,
                    'created_at' => $comment->created_at?->toISOString(),
                    'attachments' => $comment->attachments->map(fn ($attachment): array => $this->attachmentPayload($attachment))->values(),
                ]),
            ],
            'transitions' => $workflow->availableCustomerTransitions($ticket, $request->user()),
            'watcherUsers' => User::query()
                ->where('company_id', $request->user()->company_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['public_id', 'name'])
                ->map(fn (User $user): array => ['id' => $user->public_id, 'name' => $user->name]),
        ]);
    }

    public function comment(StoreTicketCommentRequest $request, Ticket $ticket, TicketCommentService $comments): RedirectResponse
    {
        $this->authorize('comment', $ticket);

        $comments->create(
            $ticket,
            $request->user(),
            $request->validated('body'),
            TicketVisibility::Public,
            $request,
            (array) $request->file('attachments', []),
        );

        return back()->with('success', 'Comment added.');
    }

    public function changeStatus(ChangeOwnTicketStatusRequest $request, Ticket $ticket, TicketWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('closeOwn', $ticket);

        $workflow->customerTransition($ticket, TicketStatus::from($request->validated('status')), $request->user(), $request);

        return back()->with('success', 'Status updated.');
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

        $request->validate(['file' => ['required', 'file', 'max:20480']]);

        $attachments->store($ticket, $request->file('file'), $request->user(), TicketVisibility::Public, $request);

        return back()->with('success', 'Attachment uploaded.');
    }

    private function attachmentPayload($attachment): array
    {
        return [
            'id' => $attachment->public_id,
            'filename' => $attachment->original_name,
            'size' => $attachment->size,
            'url' => route('attachments.download', $attachment),
        ];
    }

    private function providerDepartments(): array
    {
        return SupportDepartment::query()
            ->where('status', 'active')
            ->whereHas('company', fn ($query) => $query->provider())
            ->with('users:id,public_id')
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
                'id' => $user->public_id,
                'name' => $user->name,
                'department_ids' => $user->supportDepartments->pluck('public_id')->values(),
            ])
            ->all();
    }
}
