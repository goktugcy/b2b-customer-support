<?php

namespace App\Services\Csat;

use App\Models\Ticket;
use App\Models\TicketCsatSurvey;
use App\Models\User;
use App\Notifications\CsatSurveyNotification;
use App\Services\Tickets\TicketEventRecorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CsatService
{
    public function __construct(private readonly TicketEventRecorder $events) {}

    public function sendForTicket(Ticket $ticket, ?User $actor = null): ?TicketCsatSurvey
    {
        $ticket->loadMissing('requester');

        if (! $ticket->requester) {
            return null;
        }

        $existing = TicketCsatSurvey::query()
            ->where('ticket_id', $ticket->id)
            ->where('requester_user_id', $ticket->requester->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $plainToken = Str::random(64);

        $survey = TicketCsatSurvey::create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'requester_user_id' => $ticket->requester->id,
            'sent_by_user_id' => $actor?->id,
            'token_hash' => $this->hash($plainToken),
            'sent_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);

        $ticket->requester->notify(new CsatSurveyNotification($survey, $plainToken));

        return $survey;
    }

    public function resendForTicket(Ticket $ticket, User $actor): ?TicketCsatSurvey
    {
        $ticket->loadMissing('requester');

        if (! $ticket->requester) {
            return null;
        }

        $survey = TicketCsatSurvey::query()
            ->where('ticket_id', $ticket->id)
            ->where('requester_user_id', $ticket->requester->id)
            ->first();

        if ($survey?->responded_at) {
            throw ValidationException::withMessages(['survey' => 'Answered CSAT surveys cannot be resent.']);
        }

        $plainToken = Str::random(64);

        if ($survey) {
            $survey->update([
                'sent_by_user_id' => $actor->id,
                'token_hash' => $this->hash($plainToken),
                'sent_at' => now(),
                'expires_at' => now()->addDays(14),
            ]);
        } else {
            $survey = TicketCsatSurvey::create([
                'company_id' => $ticket->company_id,
                'ticket_id' => $ticket->id,
                'requester_user_id' => $ticket->requester->id,
                'sent_by_user_id' => $actor->id,
                'token_hash' => $this->hash($plainToken),
                'sent_at' => now(),
                'expires_at' => now()->addDays(14),
            ]);
        }

        $ticket->requester->notify(new CsatSurveyNotification($survey, $plainToken));

        return $survey->refresh();
    }

    public function submit(string $token, int $rating, ?string $comment = null): TicketCsatSurvey
    {
        $survey = TicketCsatSurvey::query()
            ->where('token_hash', $this->hash($token))
            ->with('ticket')
            ->first();

        if (! $survey || $survey->responded_at) {
            throw ValidationException::withMessages(['token' => 'This CSAT survey is no longer available.']);
        }

        if ($survey->expires_at->isPast()) {
            throw ValidationException::withMessages(['token' => 'This CSAT survey has expired.']);
        }

        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages(['rating' => 'Rating must be between 1 and 5.']);
        }

        $survey->update([
            'rating' => $rating,
            'comment' => $comment,
            'responded_at' => now(),
        ]);

        if ($survey->ticket) {
            $this->events->record(
                ticket: $survey->ticket,
                eventType: 'csat.submitted',
                newValues: [
                    'survey_id' => $survey->public_id,
                    'rating' => $rating,
                ],
            );
        }

        return $survey->refresh();
    }

    public function summaryFor(User $actor): array
    {
        $base = TicketCsatSurvey::query()
            ->whereHas('ticket', fn (Builder $query) => $query->visibleTo($actor));

        $sent = (clone $base)->count();
        $responded = (clone $base)->whereNotNull('responded_at')->count();
        $average = (clone $base)->whereNotNull('responded_at')->avg('rating');
        $lowScores = (clone $base)
            ->whereNotNull('responded_at')
            ->where('rating', '<=', 2)
            ->with('ticket')
            ->latest('responded_at')
            ->limit(5)
            ->get()
            ->map(fn (TicketCsatSurvey $survey): array => [
                'id' => $survey->public_id,
                'rating' => $survey->rating,
                'comment' => $survey->comment,
                'ticket' => $survey->ticket?->displayId(),
                'subject' => $survey->ticket?->subject,
                'responded_at' => $survey->responded_at?->toISOString(),
            ])
            ->all();

        return [
            'average' => $average ? round((float) $average, 2) : null,
            'sent' => $sent,
            'responded' => $responded,
            'response_rate' => $sent > 0 ? round(($responded / $sent) * 100, 1) : 0,
            'low_scores' => $lowScores,
        ];
    }

    public function findOpenByToken(string $token): ?TicketCsatSurvey
    {
        return TicketCsatSurvey::query()
            ->where('token_hash', $this->hash($token))
            ->whereNull('responded_at')
            ->where('expires_at', '>', now())
            ->with('ticket')
            ->first();
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
