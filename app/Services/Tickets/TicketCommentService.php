<?php

namespace App\Services\Tickets;

use App\Enums\TicketVisibility;
use App\Jobs\SendTicketNotification;
use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketCommentService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TicketEventRecorder $events,
        private readonly TicketWorkflowService $workflow,
    ) {}

    public function create(Ticket $ticket, User|ApiClient $actor, string $body, TicketVisibility $visibility, ?Request $request = null): TicketComment
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

        return DB::transaction(function () use ($ticket, $actor, $body, $visibility, $request): TicketComment {
            $comment = TicketComment::create([
                'company_id' => $ticket->company_id,
                'ticket_id' => $ticket->id,
                'user_id' => $actor instanceof User ? $actor->id : null,
                'api_client_id' => $actor instanceof ApiClient ? $actor->id : null,
                'visibility' => $visibility,
                'body' => $body,
            ]);

            $isCustomerSide = $actor instanceof ApiClient || ($actor instanceof User && $actor->isCustomerUser());

            $ticket->forceFill([
                'last_customer_activity_at' => $isCustomerSide ? now() : $ticket->last_customer_activity_at,
                'last_agent_activity_at' => ! $isCustomerSide ? now() : $ticket->last_agent_activity_at,
            ])->save();

            if ($isCustomerSide && $visibility === TicketVisibility::Public) {
                $this->workflow->handleCustomerReply($ticket->refresh(), $actor, $request);
            }

            $this->events->record(
                ticket: $ticket->refresh(),
                eventType: $visibility === TicketVisibility::Internal ? 'ticket.internal_note.created' : 'ticket.comment.created',
                actor: $actor,
                newValues: [
                    'comment_id' => $comment->public_id,
                    'visibility' => $visibility->value,
                ],
                request: $request,
            );

            $this->audit->log('ticket.comment.created', $ticket, $actor, after: [
                'comment_id' => $comment->public_id,
                'visibility' => $visibility->value,
            ], request: $request);

            SendTicketNotification::dispatch($ticket, 'ticket.comment.created')->afterCommit()->onQueue('notifications');

            return $comment;
        });
    }
}
