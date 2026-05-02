<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $event,
        public ?User $subjectUser = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticketUrl = $notifiable instanceof User && $notifiable->isProviderUser()
            ? route('admin.tickets.show', $this->ticket->adminRouteParameters())
            : route('portal.tickets.show', $this->ticket->portalRouteParameters());

        return (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hello '.$notifiable->name)
            ->line($this->line())
            ->line('Ticket: '.$this->ticket->subject)
            ->action('Open ticket', $ticketUrl);
    }

    private function subject(): string
    {
        return match ($this->event) {
            'ticket.created' => 'New ticket: '.$this->ticket->subject,
            'ticket.watcher_added' => 'Ticket watcher added: '.$this->ticket->subject,
            'ticket.assigned' => 'Ticket assigned: '.$this->ticket->subject,
            'ticket.comment.created' => 'Ticket reply added: '.$this->ticket->subject,
            default => 'Ticket updated: '.$this->ticket->subject,
        };
    }

    private function line(): string
    {
        return match ($this->event) {
            'ticket.created' => 'A new ticket has been created and targeted to your team.',
            'ticket.watcher_added' => $this->subjectUser
                ? $this->subjectUser->name.' was added as a watcher.'
                : 'A watcher was added to this ticket.',
            'ticket.assigned' => 'This ticket assignment changed.',
            'ticket.comment.created' => 'A new public reply was added.',
            default => 'This ticket was updated.',
        };
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'ticket_id' => $this->ticket->public_id,
            'ticket_number' => $this->ticket->ticket_number,
            'display_id' => $this->ticket->displayId(),
            'ticket_subject' => $this->ticket->subject,
            'subject_user' => $this->subjectUser?->name,
            'message' => $this->line(),
            'url' => $notifiable instanceof User && $notifiable->isProviderUser()
                ? route('admin.tickets.show', $this->ticket->adminRouteParameters())
                : route('portal.tickets.show', $this->ticket->portalRouteParameters()),
        ];
    }
}
