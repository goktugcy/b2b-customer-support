<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCsatSurvey extends Model
{
    use BelongsToCompany, HasFactory, HasPublicId;

    protected $fillable = [
        'public_id',
        'company_id',
        'ticket_id',
        'requester_user_id',
        'sent_by_user_id',
        'token_hash',
        'rating',
        'comment',
        'sent_at',
        'responded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function hasResponse(): bool
    {
        return $this->responded_at !== null;
    }
}
