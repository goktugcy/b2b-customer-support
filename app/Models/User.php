<?php

namespace App\Models;

use App\Enums\CompanyType;
use App\Enums\UserStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasPublicId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToCompany, HasFactory, HasPublicId, HasRoles, Notifiable;

    protected $fillable = [
        'public_id',
        'company_id',
        'name',
        'email',
        'email_verified_at',
        'password',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
        ];
    }

    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by_user_id');
    }

    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_user_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to_user_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by_user_id');
    }

    public function supportDepartments(): BelongsToMany
    {
        return $this->belongsToMany(SupportDepartment::class, 'support_department_user')
            ->withTimestamps();
    }

    public function watchedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_watchers')
            ->withPivot(['side', 'added_by_user_id', 'notified_at'])
            ->withTimestamps();
    }

    public function cannedResponses(): HasMany
    {
        return $this->hasMany(CannedResponse::class);
    }

    public function ticketSavedViews(): HasMany
    {
        return $this->hasMany(TicketSavedView::class);
    }

    public function isProviderUser(): bool
    {
        return $this->company?->type === CompanyType::Provider;
    }

    public function isCustomerUser(): bool
    {
        return $this->company?->type === CompanyType::Client;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
