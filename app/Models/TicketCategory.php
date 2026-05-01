<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'public_id',
        'support_project_id',
        'name',
        'slug',
        'description',
        'status',
    ];

    public function supportProject(): BelongsTo
    {
        return $this->belongsTo(SupportProject::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
