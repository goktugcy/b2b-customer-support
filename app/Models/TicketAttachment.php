<?php

namespace App\Models;

use App\Enums\AttachmentScanStatus;
use App\Enums\TicketVisibility;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'ticket_id',
        'comment_id',
        'uploaded_by_user_id',
        'api_client_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'visibility',
        'scan_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => TicketVisibility::class,
            'scan_status' => AttachmentScanStatus::class,
            'metadata' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }
}
