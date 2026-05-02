<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRuleExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_rule_id',
        'company_id',
        'ticket_id',
        'trigger',
        'status',
        'context',
        'actions',
        'error_message',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'actions' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
