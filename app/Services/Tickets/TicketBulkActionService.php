<?php

namespace App\Services\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketBulkActionService
{
    public function __construct(
        private readonly TicketWorkflowService $workflow,
        private readonly TicketAssignmentService $assignments,
        private readonly IssueTrackingService $issueTracking,
        private readonly TicketEventRecorder $events,
        private readonly AuditLogger $audit,
    ) {}

    public function updateForProvider(User $actor, array $data, ?Request $request = null): int
    {
        if (! $actor->isProviderUser() || ! $actor->can('tickets.bulk_update')) {
            throw new AuthorizationException('You are not allowed to bulk update tickets.');
        }

        $tickets = Ticket::query()
            ->visibleTo($actor)
            ->whereIn('public_id', $data['ticket_ids'])
            ->get();

        return DB::transaction(function () use ($tickets, $actor, $data, $request): int {
            foreach ($tickets as $ticket) {
                $this->applyChanges($ticket, $actor, $data, $request, allowAssignment: true);
            }

            return $tickets->count();
        });
    }

    public function updateForApi(ApiClient $client, array $data, ?Request $request = null): int
    {
        if (! $client->tokenCan('tickets:bulk_update')) {
            throw new AuthorizationException('This token cannot bulk update tickets.');
        }

        $tickets = Ticket::query()
            ->where('company_id', $client->company_id)
            ->whereIn('public_id', $data['ticket_ids'])
            ->get();

        return DB::transaction(function () use ($tickets, $client, $data, $request): int {
            foreach ($tickets as $ticket) {
                $this->applyChanges($ticket, $client, $data, $request, allowAssignment: false);
            }

            return $tickets->count();
        });
    }

    private function applyChanges(Ticket $ticket, User|ApiClient $actor, array $data, ?Request $request, bool $allowAssignment): void
    {
        if (isset($data['status'])) {
            $status = TicketStatus::from($data['status']);

            if ($actor instanceof ApiClient && ! in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
                throw ValidationException::withMessages(['status' => 'API bulk status updates can only resolve or close tickets.']);
            }

            $this->workflow->forceTransition($ticket, $status, $actor, $request);
            $ticket->refresh();
        }

        if (isset($data['assigned_to_user_id']) && $allowAssignment && $actor instanceof User) {
            $assignee = $data['assigned_to_user_id']
                ? User::query()->where('public_id', $data['assigned_to_user_id'])->firstOrFail()
                : null;

            $this->assignments->assign($ticket, $assignee, $actor, $request);
            $ticket->refresh();
        }

        if (isset($data['priority'])) {
            $priority = TicketPriority::from($data['priority']);
            $previous = $ticket->priority?->value;

            if ($previous !== $priority->value) {
                $ticket->forceFill(['priority' => $priority])->save();

                $this->events->record(
                    ticket: $ticket,
                    eventType: 'ticket.priority_changed',
                    actor: $actor,
                    oldValues: ['priority' => $previous],
                    newValues: ['priority' => $priority->value],
                    request: $request,
                );

                $this->audit->log('ticket.priority_changed', $ticket, $actor, before: ['priority' => $previous], after: ['priority' => $priority->value], request: $request);
            }
        }

        if (array_key_exists('tag_names', $data)) {
            $this->issueTracking->syncTags($ticket, $data['tag_names'] ?? []);

            $this->events->record(
                ticket: $ticket,
                eventType: 'ticket.tags_changed',
                actor: $actor,
                newValues: ['tags' => $data['tag_names'] ?? []],
                request: $request,
            );
        }
    }
}
