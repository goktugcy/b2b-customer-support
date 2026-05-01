<?php

namespace App\Models;

use App\Enums\WebhookEndpointStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoint extends Model
{
    use BelongsToCompany, HasFactory, HasPublicId;

    protected $fillable = [
        'public_id',
        'company_id',
        'url',
        'secret',
        'events',
        'status',
        'failure_count',
        'last_success_at',
        'last_failure_at',
    ];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'events' => 'array',
            'status' => WebhookEndpointStatus::class,
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function isActive(): bool
    {
        return $this->status === WebhookEndpointStatus::Active;
    }
}
