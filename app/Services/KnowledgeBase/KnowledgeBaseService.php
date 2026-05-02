<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

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
            ->withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function adminArticles(?string $search = null): Collection
    {
        return KnowledgeBaseArticle::query()
            ->with(['category', 'author'])
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
        return KnowledgeBaseArticle::create([
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
    }

    public function updateArticle(KnowledgeBaseArticle $article, array $data, User $author): KnowledgeBaseArticle
    {
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

        return $article->refresh();
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
}
