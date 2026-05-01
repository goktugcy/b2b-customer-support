<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCustomField extends Model
{
    use HasFactory, HasPublicId;

    public const TYPES = [
        'text',
        'textarea',
        'number',
        'date',
        'boolean',
        'single_select',
        'multi_select',
        'user',
        'project',
        'category',
    ];

    protected $fillable = [
        'public_id',
        'ticket_tracker_id',
        'name',
        'slug',
        'type',
        'is_required',
        'validation_regex',
        'settings',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'settings' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(TicketTracker::class, 'ticket_tracker_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(TicketCustomFieldOption::class)->orderBy('sort_order')->orderBy('label');
    }

    public function values(): HasMany
    {
        return $this->hasMany(TicketCustomFieldValue::class);
    }
}
