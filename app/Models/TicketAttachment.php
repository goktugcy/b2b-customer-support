<?php

namespace App\Models;

use App\Enums\TicketVisibility;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    use BelongsToCompany, HasFactory, HasPublicId;

    protected $fillable = [
        'public_id',
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
        'scan_status',
        'scan_result',
        'scanned_at',
        'visibility',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => TicketVisibility::class,
            'metadata' => 'array',
            'scanned_at' => 'datetime',
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
