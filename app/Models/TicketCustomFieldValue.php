<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'ticket_custom_field_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(TicketCustomField::class, 'ticket_custom_field_id');
    }
}
