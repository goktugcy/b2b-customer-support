<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySlaPolicy extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'priority',
        'first_response_minutes',
        'resolution_minutes',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'enabled' => 'boolean',
        ];
    }
}
