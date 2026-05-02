<?php

namespace App\Services\Automation;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketWatcherSide;
use App\Models\AutomationRule;
use App\Models\AutomationRuleExecution;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketTag;
use App\Models\TicketWatcher;
use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AutomationRuleService
{
    public const TRIGGERS = [
        'ticket.created',
        'ticket.comment.created',
        'ticket.status_changed',
        'ticket.resolved',
        'ticket.closed',
        'ticket.reopened',
        'ticket.sla_due_soon',
        'ticket.sla_breached',
        'csat.submitted',
    ];

    public function runForEvent(TicketEvent $event): void
    {
        if (! in_array($event->event_type, self::TRIGGERS, true)) {
            return;
        }

        $ticket = $event->ticket;

        if (! $ticket) {
            return;
        }

        AutomationRule::query()
            ->where('enabled', true)
            ->where('trigger', $event->event_type)
            ->where(fn (Builder $query) => $query
                ->whereNull('company_id')
                ->orWhere('company_id', $ticket->company_id))
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->each(function (AutomationRule $rule) use ($ticket, $event): void {
                if (! $this->matches($rule, $ticket, $event)) {
                    return;
                }

                $this->execute($rule, $ticket, $event);
            });
    }

    public function preview(AutomationRule $rule, Ticket $ticket, string $trigger): array
    {
        return [
            'matches' => $this->matches($rule, $ticket, new TicketEvent([
                'event_type' => $trigger,
                'new_values' => [],
                'old_values' => [],
            ])),
            'actions' => $rule->actions ?? [],
        ];
    }

    private function execute(AutomationRule $rule, Ticket $ticket, TicketEvent $event): void
    {
        $execution = AutomationRuleExecution::create([
            'automation_rule_id' => $rule->id,
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'trigger' => $event->event_type,
            'status' => 'processing',
            'context' => [
                'event_id' => $event->id,
                'ticket_id' => $ticket->public_id,
                'display_id' => $ticket->displayId(),
            ],
            'actions' => $rule->actions ?? [],
            'executed_at' => now(),
        ]);

        try {
            DB::transaction(function () use ($rule, $ticket, $event): void {
                foreach ($rule->actions ?? [] as $action) {
                    $this->applyAction($ticket, $action, $event);
                }

                $rule->forceFill(['last_run_at' => now()])->save();
            });

            $execution->update(['status' => 'completed']);
        } catch (\Throwable $exception) {
            $execution->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    private function matches(AutomationRule $rule, Ticket $ticket, TicketEvent $event): bool
    {
        $conditions = $rule->conditions ?? [];

        if (($conditions['status'] ?? null) && $ticket->status->value !== $conditions['status']) {
            return false;
        }

        if (($conditions['priority'] ?? null) && $ticket->priority->value !== $conditions['priority']) {
            return false;
        }

        if (($conditions['company'] ?? null) && ! in_array($conditions['company'], [$ticket->company?->public_id, $ticket->company?->slug], true)) {
            return false;
        }

        if (($conditions['changed_to'] ?? null) && ($event->new_values['status'] ?? null) !== $conditions['changed_to']) {
            return false;
        }

        $tag = $conditions['tag'] ?? null;

        if ($tag && ! $ticket->tags()->where(fn (Builder $query) => $query->where('slug', $tag)->orWhere('name', $tag)->orWhere('public_id', $tag))->exists()) {
            return false;
        }

        return true;
    }

    private function applyAction(Ticket $ticket, array $action, TicketEvent $event): void
    {
        match ($action['type'] ?? null) {
            'set_status' => $this->setStatus($ticket, $action['value'] ?? null),
            'set_priority' => $this->setPriority($ticket, $action['value'] ?? null),
            'assign' => $this->assign($ticket, $action['user_id'] ?? null),
            'add_tag' => $this->addTag($ticket, $action['name'] ?? null),
            'add_watcher' => $this->addWatcher($ticket, $action['user_id'] ?? null),
            'send_notification' => $this->sendNotification($ticket, $action['user_id'] ?? null),
            default => null,
        };
    }

    private function setStatus(Ticket $ticket, ?string $value): void
    {
        $status = $value ? TicketStatus::tryFrom($value) : null;

        if (! $status || $ticket->status === $status) {
            return;
        }

        $ticket->forceFill([
            'status' => $status,
            'resolved_at' => $status === TicketStatus::Resolved ? now() : $ticket->resolved_at,
            'closed_at' => $status === TicketStatus::Closed ? now() : null,
        ])->save();
    }

    private function setPriority(Ticket $ticket, ?string $value): void
    {
        $priority = $value ? TicketPriority::tryFrom($value) : null;

        if ($priority) {
            $ticket->forceFill(['priority' => $priority])->save();
        }
    }

    private function assign(Ticket $ticket, ?string $userPublicId): void
    {
        $user = $this->providerUser($userPublicId);

        if ($user) {
            $ticket->forceFill(['assigned_to_user_id' => $user->id])->save();
        }
    }

    private function addTag(Ticket $ticket, ?string $name): void
    {
        if (! $name) {
            return;
        }

        $tag = TicketTag::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'color' => '#64748b'],
        );

        $ticket->tags()->syncWithoutDetaching([$tag->id]);
    }

    private function addWatcher(Ticket $ticket, ?string $userPublicId): void
    {
        $user = $this->providerUser($userPublicId);

        if (! $user) {
            return;
        }

        TicketWatcher::firstOrCreate([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ], [
            'side' => TicketWatcherSide::Provider,
        ]);
    }

    private function sendNotification(Ticket $ticket, ?string $userPublicId): void
    {
        $user = $this->providerUser($userPublicId) ?: $ticket->assignee;

        $user?->notify(new TicketUpdatedNotification($ticket, 'automation.notification'));
    }

    private function providerUser(?string $publicId): ?User
    {
        if (! $publicId) {
            return null;
        }

        return User::query()
            ->where('public_id', $publicId)
            ->whereHas('company', fn (Builder $query) => $query->where('type', 'provider'))
            ->first();
    }
}
