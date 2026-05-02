<?php

namespace App\Services\Tickets;

use App\Enums\TicketVisibility;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketCommentMention;
use App\Models\User;
use App\Notifications\MentionedInTicketNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MentionParserService
{
    /**
     * @param  list<string>  $mentionedUserPublicIds
     */
    public function createMentions(TicketComment $comment, User $actor, array $mentionedUserPublicIds): void
    {
        $ids = collect($mentionedUserPublicIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $ticket = $comment->ticket()->firstOrFail();
        $eligibleUsers = $this->eligibleUsers($ticket, $comment->visibility, $actor)
            ->whereIn('public_id', $ids)
            ->whereKeyNot($actor->id)
            ->get();

        DB::afterCommit(function () use ($eligibleUsers, $comment, $actor): void {
            foreach ($eligibleUsers as $user) {
                $mention = TicketCommentMention::query()->firstOrCreate([
                    'ticket_comment_id' => $comment->id,
                    'mentioned_user_id' => $user->id,
                ], [
                    'mentioned_by_user_id' => $actor->id,
                ]);

                $user->notify(new MentionedInTicketNotification($comment->fresh(['ticket']), $actor));
                $mention->forceFill(['notified_at' => now()])->save();
            }
        });
    }

    public function mentionableUsers(Ticket $ticket, TicketVisibility $visibility): Collection
    {
        return $this->eligibleUsers($ticket, $visibility)
            ->orderBy('name')
            ->get(['id', 'public_id', 'name', 'company_id'])
            ->map(fn (User $user): array => [
                'id' => $user->public_id,
                'name' => $user->name,
                'side' => $user->company_id === $ticket->company_id ? 'client' : 'provider',
            ]);
    }

    private function eligibleUsers(Ticket $ticket, TicketVisibility $visibility, ?User $actor = null): Builder
    {
        return User::query()
            ->where('status', 'active')
            ->where(function (Builder $query) use ($ticket, $visibility): void {
                if ($visibility === TicketVisibility::Internal) {
                    $query->whereHas('company', fn (Builder $company) => $company->provider());

                    return;
                }

                $query->where('company_id', $ticket->company_id)
                    ->orWhereHas('company', fn (Builder $company) => $company->provider());
            })
            ->when($actor, fn (Builder $query) => $query->whereKeyNot($actor->id));
    }
}
