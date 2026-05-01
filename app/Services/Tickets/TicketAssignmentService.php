<?php

namespace App\Services\Tickets;

use App\Enums\CompanyType;
use App\Enums\UserStatus;
use App\Jobs\SendTicketNotification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketAssignmentService
{
    public function __construct(private readonly TicketEventRecorder $events) {}

    public function assign(Ticket $ticket, ?User $assignee, User $actor, ?Request $request = null): Ticket
    {
        if (! $actor->can('tickets.assign') || ! $actor->isProviderUser()) {
            throw new AuthorizationException('You are not allowed to assign tickets.');
        }

        if ($assignee) {
            $this->assertAssignable($assignee);
        }

        return DB::transaction(function () use ($ticket, $assignee, $actor, $request): Ticket {
            $previous = $ticket->assigned_to_user_id;

            $ticket->forceFill([
                'assigned_to_user_id' => $assignee?->id,
                'last_agent_activity_at' => now(),
            ])->save();

            $this->events->record(
                ticket: $ticket,
                eventType: $assignee ? 'ticket.assigned' : 'ticket.unassigned',
                actor: $actor,
                oldValues: ['assigned_to_user_id' => $previous],
                newValues: ['assigned_to_user_id' => $assignee?->id],
                request: $request,
            );

            if ($assignee) {
                SendTicketNotification::dispatch($ticket, 'ticket.assigned')->afterCommit()->onQueue('notifications');
            }

            return $ticket->refresh();
        });
    }

    private function assertAssignable(User $assignee): void
    {
        if ($assignee->status !== UserStatus::Active || $assignee->company?->type !== CompanyType::Provider) {
            throw ValidationException::withMessages(['assigned_to_user_id' => 'The assignee must be an active provider user.']);
        }

        if (! $assignee->can('tickets.update')) {
            throw ValidationException::withMessages(['assigned_to_user_id' => 'The assignee must be an agent or provider admin.']);
        }
    }
}
