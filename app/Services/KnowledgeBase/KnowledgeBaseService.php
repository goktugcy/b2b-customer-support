<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseArticleFeedback;
use App\Models\KnowledgeBaseArticleVersion;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KnowledgeBaseService
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /**
     * @return Collection<int, KnowledgeBaseCategory>
     */
    public function categoriesForPortal(): Collection
    {
        return KnowledgeBaseCategory::query()
            ->visibleToPortal()
            ->with('parent')
            ->withCount(['articles' => fn (Builder $query) => $query->visibleToPortal()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function articlesForPortal(?string $search = null, ?string $categorySlug = null): Collection
    {
        return KnowledgeBaseArticle::query()
            ->visibleToPortal()
            ->with('category')
            ->when($categorySlug, fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->where('slug', $categorySlug)))
            ->when($search, fn (Builder $query) => $this->applyArticleSearch($query, $search))
            ->orderByDesc('published_at')
            ->orderBy('title')
            ->get();
    }

    public function adminCategories(): Collection
    {
        return KnowledgeBaseCategory::query()
            ->with('parent')
            ->withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function adminArticles(?string $search = null): Collection
    {
        return KnowledgeBaseArticle::query()
            ->with(['category', 'author', 'versions.editor'])
            ->withCount([
                'versions',
                'feedback',
                'feedback as helpful_count' => fn (Builder $query) => $query->where('helpful', true),
                'feedback as not_helpful_count' => fn (Builder $query) => $query->where('helpful', false),
            ])
            ->when($search, fn (Builder $query) => $this->applyArticleSearch($query, $search))
            ->latest()
            ->get();
    }

    public function storeCategory(array $data): KnowledgeBaseCategory
    {
        return KnowledgeBaseCategory::create([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug(KnowledgeBaseCategory::class, $data['slug'] ?? $data['name']),
            'visibility' => $data['visibility'],
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function updateCategory(KnowledgeBaseCategory $category, array $data): KnowledgeBaseCategory
    {
        $category->update([
            'parent_id' => $this->validatedParentId(
                $category,
                array_key_exists('parent_id', $data) ? $data['parent_id'] : $category->parent_id,
            ),
            'name' => $data['name'] ?? $category->name,
            'slug' => array_key_exists('slug', $data)
                ? $this->uniqueSlug(KnowledgeBaseCategory::class, $data['slug'] ?: $category->name, $category->id)
                : $category->slug,
            'visibility' => $data['visibility'] ?? $category->visibility,
            'status' => $data['status'] ?? $category->status,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        return $category->refresh();
    }

    public function storeArticle(array $data, User $author): KnowledgeBaseArticle
    {
        return DB::transaction(function () use ($data, $author): KnowledgeBaseArticle {
            $article = KnowledgeBaseArticle::create([
                'knowledge_base_category_id' => $data['knowledge_base_category_id'] ?? null,
                'author_user_id' => $author->id,
                'title' => $data['title'],
                'slug' => $this->uniqueSlug(KnowledgeBaseArticle::class, $data['slug'] ?? $data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $this->sanitizer->sanitize($data['body']),
                'visibility' => $data['visibility'],
                'status' => $data['status'],
                'published_at' => $data['status'] === KnowledgeBaseArticle::STATUS_PUBLISHED ? now() : null,
            ]);

            $this->recordVersion($article, $author);

            return $article;
        });
    }

    public function updateArticle(KnowledgeBaseArticle $article, array $data, User $author): KnowledgeBaseArticle
    {
        return DB::transaction(function () use ($article, $data, $author): KnowledgeBaseArticle {
            $status = $data['status'] ?? $article->status;

            $article->update([
                'knowledge_base_category_id' => array_key_exists('knowledge_base_category_id', $data)
                    ? $data['knowledge_base_category_id']
                    : $article->knowledge_base_category_id,
                'author_user_id' => $article->author_user_id ?? $author->id,
                'title' => $data['title'] ?? $article->title,
                'slug' => array_key_exists('slug', $data)
                    ? $this->uniqueSlug(KnowledgeBaseArticle::class, $data['slug'] ?: ($data['title'] ?? $article->title), $article->id)
                    : $article->slug,
                'excerpt' => array_key_exists('excerpt', $data) ? $data['excerpt'] : $article->excerpt,
                'body' => array_key_exists('body', $data) ? $this->sanitizer->sanitize($data['body']) : $article->body,
                'visibility' => $data['visibility'] ?? $article->visibility,
                'status' => $status,
                'published_at' => $status === KnowledgeBaseArticle::STATUS_PUBLISHED
                    ? ($article->published_at ?? now())
                    : null,
            ]);

            $this->recordVersion($article->refresh(), $author);

            return $article->refresh();
        });
    }

    public function deleteCategory(KnowledgeBaseCategory $category): void
    {
        $category->delete();
    }

    public function deleteArticle(KnowledgeBaseArticle $article): void
    {
        $article->delete();
    }

    public function storeFeedback(KnowledgeBaseArticle $article, User $user, bool $helpful, ?string $comment, Request $request): KnowledgeBaseArticleFeedback
    {
        return $article->feedback()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'helpful' => $helpful,
            'comment' => $comment,
            'ip_hash' => hash_hmac('sha256', $request->ip() ?? 'unknown', config('app.key')),
        ]);
    }

    private function applyArticleSearch(Builder $query, string $search): Builder
    {
        $term = '%'.$search.'%';

        return $query->where(function (Builder $scope) use ($term): void {
            $scope->where('title', 'like', $term)
                ->orWhere('excerpt', 'like', $term)
                ->orWhere('body', 'like', $term);
        });
    }

    private function uniqueSlug(string $modelClass, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $counter = 2;

        while ($modelClass::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function recordVersion(KnowledgeBaseArticle $article, User $editor): KnowledgeBaseArticleVersion
    {
        $nextVersion = ((int) $article->versions()->max('version')) + 1;

        return $article->versions()->create([
            'editor_user_id' => $editor->id,
            'version' => $nextVersion,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'visibility' => $article->visibility,
            'status' => $article->status,
            'published_at' => $article->published_at,
        ]);
    }

    private function validatedParentId(KnowledgeBaseCategory $category, ?int $parentId): ?int
    {
        if (! $parentId) {
            return null;
        }

        if ($parentId === $category->id) {
            throw ValidationException::withMessages(['parent_id' => 'A category cannot be its own parent.']);
        }

        return $parentId;
    }
}
