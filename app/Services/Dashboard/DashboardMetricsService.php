<?php

namespace App\Services\Dashboard;

use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardMetricsService
{
    public function admin(User $user): array
    {
        $query = Ticket::query()->visibleTo($user);

        return [
            'summary' => $this->summary($query),
            'by_status' => $this->countsBy($query, 'status'),
            'by_priority' => $this->countsBy($query, 'priority'),
            'recent_events' => TicketEvent::query()
                ->with(['ticket.company', 'actor', 'apiClient'])
                ->latest('occurred_at')
                ->limit(10)
                ->get()
                ->map(fn (TicketEvent $event): array => [
                    'id' => $event->id,
                    'type' => $event->event_type,
                    'ticket_id' => $event->ticket?->displayId(),
                    'ticket_number' => $event->ticket?->ticket_number,
                    'ticket_url' => $event->ticket ? route('admin.tickets.show', $event->ticket->adminRouteParameters()) : null,
                    'ticket' => $event->ticket?->subject,
                    'company' => $event->ticket?->company?->name,
                    'actor' => $event->actor?->name ?? $event->apiClient?->name ?? 'System',
                    'occurred_at' => $event->occurred_at?->toISOString(),
                ]),
        ];
    }

    public function portal(Company $company): array
    {
        $query = Ticket::query()->where('company_id', $company->id);

        return [
            'summary' => $this->summary($query),
            'by_status' => $this->countsBy($query, 'status'),
            'recent_tickets' => Ticket::query()
                ->where('company_id', $company->id)
                ->with('assignee')
                ->latest('updated_at')
                ->limit(8)
                ->get()
                ->map(fn (Ticket $ticket): array => [
                    'id' => $ticket->public_id,
                    'ticket_number' => $ticket->ticket_number,
                    'display_id' => $ticket->displayId(),
                    'route_params' => $ticket->portalRouteParameters(),
                    'url' => route('portal.tickets.show', $ticket->portalRouteParameters()),
                    'subject' => $ticket->subject,
                    'status' => $ticket->status->value,
                    'priority' => $ticket->priority->value,
                    'assignee' => $ticket->assignee?->name,
                    'updated_at' => $ticket->updated_at?->toISOString(),
                ]),
        ];
    }

    private function summary(Builder $query): array
    {
        $base = clone $query;
        $openStatuses = [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::WaitingOnCustomer->value,
            TicketStatus::Pending->value,
        ];

        return [
            'open' => (clone $base)->whereIn('status', $openStatuses)->count(),
            'resolved' => (clone $base)->where('status', TicketStatus::Resolved->value)->count(),
            'closed' => (clone $base)->where('status', TicketStatus::Closed->value)->count(),
            'waiting_on_customer' => (clone $base)->where('status', TicketStatus::WaitingOnCustomer->value)->count(),
            'unassigned' => (clone $base)->whereNull('assigned_to_user_id')->whereIn('status', $openStatuses)->count(),
            'overdue' => (clone $base)
                ->whereIn('status', $openStatuses)
                ->where(fn ($q) => $q
                    ->whereNotNull('sla_first_response_breached_at')
                    ->orWhereNotNull('sla_resolution_breached_at'))
                ->count(),
            'due_soon' => (clone $base)
                ->whereIn('status', $openStatuses)
                ->whereNull('sla_first_response_breached_at')
                ->whereNull('sla_resolution_breached_at')
                ->where(fn ($q) => $q
                    ->where(fn ($first) => $first
                        ->whereNull('first_responded_at')
                        ->whereBetween('first_response_due_at', [now(), now()->addHours(4)]))
                    ->orWhereBetween('due_at', [now(), now()->addHours(4)]))
                ->count(),
        ];
    }

    private function countsBy(Builder $query, string $column): array
    {
        return (clone $query)
            ->selectRaw($column.', count(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->all();
    }
}
