<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'public_id',
        'type',
        'name',
        'slug',
        'status',
        'timezone',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'type' => CompanyType::class,
            'status' => CompanyStatus::class,
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function supportProjects(): HasMany
    {
        return $this->hasMany(SupportProject::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function apiClients(): HasMany
    {
        return $this->hasMany(ApiClient::class);
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function slaPolicies(): HasMany
    {
        return $this->hasMany(CompanySlaPolicy::class);
    }

    public function isProvider(): bool
    {
        return $this->type === CompanyType::Provider;
    }

    public function isClient(): bool
    {
        return $this->type === CompanyType::Client;
    }

    public function scopeClients(Builder $query): Builder
    {
        return $query->where('type', CompanyType::Client->value);
    }

    public function scopeProvider(Builder $query): Builder
    {
        return $query->where('type', CompanyType::Provider->value);
    }
}
