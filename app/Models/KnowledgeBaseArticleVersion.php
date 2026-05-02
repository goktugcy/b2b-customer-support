<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseArticleVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_base_article_id',
        'editor_user_id',
        'version',
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
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'knowledge_base_article_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_user_id');
    }
}
