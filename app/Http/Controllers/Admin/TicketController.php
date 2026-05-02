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
use App\Models\CannedResponse;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use App\Models\TicketSavedView;
use App\Models\TicketTag;
use App\Models\User;
use App\Services\CannedResponses\CannedResponseService;
use App\Services\Content\HtmlSanitizer;
use App\Services\Search\PostgresFullTextSearch;
use App\Services\Tickets\IssueTrackingService;
use App\Services\Tickets\MentionParserService;
use App\Services\Tickets\TicketAssignmentService;
use App\Services\Tickets\TicketAttachmentService;
use App\Services\Tickets\TicketCommentService;
use App\Services\Tickets\TicketCreationService;
use App\Services\Tickets\TicketSavedViewService;
use App\Services\Tickets\TicketTargetService;
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

        $tickets = Ticket::query()
            ->visibleTo($request->user())
            ->with(['company', 'assignee', 'supportProject', 'tracker', 'category', 'tags'])
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request, $textSearch): void {
                $rawSearch = $request->string('search')->toString();
                $ticketNumber = ltrim($rawSearch, '#');

                $query->where(function ($inner) use ($rawSearch, $ticketNumber, $textSearch): void {
                    $textSearch->apply($inner, ['tickets.subject', 'tickets.description'], $rawSearch);
                    $inner
                        ->orWhereHas('company', fn ($company) => $textSearch->apply($company, ['companies.name', 'companies.slug'], $rawSearch))
                        ->orWhereHas('tags', fn ($tag) => $textSearch->apply($tag, ['ticket_tags.name', 'ticket_tags.slug'], $rawSearch));

                    if (ctype_digit($ticketNumber)) {
                        $inner->orWhere('ticket_number', (int) $ticketNumber);
                    }
                });
            })
            ->when($request->string('queue')->isNotEmpty(), fn ($query) => $this->applyQueueFilter($query, $request->string('queue')->toString(), $request))
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->string('priority')->isNotEmpty(), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->string('company')->isNotEmpty(), fn ($query) => $query->whereHas('company', fn ($company) => $company->where('public_id', $request->string('company'))))
            ->when($request->string('project')->isNotEmpty(), fn ($query) => $query->whereHas('supportProject', fn ($project) => $project->where('public_id', $request->string('project'))))
            ->when($request->string('tracker')->isNotEmpty(), fn ($query) => $query->whereHas('tracker', fn ($tracker) => $tracker->where('public_id', $request->string('tracker'))))
            ->when($request->string('category')->isNotEmpty(), fn ($query) => $query->whereHas('category', fn ($category) => $category->where('public_id', $request->string('category'))))
            ->when($request->string('tag')->isNotEmpty(), fn ($query) => $query->whereHas('tags', fn ($tag) => $tag->where('public_id', $request->string('tag'))))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Ticket $ticket): array => $this->ticketSummary($ticket));

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'queue', 'status', 'priority', 'company', 'project', 'tracker', 'category', 'tag']),
            'companies' => Company::clients()->orderBy('name')->get(['public_id', 'name']),
            'projects' => $issueTracking->projectOptions(),
            'trackers' => $issueTracking->trackerOptions(),
            'categories' => $issueTracking->categoryOptions(),
            'tags' => $issueTracking->tagOptions(),
            'statuses' => $this->statusOptions(),
            'priorities' => $this->priorityOptions(),
            'agents' => $this->providerUsers(),
            'savedViews' => $savedViews->visibleTo($request->user(), TicketSavedView::SECTION_ADMIN)
                ->map(fn (TicketSavedView $view): array => $savedViews->payload($view))
                ->values(),
        ]);
    }

    public function create(IssueTrackingService $issueTracking): Response
    {
        $this->authorize('create', Ticket::class);

        return Inertia::render('Admin/Tickets/Create', [
            'companies' => Company::clients()->orderBy('name')->get(['public_id', 'name']),
            'departments' => $this->providerDepartments(),
            'providerUsers' => $this->providerUsers(),
            'priorities' => $this->priorityOptions(),
            'projects' => $issueTracking->projectOptions(),
            'trackers' => $issueTracking->trackerOptions(),
            'categories' => $issueTracking->categoryOptions(),
            'tags' => $issueTracking->tagOptions(),
            'customFields' => $issueTracking->customFieldOptions(),
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
        ], $request->user(), TicketSource::Admin, $request);

        if ($assignee) {
            $assignments->assign($ticket, $assignee, $request->user(), $request);
        }

        return redirect()->route('admin.tickets.show', $ticket->adminRouteParameters())->with('success', 'Ticket created.');
    }

    public function legacyShow(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('view', $ticket);

        return redirect()->route('admin.tickets.show', $ticket->adminRouteParameters());
    }

    public function show(
        Request $request,
        Company $company,
        Ticket $ticket,
        TicketWorkflowService $workflow,
        IssueTrackingService $issueTracking,
        CannedResponseService $cannedResponses,
        MentionParserService $mentions,
    ): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'company',
            'supportProject',
            'tracker.customFields.options',
            'category',
            'tags',
            'customFieldValues.customField.options',
            'assignee',
            'createdBy',
            'requester',
            'comments' => fn ($query) => $query->with(['user', 'apiClient', 'attachments'])->oldest(),
            'attachments' => fn ($query) => $query->whereNull('comment_id'),
            'targetDepartments',
            'targetUsers',
            'watcherUsers',
            'csatSurveys',
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
            'projects' => $issueTracking->projectOptions(),
            'trackers' => $issueTracking->trackerOptions(),
            'categories' => $issueTracking->categoryOptions(),
            'tags' => $issueTracking->tagOptions(),
            'customFields' => $issueTracking->customFieldOptions(),
            'cannedResponses' => $cannedResponses->forUser($request->user())
                ->map(fn (CannedResponse $response): array => [
                    'id' => $response->public_id,
                    'title' => $response->title,
                    'shortcut' => $response->shortcut,
                    'body' => $cannedResponses->render($response, $ticket, $request->user()),
                ])
                ->values(),
            'mentionableUsers' => [
                'public' => $mentions->mentionableUsers($ticket, TicketVisibility::Public),
                'internal' => $mentions->mentionableUsers($ticket, TicketVisibility::Internal),
            ],
        ]);
    }

    public function update(Request $request, Company $company, Ticket $ticket, IssueTrackingService $issueTracking, HtmlSanitizer $sanitizer): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:20000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'project_id' => ['required', 'exists:support_projects,public_id'],
            'tracker_id' => ['required', 'exists:ticket_trackers,public_id'],
            'category_id' => ['nullable', 'exists:ticket_categories,public_id'],
            'tag_names' => ['nullable', 'array', 'max:20'],
            'tag_names.*' => ['string', 'max:40'],
            'custom_fields' => ['nullable', 'array'],
        ]);
        $company = $ticket->company()->firstOrFail();
        $project = $issueTracking->resolveProject($validated['project_id'], $company);
        $tracker = $issueTracking->resolveTracker($validated['tracker_id']);
        $category = $issueTracking->resolveCategory($validated['category_id'] ?? null, $project);

        $ticket->update([
            'subject' => $validated['subject'],
            'description' => array_key_exists('description', $validated) ? $sanitizer->sanitize($validated['description']) : $ticket->description,
            'priority' => $validated['priority'],
            'support_project_id' => $project->id,
            'ticket_tracker_id' => $tracker->id,
            'ticket_category_id' => $category?->id,
            'last_agent_activity_at' => now(),
        ]);

        $issueTracking->syncTags($ticket, $validated['tag_names'] ?? []);
        $issueTracking->syncCustomFields($ticket->refresh(), $tracker, $validated['custom_fields'] ?? []);

        return back()->with('success', 'Ticket updated.');
    }

    public function changeStatus(ChangeTicketStatusRequest $request, Company $company, Ticket $ticket, TicketWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('changeStatus', $ticket);

        $workflow->transition($ticket, TicketStatus::from($request->validated('status')), $request->user(), $request);

        return back()->with('success', 'Status updated.');
    }

    public function assign(AssignTicketRequest $request, Company $company, Ticket $ticket, TicketAssignmentService $assignments): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $assignee = $request->validated('assigned_to_user_id')
            ? User::where('public_id', $request->validated('assigned_to_user_id'))->firstOrFail()
            : null;

        $assignments->assign($ticket, $assignee, $request->user(), $request);

        return back()->with('success', $assignee ? 'Ticket assigned.' : 'Ticket unassigned.');
    }

    public function comment(StoreTicketCommentRequest $request, Company $company, Ticket $ticket, TicketCommentService $comments): RedirectResponse
    {
        $this->authorize('comment', $ticket);

        $comments->create(
            $ticket,
            $request->user(),
            $request->validated('body'),
            TicketVisibility::from($request->validated('visibility')),
            $request,
            (array) $request->file('attachments', []),
            $request->validated('mentioned_user_ids', []),
        );

        return back()->with('success', 'Comment added.');
    }

    public function updateTargets(UpdateTicketTargetsRequest $request, Company $company, Ticket $ticket, TicketTargetService $targets): RedirectResponse
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

    public function addWatcher(StoreTicketWatcherRequest $request, Company $company, Ticket $ticket, TicketWatcherService $watchers): RedirectResponse
    {
        $this->authorize('addWatcher', $ticket);

        $watcher = User::where('public_id', $request->validated('user_id'))->firstOrFail();

        $watchers->add($ticket, $watcher, $request->user());

        return back()->with('success', 'Watcher added.');
    }

    public function removeWatcher(Request $request, Company $company, Ticket $ticket, User $user, TicketWatcherService $watchers): RedirectResponse
    {
        $this->authorize('addWatcher', $ticket);

        $watchers->remove($ticket, $user, $request->user());

        return back()->with('success', 'Watcher removed.');
    }

    public function attachment(Request $request, Company $company, Ticket $ticket, TicketAttachmentService $attachments): RedirectResponse
    {
        $this->authorize('attach', $ticket);

        $validated = $request->validate([
            'file' => AttachmentValidationRules::upload(),
            'visibility' => ['required', 'in:public,internal'],
        ]);

        $attachments->store($ticket, $request->file('file'), $request->user(), TicketVisibility::from($validated['visibility']), $request);

        return back()->with('success', 'Attachment uploaded.');
    }

    private function ticketSummary(Ticket $ticket): array
    {
        return [
            'id' => $ticket->public_id,
            'ticket_number' => $ticket->ticket_number,
            'display_id' => $ticket->displayId(),
            'route_params' => $ticket->adminRouteParameters(),
            'url' => route('admin.tickets.show', $ticket->adminRouteParameters()),
            'subject' => $ticket->subject,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'company' => $ticket->company?->name,
            'company_slug' => $ticket->company?->slug,
            'project' => $ticket->supportProject?->name,
            'project_id' => $ticket->supportProject?->public_id,
            'tracker' => $ticket->tracker?->name,
            'tracker_id' => $ticket->tracker?->public_id,
            'category' => $ticket->category?->name,
            'category_id' => $ticket->category?->public_id,
            'tags' => $ticket->tags->map(fn (TicketTag $tag): array => [
                'id' => $tag->public_id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])->values(),
            'assignee' => $ticket->assignee?->name,
            'assignee_id' => $ticket->assignee?->public_id,
            'created_at' => $ticket->created_at?->toISOString(),
            'sla' => $ticket->sla_first_response_breached_at || $ticket->sla_resolution_breached_at ? 'breached' : null,
        ];
    }

    private function applyQueueFilter($query, string $queue, Request $request): void
    {
        match ($queue) {
            'mine' => $query->where('assigned_to_user_id', $request->user()->id),
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

    private function ticketDetail(Ticket $ticket): array
    {
        return [
            ...$this->ticketSummary($ticket),
            'description' => $ticket->description,
            'source' => $ticket->source->value,
            'requester' => $ticket->requester?->name,
            'created_by' => $ticket->createdBy?->name,
            'custom_fields' => $ticket->customFieldValues
                ->map(fn ($value): array => [
                    'id' => $value->customField?->public_id,
                    'name' => $value->customField?->name,
                    'type' => $value->customField?->type,
                    'value' => $value->value['value'] ?? null,
                ])
                ->filter(fn (array $field): bool => $field['id'] !== null)
                ->values(),
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
            'csat' => [
                'latest_rating' => $ticket->csatSurveys->sortByDesc('responded_at')->first()?->rating,
                'average_rating' => $ticket->csatSurveys->whereNotNull('rating')->avg('rating'),
                'responses_count' => $ticket->csatSurveys->whereNotNull('responded_at')->count(),
            ],
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
            'mime_type' => $attachment->mime_type,
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
