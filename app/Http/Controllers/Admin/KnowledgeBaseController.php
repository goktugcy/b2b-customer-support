<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request, KnowledgeBaseService $knowledgeBase): Response
    {
        abort_unless($request->user()->can('knowledge_base.manage'), 403);

        return Inertia::render('Admin/KnowledgeBase/Index', [
            'categories' => $knowledgeBase->adminCategories()->map(fn (KnowledgeBaseCategory $category): array => $this->categoryPayload($category)),
            'articles' => $knowledgeBase->adminArticles($request->string('search')->toString())->map(fn (KnowledgeBaseArticle $article): array => $this->articlePayload($article)),
            'filters' => $request->only(['search']),
            'visibilities' => ['public', 'internal'],
            'statuses' => ['draft', 'published', 'archived'],
        ]);
    }

    public function storeCategory(Request $request, KnowledgeBaseService $knowledgeBase): RedirectResponse
    {
        abort_unless($request->user()->can('knowledge_base.manage'), 403);

        $validated = $request->validate($this->categoryRules());
        $validated['parent_id'] = $this->categoryIdFromPublicId($validated['parent_id'] ?? null);

        $knowledgeBase->storeCategory($validated);

        return back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, KnowledgeBaseCategory $category, KnowledgeBaseService $knowledgeBase): RedirectResponse
    {
        abort_unless($request->user()->can('knowledge_base.manage'), 403);

        $validated = $request->validate($this->categoryRules(partial: true));

        if (array_key_exists('parent_id', $validated)) {
            $validated['parent_id'] = $this->categoryIdFromPublicId($validated['parent_id']);
        }

        $knowledgeBase->updateCategory($category, $validated);

        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(Request $request, KnowledgeBaseCategory $category, KnowledgeBaseService $knowledgeBase): RedirectResponse
    {
        abort_unless($request->user()->can('knowledge_base.manage'), 403);

        $knowledgeBase->deleteCategory($category);

        return back()->with('success', 'Category deleted.');
    }

    public function storeArticle(Request $request, KnowledgeBaseService $knowledgeBase): RedirectResponse
    {
        abort_unless($request->user()->can('knowledge_base.manage'), 403);

        $validated = $request->validate($this->articleRules());
        $validated['knowledge_base_category_id'] = $this->categoryIdFromPublicId($validated['category_id'] ?? null);

        $knowledgeBase->storeArticle($validated, $request->user());

        return back()->with('success', 'Article created.');
    }

    public function updateArticle(Request $request, KnowledgeBaseArticle $article, KnowledgeBaseService $knowledgeBase): RedirectResponse
    {
        abort_unless($request->user()->can('knowledge_base.manage'), 403);

        $validated = $request->validate($this->articleRules(partial: true));

        if (array_key_exists('category_id', $validated)) {
            $validated['knowledge_base_category_id'] = $this->categoryIdFromPublicId($validated['category_id']);
        }

        $knowledgeBase->updateArticle($article, $validated, $request->user());

        return back()->with('success', 'Article updated.');
    }

    public function destroyArticle(Request $request, KnowledgeBaseArticle $article, KnowledgeBaseService $knowledgeBase): RedirectResponse
    {
        abort_unless($request->user()->can('knowledge_base.manage'), 403);

        $knowledgeBase->deleteArticle($article);

        return back()->with('success', 'Article deleted.');
    }

    private function categoryRules(bool $partial = false): array
    {
        return [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'parent_id' => ['nullable', 'exists:knowledge_base_categories,public_id'],
            'slug' => ['nullable', 'string', 'max:180'],
            'visibility' => [$partial ? 'sometimes' : 'required', Rule::in(['public', 'internal'])],
            'status' => [$partial ? 'sometimes' : 'required', Rule::in(['draft', 'published', 'archived'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    private function articleRules(bool $partial = false): array
    {
        return [
            'category_id' => ['nullable', 'exists:knowledge_base_categories,public_id'],
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:240'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => [$partial ? 'sometimes' : 'required', 'string', 'max:100000'],
            'visibility' => [$partial ? 'sometimes' : 'required', Rule::in(['public', 'internal'])],
            'status' => [$partial ? 'sometimes' : 'required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    private function categoryIdFromPublicId(?string $publicId): ?int
    {
        return $publicId
            ? KnowledgeBaseCategory::query()->where('public_id', $publicId)->firstOrFail()->id
            : null;
    }

    private function categoryPayload(KnowledgeBaseCategory $category): array
    {
        return [
            'id' => $category->public_id,
            'parent_id' => $category->parent?->public_id,
            'parent' => $category->parent?->name,
            'name' => $category->name,
            'slug' => $category->slug,
            'visibility' => $category->visibility,
            'status' => $category->status,
            'sort_order' => $category->sort_order,
            'articles_count' => $category->articles_count ?? 0,
        ];
    }

    private function articlePayload(KnowledgeBaseArticle $article): array
    {
        return [
            'id' => $article->public_id,
            'category_id' => $article->category?->public_id,
            'category' => $article->category?->name,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'visibility' => $article->visibility,
            'status' => $article->status,
            'published_at' => $article->published_at?->toISOString(),
            'versions_count' => $article->versions_count ?? 0,
            'feedback_count' => $article->feedback_count ?? 0,
            'helpful_count' => $article->helpful_count ?? 0,
            'not_helpful_count' => $article->not_helpful_count ?? 0,
            'versions' => $article->versions->map(fn ($version): array => [
                'version' => $version->version,
                'editor' => $version->editor?->name,
                'status' => $version->status,
                'visibility' => $version->visibility,
                'created_at' => $version->created_at?->toISOString(),
            ]),
        ];
    }
}
