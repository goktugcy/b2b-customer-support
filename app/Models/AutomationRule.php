<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'public_id',
        'company_id',
        'name',
        'trigger',
        'conditions',
        'actions',
        'enabled',
        'priority',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'enabled' => 'boolean',
            'priority' => 'integer',
            'last_run_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AutomationRuleExecution::class);
    }
}
