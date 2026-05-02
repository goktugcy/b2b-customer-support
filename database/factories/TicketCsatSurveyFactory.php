<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketCsatSurvey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketCsatSurvey>
 */
class TicketCsatSurveyFactory extends Factory
{
    public function definition(): array
    {
        $ticket = Ticket::factory()->create();

        return [
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'requester_user_id' => $ticket->requester_user_id ?? User::factory()->create(['company_id' => $ticket->company_id])->id,
            'sent_by_user_id' => null,
            'token_hash' => hash('sha256', fake()->unique()->sha256()),
            'rating' => null,
            'comment' => null,
            'sent_at' => now(),
            'responded_at' => null,
            'expires_at' => now()->addDays(14),
        ];
    }
}
