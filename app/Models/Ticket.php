<?php

namespace App\Models;

use App\Enums\CompanyType;
use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasPublicId;
use App\Services\Content\HtmlSanitizer;
use App\Services\Tickets\TicketNumberService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use BelongsToCompany, HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'public_id',
        'ticket_number',
        'company_id',
        'support_project_id',
        'ticket_tracker_id',
        'ticket_category_id',
        'created_by_user_id',
        'requester_user_id',
        'api_client_id',
        'assigned_to_user_id',
        'merged_into_ticket_id',
        'merged_at',
        'merged_by_user_id',
        'split_from_ticket_id',
        'subject',
        'description',
        'status',
        'priority',
        'source',
        'first_response_due_at',
        'due_at',
        'first_responded_at',
        'sla_first_response_breached_at',
        'sla_resolution_breached_at',
        'sla_policy_snapshot',
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
            'ticket_number' => 'integer',
            'merged_at' => 'datetime',
            'first_response_due_at' => 'datetime',
            'due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'sla_first_response_breached_at' => 'datetime',
            'sla_resolution_breached_at' => 'datetime',
            'sla_policy_snapshot' => 'array',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_customer_activity_at' => 'datetime',
            'last_agent_activity_at' => 'datetime',
        ];
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => app(HtmlSanitizer::class)->sanitize($value),
        );
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (! $ticket->ticket_number && $ticket->company_id) {
                $ticket->ticket_number = app(TicketNumberService::class)->nextForCompany((int) $ticket->company_id);
            }
        });
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        if ($field === 'ticket_number') {
            $query = static::query()->where('ticket_number', $value);
            $company = request()->route('company');
            $user = request()->user();

            if ($company instanceof Company) {
                $query->where('company_id', $company->id);
            } elseif ($user instanceof User && $user->isCustomerUser()) {
                $query->where('company_id', $user->company_id);
            }

            return $query->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function displayId(): string
    {
        return '#'.$this->ticket_number;
    }

    public function adminRouteParameters(): array
    {
        $this->loadMissing('company');

        return [
            'company' => $this->company?->slug,
            'ticket' => $this->ticket_number,
        ];
    }

    public function portalRouteParameters(): array
    {
        return ['ticket' => $this->ticket_number];
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

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_ticket_id');
    }

    public function mergedTickets(): HasMany
    {
        return $this->hasMany(self::class, 'merged_into_ticket_id');
    }

    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by_user_id');
    }

    public function splitFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'split_from_ticket_id');
    }

    public function splitTickets(): HasMany
    {
        return $this->hasMany(self::class, 'split_from_ticket_id');
    }

    public function supportProject(): BelongsTo
    {
        return $this->belongsTo(SupportProject::class);
    }

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(TicketTracker::class, 'ticket_tracker_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function targetDepartments(): BelongsToMany
    {
        return $this->belongsToMany(SupportDepartment::class, 'ticket_target_departments')
            ->withPivot('added_by_user_id')
            ->withTimestamps();
    }

    public function targetUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_target_users')
            ->withPivot('added_by_user_id')
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TicketTag::class, 'ticket_tag')->withTimestamps();
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(TicketCustomFieldValue::class);
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TicketWatcher::class);
    }

    public function watcherUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_watchers')
            ->withPivot(['side', 'added_by_user_id', 'notified_at'])
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class);
    }

    public function csatSurveys(): HasMany
    {
        return $this->hasMany(TicketCsatSurvey::class);
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
