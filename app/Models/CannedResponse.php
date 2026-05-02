<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CannedResponse extends Model
{
    use HasFactory, HasPublicId;

    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_PERSONAL = 'personal';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'public_id',
        'user_id',
        'scope',
        'title',
        'shortcut',
        'body',
        'variables',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => app(HtmlSanitizer::class)->sanitize($value),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $scope) use ($user): void {
                $scope->where('scope', self::SCOPE_GLOBAL)
                    ->orWhere(function (Builder $personal) use ($user): void {
                        $personal->where('scope', self::SCOPE_PERSONAL)
                            ->where('user_id', $user->id);
                    });
            });
    }
}
