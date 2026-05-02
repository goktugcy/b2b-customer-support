<?php

namespace App\Services\Tickets;

use App\Enums\TicketVisibility;
use App\Jobs\SendTicketNotification;
use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\HtmlSanitizer;
use App\Services\Sla\SlaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TicketCommentService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TicketEventRecorder $events,
        private readonly TicketWorkflowService $workflow,
        private readonly TicketAttachmentService $attachments,
        private readonly MentionParserService $mentions,
        private readonly HtmlSanitizer $sanitizer,
        private readonly SlaService $sla,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function create(Ticket $ticket, User|ApiClient $actor, string $body, TicketVisibility $visibility, ?Request $request = null, array $files = [], array $mentionedUserIds = []): TicketComment
    {
        if ($actor instanceof ApiClient && $visibility !== TicketVisibility::Public) {
            throw new AuthorizationException('API clients can only create public comments.');
        }

        if ($actor instanceof User) {
            if ($visibility === TicketVisibility::Internal && (! $actor->isProviderUser() || ! $actor->can('tickets.comment_internal'))) {
                throw new AuthorizationException('You are not allowed to create internal notes.');
            }

            if ($visibility === TicketVisibility::Public && ! $actor->can('tickets.comment_public')) {
                throw new AuthorizationException('You are not allowed to comment on tickets.');
            }
        }

        return DB::transaction(function () use ($ticket, $actor, $body, $visibility, $request, $files, $mentionedUserIds): TicketComment {
            $comment = TicketComment::create([
                'company_id' => $ticket->company_id,
                'ticket_id' => $ticket->id,
                'user_id' => $actor instanceof User ? $actor->id : null,
                'api_client_id' => $actor instanceof ApiClient ? $actor->id : null,
                'visibility' => $visibility,
                'body' => $this->sanitizer->sanitize($body),
            ]);

            $isCustomerSide = $actor instanceof ApiClient || ($actor instanceof User && $actor->isCustomerUser());

            $ticket->forceFill([
                'last_customer_activity_at' => $isCustomerSide ? now() : $ticket->last_customer_activity_at,
                'last_agent_activity_at' => ! $isCustomerSide ? now() : $ticket->last_agent_activity_at,
            ])->save();

            if ($isCustomerSide && $visibility === TicketVisibility::Public) {
                $this->workflow->handleCustomerReply($ticket->refresh(), $actor, $request);
            }

            if ($actor instanceof User && $visibility === TicketVisibility::Public) {
                $this->sla->markFirstResponse($ticket->refresh(), $actor);
            }

            $eventType = $visibility === TicketVisibility::Internal ? 'ticket.internal_note.created' : 'ticket.comment.created';

            $this->events->record(
                ticket: $ticket->refresh(),
                eventType: $eventType,
                actor: $actor,
                newValues: [
                    'comment_id' => $comment->public_id,
                    'visibility' => $visibility->value,
                ],
                request: $request,
            );

            $this->audit->log($eventType, $ticket, $actor, after: [
                'comment_id' => $comment->public_id,
                'visibility' => $visibility->value,
            ], request: $request);

            foreach ($files as $file) {
                $this->attachments->store($ticket, $file, $actor, $visibility, $request, $comment);
            }

            if ($actor instanceof User) {
                $this->mentions->createMentions($comment, $actor, $mentionedUserIds);
            }

            if ($visibility === TicketVisibility::Public) {
                SendTicketNotification::dispatch($ticket, 'ticket.comment.created')->afterCommit()->onQueue('notifications');
            }

            return $comment;
        });
    }
}
