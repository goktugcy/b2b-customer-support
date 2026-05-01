<?php

namespace App\Jobs;

use App\Enums\AttachmentScanStatus;
use App\Models\TicketAttachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAttachment implements ShouldQueue
{
    use Queueable;

    public function __construct(public TicketAttachment $attachment) {}

    public function handle(): void
    {
        $this->attachment->forceFill([
            'scan_status' => AttachmentScanStatus::Clean,
        ])->save();
    }
}
