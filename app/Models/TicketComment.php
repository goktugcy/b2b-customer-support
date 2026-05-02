<?php

namespace App\Models;

use App\Enums\TicketVisibility;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasPublicId;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketComment extends Model
{
    use BelongsToCompany, HasFactory, HasPublicId, SoftDeletes;

    protected $fillable = [
        'public_id',
        'company_id',
        'ticket_id',
        'user_id',
        'api_client_id',
        'visibility',
        'body',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => TicketVisibility::class,
            'edited_at' => 'datetime',
        ];
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => app(HtmlSanitizer::class)->sanitize($value),
        );
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'comment_id');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(TicketCommentMention::class);
    }

    public function scopeVisibleTo(Builder $query, User|ApiClient $actor): Builder
    {
        if ($actor instanceof ApiClient || ! $actor->isProviderUser()) {
            return $query->where('visibility', TicketVisibility::Public->value);
        }

        return $query;
    }
}
