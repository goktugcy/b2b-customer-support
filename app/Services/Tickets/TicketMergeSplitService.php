<?php

namespace App\Services\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketMergeSplitService
{
    public function __construct(
        private readonly TicketEventRecorder $events,
        private readonly AuditLogger $audit,
    ) {}

    public function merge(Ticket $source, Ticket $target, User $actor, ?Request $request = null): Ticket
    {
        if (! $actor->isProviderUser() || ! $actor->can('tickets.merge')) {
            throw new AuthorizationException('You are not allowed to merge tickets.');
        }

        if ($source->is($target)) {
            throw ValidationException::withMessages(['target_ticket_id' => 'A ticket cannot be merged into itself.']);
        }

        if ($source->company_id !== $target->company_id) {
            throw ValidationException::withMessages(['target_ticket_id' => 'Tickets must belong to the same company.']);
        }

        if ($source->status === TicketStatus::Merged) {
            throw ValidationException::withMessages(['ticket' => 'This ticket has already been merged.']);
        }

        return DB::transaction(function () use ($source, $target, $actor, $request): Ticket {
            $previousStatus = $source->status?->value;

            $source->comments()->update(['ticket_id' => $target->id]);
            $source->attachments()->update(['ticket_id' => $target->id]);
            $source->events()->update(['ticket_id' => $target->id]);

            foreach ($source->watchers()->get() as $watcher) {
                $target->watchers()->firstOrCreate([
                    'user_id' => $watcher->user_id,
                ], [
                    'side' => $watcher->side,
                    'added_by_user_id' => $actor->id,
                    'notified_at' => $watcher->notified_at,
                ]);
            }

            $source->watchers()->delete();

            $source->forceFill([
                'merged_into_ticket_id' => $target->id,
                'merged_at' => now(),
                'merged_by_user_id' => $actor->id,
                'status' => TicketStatus::Merged,
                'closed_at' => now(),
            ])->save();

            $this->events->record(
                ticket: $source,
                eventType: 'ticket.merged',
                actor: $actor,
                oldValues: ['status' => $previousStatus],
                newValues: [
                    'status' => TicketStatus::Merged->value,
                    'merged_into_ticket_id' => $target->public_id,
                ],
                request: $request,
            );

            $this->events->record(
                ticket: $target,
                eventType: 'ticket.merge_received',
                actor: $actor,
                newValues: ['source_ticket_id' => $source->public_id],
                request: $request,
            );

            $this->audit->log('ticket.merged', $source, $actor, after: [
                'merged_into_ticket_id' => $target->public_id,
            ], request: $request);

            return $target->refresh();
        });
    }

    /**
     * @param  list<string>  $commentPublicIds
     */
    public function split(Ticket $source, User $actor, string $subject, array $commentPublicIds, ?Request $request = null): Ticket
    {
        if (! $actor->isProviderUser() || ! $actor->can('tickets.split')) {
            throw new AuthorizationException('You are not allowed to split tickets.');
        }

        $comments = TicketComment::query()
            ->where('ticket_id', $source->id)
            ->whereIn('public_id', $commentPublicIds)
            ->get();

        if ($comments->isEmpty()) {
            throw ValidationException::withMessages(['comment_ids' => 'Select at least one comment to split.']);
        }

        return DB::transaction(function () use ($source, $actor, $subject, $comments, $request): Ticket {
            $newTicket = Ticket::create([
                'company_id' => $source->company_id,
                'support_project_id' => $source->support_project_id,
                'ticket_tracker_id' => $source->ticket_tracker_id,
                'ticket_category_id' => $source->ticket_category_id,
                'created_by_user_id' => $actor->id,
                'requester_user_id' => $source->requester_user_id,
                'assigned_to_user_id' => $source->assigned_to_user_id,
                'split_from_ticket_id' => $source->id,
                'subject' => $subject,
                'description' => 'Split from ticket '.$source->public_id,
                'status' => TicketStatus::Open,
                'priority' => $source->priority,
                'source' => $source->source,
                'last_agent_activity_at' => now(),
            ]);

            $newTicket->tags()->sync($source->tags()->pluck('ticket_tags.id')->all());

            $commentIds = $comments->pluck('id')->all();
            TicketComment::query()->whereKey($commentIds)->update(['ticket_id' => $newTicket->id]);
            $source->attachments()->whereIn('comment_id', $commentIds)->update(['ticket_id' => $newTicket->id]);

            foreach ($source->watchers()->get() as $watcher) {
                $newTicket->watchers()->firstOrCreate([
                    'user_id' => $watcher->user_id,
                ], [
                    'side' => $watcher->side,
                    'added_by_user_id' => $actor->id,
                    'notified_at' => $watcher->notified_at,
                ]);
            }

            $this->events->record(
                ticket: $source,
                eventType: 'ticket.split',
                actor: $actor,
                newValues: ['split_ticket_id' => $newTicket->public_id],
                request: $request,
            );

            $this->events->record(
                ticket: $newTicket,
                eventType: 'ticket.split_created',
                actor: $actor,
                newValues: [
                    'source_ticket_id' => $source->public_id,
                    'comment_ids' => $comments->pluck('public_id')->values()->all(),
                ],
                request: $request,
            );

            $this->audit->log('ticket.split', $source, $actor, after: [
                'split_ticket_id' => $newTicket->public_id,
            ], request: $request);

            return $newTicket->refresh();
        });
    }
}
