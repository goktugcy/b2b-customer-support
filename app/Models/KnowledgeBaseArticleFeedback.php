<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseArticleFeedback extends Model
{
    use HasFactory;

    protected $table = 'knowledge_base_article_feedback';

    protected $fillable = [
        'knowledge_base_article_id',
        'company_id',
        'user_id',
        'helpful',
        'comment',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'helpful' => 'boolean',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'knowledge_base_article_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
