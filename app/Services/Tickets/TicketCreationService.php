<?php

namespace App\Services\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketNotification;
use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Audit\AuditLogger;
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
    ) {}

    /**
     * @param  array{company_id:int, subject:string, description:string, priority?:string|TicketPriority, assigned_to_user_id?:int|null, target_department_ids?:list<string>, target_user_ids?:list<string>, attachments?:list<UploadedFile>}  $data
     */
    public function create(array $data, User|ApiClient $actor, TicketSource $source, ?Request $request = null): Ticket
    {
        return DB::transaction(function () use ($data, $actor, $source, $request): Ticket {
            $isUser = $actor instanceof User;

            $ticket = Ticket::create([
                'company_id' => $data['company_id'],
                'created_by_user_id' => $isUser ? $actor->id : null,
                'requester_user_id' => $isUser && $actor->isCustomerUser() ? $actor->id : null,
                'api_client_id' => $actor instanceof ApiClient ? $actor->id : null,
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'status' => TicketStatus::Open,
                'priority' => $data['priority'] ?? TicketPriority::Normal,
                'source' => $source,
                'last_customer_activity_at' => $source !== TicketSource::Admin ? now() : null,
                'last_agent_activity_at' => $source === TicketSource::Admin ? now() : null,
            ]);

            $this->events->record(
                ticket: $ticket,
                eventType: 'ticket.created',
                actor: $actor,
                newValues: [
                    'subject' => $ticket->subject,
                    'priority' => $ticket->priority->value,
                    'source' => $ticket->source->value,
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

            foreach ($data['attachments'] ?? [] as $file) {
                $this->attachments->store($ticket, $file, $actor, request: $request);
            }

            SendTicketNotification::dispatch($ticket, 'ticket.created')->afterCommit()->onQueue('notifications');

            return $ticket;
        });
    }
}
