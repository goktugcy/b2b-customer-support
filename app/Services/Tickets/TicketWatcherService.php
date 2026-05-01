<?php

namespace App\Services\Tickets;

use App\Enums\TicketWatcherSide;
use App\Jobs\SendTicketNotification;
use App\Models\Ticket;
use App\Models\TicketWatcher;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketWatcherService
{
    public function __construct(private readonly TicketEventRecorder $events) {}

    public function add(Ticket $ticket, User $watcher, User $actor): TicketWatcher
    {
        if (! $actor->can('tickets.add_watcher')) {
            throw new AuthorizationException('You are not allowed to add watchers.');
        }

        $side = $this->sideFor($ticket, $watcher, $actor);

        return DB::transaction(function () use ($ticket, $watcher, $actor, $side): TicketWatcher {
            $ticketWatcher = TicketWatcher::firstOrCreate([
                'ticket_id' => $ticket->id,
                'user_id' => $watcher->id,
            ], [
                'side' => $side,
                'added_by_user_id' => $actor->id,
            ]);

            if ($ticketWatcher->wasRecentlyCreated) {
                $this->events->record(
                    ticket: $ticket,
                    eventType: 'ticket.watcher_added',
                    actor: $actor,
                    newValues: [
                        'watcher_id' => $watcher->public_id,
                        'side' => $side->value,
                    ],
                );

                SendTicketNotification::dispatch($ticket, 'ticket.watcher_added', $watcher->id)
                    ->afterCommit()
                    ->onQueue('notifications');
            }

            return $ticketWatcher;
        });
    }

    public function remove(Ticket $ticket, User $watcher, User $actor): void
    {
        if (! $actor->can('tickets.add_watcher')) {
            throw new AuthorizationException('You are not allowed to remove watchers.');
        }

        $this->sideFor($ticket, $watcher, $actor);

        $deleted = TicketWatcher::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $watcher->id)
            ->delete();

        if ($deleted > 0) {
            $this->events->record(
                ticket: $ticket,
                eventType: 'ticket.watcher_removed',
                actor: $actor,
                oldValues: ['watcher_id' => $watcher->public_id],
            );
        }
    }

    private function sideFor(Ticket $ticket, User $watcher, User $actor): TicketWatcherSide
    {
        if ($actor->isProviderUser()) {
            if (! $watcher->isProviderUser()) {
                throw ValidationException::withMessages([
                    'user_id' => 'Provider users can only add provider-side watchers.',
                ]);
            }

            return TicketWatcherSide::Provider;
        }

        if ($watcher->company_id !== $ticket->company_id || $watcher->company_id !== $actor->company_id) {
            throw ValidationException::withMessages([
                'user_id' => 'Customer users can only add watchers from their own company.',
            ]);
        }

        return TicketWatcherSide::Client;
    }
}
