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
use App\Models\TicketSavedView;
use App\Models\TicketTag;
use App\Models\User;
use App\Services\Search\PostgresFullTextSearch;
use App\Services\Tickets\IssueTrackingService;
use App\Services\Tickets\MentionParserService;
use App\Services\Tickets\TicketAttachmentService;
use App\Services\Tickets\TicketCommentService;
use App\Services\Tickets\TicketCreationService;
use App\Services\Tickets\TicketSavedViewService;
use App\Services\Tickets\TicketWatcherService;
use App\Services\Tickets\TicketWorkflowService;
use App\Support\AttachmentValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request, IssueTrackingService $issueTracking, TicketSavedViewService $savedViews, PostgresFullTextSearch $textSearch): Response
    {
        $this->authorize('viewAny', Ticket::class);

        return Inertia::render('Portal/Tickets/Index', [
            'tickets' => Ticket::query()
                ->visibleTo($request->user())
                ->with(['assignee', 'supportProject', 'tracker', 'category', 'tags'])
                ->when($request->string('search')->isNotEmpty(), function ($query) use ($request, $textSearch): void {
                    $rawSearch = $request->string('search')->toString();
                    $ticketNumber = ltrim($rawSearch, '#');

                    $query->where(function ($inner) use ($rawSearch, $ticketNumber, $textSearch): void {
                        $textSearch->apply($inner, ['tickets.subject', 'tickets.description'], $rawSearch);
                        $inner->orWhereHas('tags', fn ($tag) => $textSearch->apply($tag, ['ticket_tags.name', 'ticket_tags.slug'], $rawSearch));

                        if (ctype_digit($ticketNumber)) {
                            $inner->orWhere('ticket_number', (int) $ticketNumber);
                        }
                    });
                })
                ->when($request->string('queue')->isNotEmpty(), fn ($query) => $this->applyQueueFilter($query, $request->string('queue')->toString(), $request))
                ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
                ->when($request->string('project')->isNotEmpty(), fn ($query) => $query->whereHas('supportProject', fn ($project) => $project->where('public_id', $request->string('project'))))
                ->when($request->string('tracker')->isNotEmpty(), fn ($query) => $query->whereHas('tracker', fn ($tracker) => $tracker->where('public_id', $request->string('tracker'))))
                ->when($request->string('tag')->isNotEmpty(), fn ($query) => $query->whereHas('tags', fn ($tag) => $tag->where('public_id', $request->string('tag'))))
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Ticket $ticket): array => [
                    'id' => $ticket->public_id,
                    'ticket_number' => $ticket->ticket_number,
                    'display_id' => $ticket->displayId(),
                    'route_params' => $ticket->portalRouteParameters(),
                    'url' => route('portal.tickets.show', $ticket->portalRouteParameters()),
                    'subject' => $ticket->subject,
                    'status' => $ticket->status->value,
                    'priority' => $ticket->priority->value,
                    'project' => $ticket->supportProject?->name,
                    'tracker' => $ticket->tracker?->name,
                    'tags' => $ticket->tags->pluck('name')->values(),
                    'assignee' => $ticket->assignee?->name,
                    'created_at' => $ticket->created_at?->toISOString(),
                    'sla' => $ticket->sla_first_response_breached_at || $ticket->sla_resolution_breached_at ? 'breached' : null,
                ]),
            'filters' => $request->only(['search', 'queue', 'status', 'project', 'tracker', 'tag']),
            'statuses' => array_map(fn (TicketStatus $status): array => ['value' => $status->value, 'label' => $status->label()], TicketStatus::cases()),
            'projects' => $issueTracking->projectOptions($request->user()->company),
            'trackers' => $issueTracking->trackerOptions(),
            'tags' => $issueTracking->tagOptions(),
            'savedViews' => $savedViews->visibleTo($request->user(), TicketSavedView::SECTION_PORTAL)
                ->map(fn (TicketSavedView $view): array => $savedViews->payload($view))
                ->values(),
        ]);
    }

    public function create(Request $request, IssueTrackingService $issueTracking): Response
    {
        $this->authorize('create', Ticket::class);

        return Inertia::render('Portal/Tickets/Create', [
            'priorities' => array_map(fn (TicketPriority $priority): array => ['value' => $priority->value, 'label' => $priority->label()], TicketPriority::cases()),
            'departments' => $this->providerDepartments(),
            'providerUsers' => $this->providerUsers(),
            'projects' => $issueTracking->projectOptions($request->user()->company),
            'trackers' => $issueTracking->trackerOptions(),
            'categories' => $issueTracking->categoryOptions($request->user()->company),
            'tags' => $issueTracking->tagOptions(),
            'customFields' => $issueTracking->customFieldOptions(),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketCreationService $tickets): RedirectResponse
    {
        $ticket = $tickets->create([
            'company_id' => $request->user()->company_id,
            'project_id' => $request->validated('project_id'),
            'tracker_id' => $request->validated('tracker_id'),
            'category_id' => $request->validated('category_id'),
            'subject' => $request->validated('subject'),
            'description' => $request->validated('description'),
            'priority' => $request->validated('priority'),
            'tag_names' => $request->validated('tag_names', []),
            'custom_fields' => $request->validated('custom_fields', []),
            'target_department_ids' => $request->validated('target_department_ids', []),
            'target_user_ids' => $request->validated('target_user_ids', []),
            'attachments' => (array) $request->file('attachments', []),
        ], $request->user(), TicketSource::Portal, $request);

        return redirect()->route('portal.tickets.show', $ticket->portalRouteParameters())->with('success', 'Ticket created.');
    }

    public function show(Request $request, string $ticket, TicketWorkflowService $workflow, MentionParserService $mentions): Response|RedirectResponse
    {
        $ticket = $this->resolveTicketForUser($request, $ticket);
        $this->authorize('view', $ticket);

        if (! ctype_digit((string) $request->route('ticket'))) {
            return redirect()->route('portal.tickets.show', $ticket->portalRouteParameters());
        }

        $ticket->load([
            'assignee',
            'supportProject',
            'tracker.customFields.options',
            'category',
            'tags',
            'customFieldValues.customField.options',
            'targetDepartments',
            'targetUsers',
            'watcherUsers',
            'comments' => fn ($query) => $query->visibleTo($request->user())->with([
                'user',
                'apiClient',
                'attachments' => fn ($attachments) => $attachments->where('visibility', TicketVisibility::Public->value),
            ])->oldest(),
            'attachments' => fn ($query) => $query->whereNull('comment_id')->where('visibility', TicketVisibility::Public->value),
            'csatSurveys',
        ]);

        return Inertia::render('Portal/Tickets/Show', [
            'ticket' => [
                'id' => $ticket->public_id,
                'ticket_number' => $ticket->ticket_number,
                'display_id' => $ticket->displayId(),
                'route_params' => $ticket->portalRouteParameters(),
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status->value,
                'priority' => $ticket->priority->value,
                'project' => $ticket->supportProject?->name,
                'tracker' => $ticket->tracker?->name,
                'category' => $ticket->category?->name,
                'tags' => $ticket->tags->map(fn (TicketTag $tag): array => [
                    'id' => $tag->public_id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ])->values(),
                'custom_fields' => $ticket->customFieldValues
                    ->map(fn ($value): array => [
                        'id' => $value->customField?->public_id,
                        'name' => $value->customField?->name,
                        'type' => $value->customField?->type,
                        'value' => $value->value['value'] ?? null,
                    ])
                    ->filter(fn (array $field): bool => $field['id'] !== null)
                    ->values(),
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
                'csat' => [
                    'latest_rating' => $ticket->csatSurveys->sortByDesc('responded_at')->first()?->rating,
                    'responses_count' => $ticket->csatSurveys->whereNotNull('responded_at')->count(),
                ],
            ],
            'transitions' => $workflow->availableCustomerTransitions($ticket, $request->user()),
            'watcherUsers' => User::query()
                ->where('company_id', $request->user()->company_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['public_id', 'name'])
                ->map(fn (User $user): array => ['id' => $user->public_id, 'name' => $user->name]),
            'mentionableUsers' => $mentions->mentionableUsers($ticket, TicketVisibility::Public),
        ]);
    }

    private function resolveTicketForUser(Request $request, string $identifier): Ticket
    {
        $normalized = ltrim($identifier, '#');

        return Ticket::query()
            ->where('company_id', $request->user()->company_id)
            ->where(function ($query) use ($identifier, $normalized): void {
                if (ctype_digit($normalized)) {
                    $query->where('ticket_number', (int) $normalized);
                }

                $query->orWhere('public_id', $identifier);
            })
            ->firstOrFail();
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
            $request->validated('mentioned_user_ids', []),
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

        $request->validate(['file' => AttachmentValidationRules::upload()]);

        $attachments->store($ticket, $request->file('file'), $request->user(), TicketVisibility::Public, $request);

        return back()->with('success', 'Attachment uploaded.');
    }

    private function attachmentPayload($attachment): array
    {
        return [
            'id' => $attachment->public_id,
            'filename' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
            'url' => route('attachments.download', $attachment),
        ];
    }

    private function applyQueueFilter($query, string $queue, Request $request): void
    {
        match ($queue) {
            'mine' => $query->where(fn ($inner) => $inner
                ->where('created_by_user_id', $request->user()->id)
                ->orWhere('requester_user_id', $request->user()->id)),
            'unassigned' => $query->whereNull('assigned_to_user_id'),
            'overdue' => $query->where(fn ($inner) => $inner
                ->whereNotNull('sla_first_response_breached_at')
                ->orWhereNotNull('sla_resolution_breached_at')),
            'due_soon' => $query
                ->whereNull('sla_first_response_breached_at')
                ->whereNull('sla_resolution_breached_at')
                ->where(fn ($inner) => $inner
                    ->where(fn ($first) => $first
                        ->whereNull('first_responded_at')
                        ->whereBetween('first_response_due_at', [now(), now()->addHours(4)]))
                    ->orWhereBetween('due_at', [now(), now()->addHours(4)])),
            default => null,
        };
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
