<?php

namespace App\Services\Sla;

use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\CompanySlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

class SlaService
{
    public function ensurePolicies(Company $company): void
    {
        foreach (config('support.sla.defaults', []) as $priority => $values) {
            CompanySlaPolicy::query()->firstOrCreate([
                'company_id' => $company->id,
                'priority' => $priority,
            ], [
                'first_response_minutes' => $values['first_response_minutes'],
                'resolution_minutes' => $values['resolution_minutes'],
                'enabled' => true,
            ]);
        }
    }

    public function applyToTicket(Ticket $ticket): void
    {
        $ticket->loadMissing('company');

        if (! $ticket->company) {
            return;
        }

        $this->ensurePolicies($ticket->company);

        $policy = $ticket->company->slaPolicies()
            ->where('priority', $ticket->priority->value)
            ->where('enabled', true)
            ->first();

        if (! $policy) {
            return;
        }

        $createdAt = $ticket->created_at ?? now();

        $ticket->forceFill([
            'first_response_due_at' => $createdAt->copy()->addMinutes($policy->first_response_minutes),
            'due_at' => $createdAt->copy()->addMinutes($policy->resolution_minutes),
            'sla_policy_snapshot' => [
                'company_id' => $ticket->company->public_id,
                'priority' => $policy->priority->value,
                'first_response_minutes' => $policy->first_response_minutes,
                'resolution_minutes' => $policy->resolution_minutes,
            ],
        ])->save();
    }

    public function markFirstResponse(Ticket $ticket, User $actor): void
    {
        if (! $actor->isProviderUser() || $ticket->first_responded_at !== null) {
            return;
        }

        $now = now();

        $ticket->forceFill([
            'first_responded_at' => $now,
            'sla_first_response_breached_at' => $ticket->first_response_due_at && $now->gt($ticket->first_response_due_at)
                ? ($ticket->sla_first_response_breached_at ?? $now)
                : $ticket->sla_first_response_breached_at,
        ])->save();
    }

    public function syncResolution(Ticket $ticket): void
    {
        if (! in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            return;
        }

        if ($ticket->due_at && now()->gt($ticket->due_at) && $ticket->sla_resolution_breached_at === null) {
            $ticket->forceFill(['sla_resolution_breached_at' => now()])->save();
        }
    }

    public function markBreaches(?Company $company = null): int
    {
        $now = now();
        $count = 0;

        Ticket::query()
            ->when($company, fn ($query) => $query->where('company_id', $company->id))
            ->whereNotIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->where(function ($query) use ($now): void {
                $query->where(function ($first) use ($now): void {
                    $first->whereNull('first_responded_at')
                        ->whereNull('sla_first_response_breached_at')
                        ->whereNotNull('first_response_due_at')
                        ->where('first_response_due_at', '<', $now);
                })->orWhere(function ($resolution) use ($now): void {
                    $resolution->whereNull('sla_resolution_breached_at')
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', $now);
                });
            })
            ->each(function (Ticket $ticket) use ($now, &$count): void {
                $updates = [];

                if ($ticket->first_responded_at === null && $ticket->first_response_due_at && $now->gt($ticket->first_response_due_at)) {
                    $updates['sla_first_response_breached_at'] = $now;
                }

                if ($ticket->due_at && $now->gt($ticket->due_at)) {
                    $updates['sla_resolution_breached_at'] = $now;
                }

                if ($updates) {
                    $ticket->forceFill($updates)->save();
                    $count++;
                }
            });

        return $count;
    }

    public function statusFor(Ticket $ticket): string
    {
        if ($ticket->sla_first_response_breached_at || $ticket->sla_resolution_breached_at) {
            return 'breached';
        }

        $soon = Carbon::now()->addHours(4);

        if (($ticket->first_response_due_at && $ticket->first_responded_at === null && $ticket->first_response_due_at->lte($soon))
            || ($ticket->due_at && ! in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true) && $ticket->due_at->lte($soon))) {
            return 'due_soon';
        }

        return 'on_track';
    }
}
