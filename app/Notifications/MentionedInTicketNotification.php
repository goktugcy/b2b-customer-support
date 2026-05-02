<?php

namespace App\Notifications;

use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionedInTicketNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TicketComment $comment,
        public User $mentionedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->comment->ticket;
        $ticketUrl = $notifiable instanceof User && $notifiable->isProviderUser()
            ? route('admin.tickets.show', $ticket)
            : route('portal.tickets.show', $ticket);

        return (new MailMessage)
            ->subject('You were mentioned: '.$ticket->subject)
            ->greeting('Hello '.$notifiable->name)
            ->line($this->mentionedBy->name.' mentioned you on a ticket.')
            ->line('Ticket: '.$ticket->subject)
            ->action('Open ticket', $ticketUrl);
    }

    public function toDatabase(object $notifiable): array
    {
        $ticket = $this->comment->ticket;

        return [
            'event' => 'ticket.mention.created',
            'ticket_id' => $ticket->public_id,
            'ticket_subject' => $ticket->subject,
            'comment_id' => $this->comment->public_id,
            'mentioned_by' => $this->mentionedBy->name,
            'message' => $this->mentionedBy->name.' mentioned you on a ticket.',
            'url' => $notifiable instanceof User && $notifiable->isProviderUser()
                ? route('admin.tickets.show', $ticket)
                : route('portal.tickets.show', $ticket),
        ];
    }
}
