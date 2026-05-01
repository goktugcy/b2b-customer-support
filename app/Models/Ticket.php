<?php

namespace App\Models;

use App\Enums\CompanyType;
use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use BelongsToCompany, HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'public_id',
        'company_id',
        'created_by_user_id',
        'requester_user_id',
        'api_client_id',
        'assigned_to_user_id',
        'subject',
        'description',
        'status',
        'priority',
        'source',
        'first_response_due_at',
        'due_at',
        'resolved_at',
        'closed_at',
        'last_customer_activity_at',
        'last_agent_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'source' => TicketSource::class,
            'first_response_due_at' => 'datetime',
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_customer_activity_at' => 'datetime',
            'last_agent_activity_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class);
    }

    public function scopeVisibleTo(Builder $query, User|ApiClient $actor): Builder
    {
        if ($actor instanceof ApiClient) {
            return $query->forCompany($actor->company_id);
        }

        if ($actor->company?->type === CompanyType::Provider && $actor->can('tickets.view_any')) {
            return $query;
        }

        return $query->forCompany($actor->company_id);
    }
}
