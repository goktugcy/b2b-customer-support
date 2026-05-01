<?php

namespace App\Models;

use App\Enums\TicketWatcherSide;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketWatcher extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'side',
        'added_by_user_id',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'side' => TicketWatcherSide::class,
            'notified_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}
