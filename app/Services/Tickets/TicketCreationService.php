<?php

namespace App\Services\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketNotification;
use App\Models\ApiClient;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\HtmlSanitizer;
use App\Services\Sla\SlaService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TicketCreationService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TicketEventRecorder $events,
        private readonly TicketTargetService $targets,
        private readonly TicketAttachmentService $attachments,
        private readonly IssueTrackingService $issueTracking,
        private readonly HtmlSanitizer $sanitizer,
        private readonly SlaService $sla,
    ) {}

    /**
     * @param  array{company_id:int, subject:string, description:string, priority?:string|TicketPriority|null, assigned_to_user_id?:int|null, target_department_ids?:list<string>, target_user_ids?:list<string>, attachments?:list<UploadedFile>, project_id?:string|null, tracker_id?:string|null, category_id?:string|null, tag_names?:list<string>, custom_fields?:array<string, mixed>}  $data
     */
    public function create(array $data, User|ApiClient $actor, TicketSource $source, ?Request $request = null): Ticket
    {
        return DB::transaction(function () use ($data, $actor, $source, $request): Ticket {
            $isUser = $actor instanceof User;
            $company = Company::query()->findOrFail($data['company_id']);
            $project = $this->issueTracking->resolveProject($data['project_id'] ?? null, $company);
            $tracker = $this->issueTracking->resolveTracker($data['tracker_id'] ?? null);
            $category = $this->issueTracking->resolveCategory($data['category_id'] ?? null, $project);

            $ticket = Ticket::create([
                'company_id' => $data['company_id'],
                'support_project_id' => $project->id,
                'ticket_tracker_id' => $tracker->id,
                'ticket_category_id' => $category?->id,
                'created_by_user_id' => $isUser ? $actor->id : null,
                'requester_user_id' => $isUser && $actor->isCustomerUser() ? $actor->id : null,
                'api_client_id' => $actor instanceof ApiClient ? $actor->id : null,
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'subject' => $data['subject'],
                'description' => $this->sanitizer->sanitize($data['description']),
                'status' => TicketStatus::Open,
                'priority' => $data['priority'] ?? TicketPriority::Normal,
                'source' => $source,
                'last_customer_activity_at' => $source !== TicketSource::Admin ? now() : null,
                'last_agent_activity_at' => $source === TicketSource::Admin ? now() : null,
            ]);

            $this->sla->applyToTicket($ticket);

            $this->events->record(
                ticket: $ticket,
                eventType: 'ticket.created',
                actor: $actor,
                newValues: [
                    'subject' => $ticket->subject,
                    'priority' => $ticket->priority->value,
                    'source' => $ticket->source->value,
                    'project' => $project->name,
                    'tracker' => $tracker->name,
                    'category' => $category?->name,
                    'tags' => $data['tag_names'] ?? [],
                ],
                request: $request,
            );

            $this->audit->log('ticket.created', $ticket, $actor, after: $ticket->only([
                'company_id',
                'subject',
                'status',
                'priority',
                'source',
            ]), request: $request);

            $this->targets->sync(
                $ticket,
                $data['target_department_ids'] ?? [],
                $data['target_user_ids'] ?? [],
                $actor,
                $request,
            );

            $this->issueTracking->syncTags($ticket, $data['tag_names'] ?? []);
            $this->issueTracking->syncCustomFields($ticket, $tracker, $data['custom_fields'] ?? []);

            foreach ($data['attachments'] ?? [] as $file) {
                $this->attachments->store($ticket, $file, $actor, request: $request);
            }

            SendTicketNotification::dispatch($ticket, 'ticket.created')->afterCommit()->onQueue('notifications');

            return $ticket;
        });
    }
}
