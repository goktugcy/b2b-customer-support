<?php

namespace App\Notifications;

use App\Models\TicketCsatSurvey;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CsatSurveyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TicketCsatSurvey $survey,
        public string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->survey->ticket;

        return (new MailMessage)
            ->subject('How was your support experience?')
            ->greeting('Hello '.$notifiable->name)
            ->line('Your ticket has been resolved.')
            ->line('Ticket: '.$ticket->subject)
            ->action('Rate support', route('csat.show', $this->token));
    }

    public function toDatabase(object $notifiable): array
    {
        $ticket = $this->survey->ticket;

        return [
            'event' => 'ticket.csat.sent',
            'ticket_id' => $ticket->public_id,
            'ticket_subject' => $ticket->subject,
            'survey_id' => $this->survey->public_id,
            'message' => 'Your ticket was resolved. Please rate your support experience.',
            'url' => route('csat.show', $this->token),
        ];
    }
}
