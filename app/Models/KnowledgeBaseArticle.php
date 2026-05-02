<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBaseArticle extends Model
{
    use HasFactory, HasPublicId;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_INTERNAL = 'internal';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'public_id',
        'knowledge_base_category_id',
        'author_user_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'visibility',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => app(HtmlSanitizer::class)->sanitize($value),
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'knowledge_base_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleVersion::class)->latest('version');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleFeedback::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeVisibleToPortal(Builder $query): Builder
    {
        return $query->public()->published();
    }

    public function scopeInternalOrPublic(Builder $query): Builder
    {
        return $query->whereIn('visibility', [self::VISIBILITY_PUBLIC, self::VISIBILITY_INTERNAL]);
    }
}
