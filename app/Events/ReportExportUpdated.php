<?php

namespace App\Events;

use App\Models\ReportExport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportExportUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ReportExport $export) {}

    public function broadcastOn(): array
    {
        return $this->export->requestedBy
            ? [new PrivateChannel('users.'.$this->export->requestedBy->public_id)]
            : [];
    }

    public function broadcastAs(): string
    {
        return 'report-export.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->export->public_id,
            'type' => $this->export->type,
            'format' => $this->export->format,
            'status' => $this->export->status,
            'error_message' => $this->export->error_message,
            'completed_at' => $this->export->completed_at?->toISOString(),
        ];
    }
}
