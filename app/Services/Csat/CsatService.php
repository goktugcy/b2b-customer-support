<?php

namespace App\Services\Csat;

use App\Models\Ticket;
use App\Models\TicketCsatSurvey;
use App\Models\User;
use App\Notifications\CsatSurveyNotification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CsatService
{
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

        return $survey->refresh();
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
