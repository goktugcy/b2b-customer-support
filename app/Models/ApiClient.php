<?php

namespace App\Models;

use App\Enums\ApiClientStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasPublicId;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class ApiClient extends Model implements AuthenticatableContract
{
    use Authenticatable, BelongsToCompany, HasApiTokens, HasFactory, HasPublicId;

    protected $fillable = [
        'public_id',
        'company_id',
        'name',
        'status',
        'created_by_user_id',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApiClientStatus::class,
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === ApiClientStatus::Active
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
