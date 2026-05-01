<?php

namespace App\Services\Tickets;

use App\Enums\TicketStatus;
use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketWorkflowService
{
    public function __construct(private readonly TicketEventRecorder $events) {}

    /**
     * @return array<string, list<TicketStatus>>
     */
    public function transitions(): array
    {
        return [
            TicketStatus::Open->value => [
                TicketStatus::InProgress,
                TicketStatus::WaitingOnCustomer,
                TicketStatus::Pending,
                TicketStatus::Resolved,
                TicketStatus::Closed,
            ],
            TicketStatus::InProgress->value => [
                TicketStatus::WaitingOnCustomer,
                TicketStatus::Pending,
                TicketStatus::Resolved,
                TicketStatus::Closed,
            ],
            TicketStatus::WaitingOnCustomer->value => [
                TicketStatus::InProgress,
                TicketStatus::Resolved,
                TicketStatus::Closed,
            ],
            TicketStatus::Pending->value => [
                TicketStatus::InProgress,
                TicketStatus::Resolved,
                TicketStatus::Closed,
            ],
            TicketStatus::Resolved->value => [
                TicketStatus::InProgress,
                TicketStatus::Closed,
            ],
            TicketStatus::Closed->value => [
                TicketStatus::InProgress,
            ],
        ];
    }

    public function availableTransitions(Ticket $ticket, ?User $actor = null): array
    {
        $statuses = $this->transitions()[$ticket->status->value] ?? [];

        if ($ticket->status === TicketStatus::Closed && ! $actor?->can('tickets.change_status')) {
            return [];
        }

        return array_map(fn (TicketStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], $statuses);
    }

    public function transition(Ticket $ticket, TicketStatus $to, User|ApiClient $actor, ?Request $request = null): Ticket
    {
        if ($actor instanceof ApiClient) {
            throw new AuthorizationException('API clients cannot change ticket status directly.');
        }

        if (! $actor->can('tickets.change_status')) {
            throw new AuthorizationException('You are not allowed to change ticket status.');
        }

        $this->assertCanTransition($ticket, $to, $actor);

        return $this->forceTransition($ticket, $to, $actor, $request);
    }

    public function forceTransition(Ticket $ticket, TicketStatus $to, User|ApiClient|null $actor = null, ?Request $request = null): Ticket
    {
        if ($ticket->status === $to) {
            return $ticket;
        }

        $from = $ticket->status;

        $ticket->forceFill([
            'status' => $to,
            'resolved_at' => $to === TicketStatus::Resolved ? now() : ($to === TicketStatus::Closed ? $ticket->resolved_at : null),
            'closed_at' => $to === TicketStatus::Closed ? now() : null,
        ])->save();

        $this->events->record(
            ticket: $ticket,
            eventType: $this->eventTypeForStatus($to, $from),
            actor: $actor,
            oldValues: ['status' => $from->value],
            newValues: ['status' => $to->value],
            request: $request,
        );

        return $ticket->refresh();
    }

    public function handleCustomerReply(Ticket $ticket, User|ApiClient $actor, ?Request $request = null): void
    {
        if ($ticket->status === TicketStatus::WaitingOnCustomer) {
            $this->forceTransition(
                $ticket,
                $ticket->assigned_to_user_id ? TicketStatus::InProgress : TicketStatus::Open,
                $actor,
                $request,
            );
        }

        if ($ticket->status === TicketStatus::Resolved) {
            $this->forceTransition($ticket, TicketStatus::Open, $actor, $request);
        }
    }

    private function assertCanTransition(Ticket $ticket, TicketStatus $to, User $actor): void
    {
        $allowed = $this->transitions()[$ticket->status->value] ?? [];

        if ($ticket->status === TicketStatus::Closed && ! $actor->can('tickets.change_status')) {
            throw ValidationException::withMessages(['status' => 'Closed tickets can only be reopened by an authorized provider user.']);
        }

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'This ticket status transition is not allowed.']);
        }
    }

    private function eventTypeForStatus(TicketStatus $to, TicketStatus $from): string
    {
        return match ($to) {
            TicketStatus::Resolved => 'ticket.resolved',
            TicketStatus::Closed => 'ticket.closed',
            TicketStatus::Open, TicketStatus::InProgress => $from === TicketStatus::Resolved || $from === TicketStatus::Closed
                ? 'ticket.reopened'
                : 'ticket.status_changed',
            default => 'ticket.status_changed',
        };
    }
}
