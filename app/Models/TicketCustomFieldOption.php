<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCustomFieldOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_custom_field_id',
        'value',
        'label',
        'sort_order',
    ];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(TicketCustomField::class, 'ticket_custom_field_id');
    }
}
