<?php

namespace App\Jobs;

use App\Models\Invitation;
use App\Notifications\InvitationCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendInvitationEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invitation $invitation,
        public string $token,
    ) {}

    public function handle(): void
    {
        Notification::route('mail', $this->invitation->email)
            ->notify(new InvitationCreatedNotification($this->invitation, $this->token));
    }
}
