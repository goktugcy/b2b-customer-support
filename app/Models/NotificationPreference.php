<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'database_enabled',
        'mail_enabled',
        'digest_enabled',
        'event_settings',
    ];

    protected function casts(): array
    {
        return [
            'database_enabled' => 'boolean',
            'mail_enabled' => 'boolean',
            'digest_enabled' => 'boolean',
            'event_settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
