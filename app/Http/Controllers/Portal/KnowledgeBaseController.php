<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request, KnowledgeBaseService $knowledgeBase): Response
    {
        return Inertia::render('Portal/KnowledgeBase/Index', [
            'categories' => $knowledgeBase->categoriesForPortal()->map(fn ($category): array => [
                'id' => $category->public_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'articles_count' => $category->articles_count,
            ]),
            'articles' => $knowledgeBase->articlesForPortal(
                $request->string('search')->toString() ?: null,
                $request->string('category')->toString() ?: null,
            )->map(fn (KnowledgeBaseArticle $article): array => $this->articlePayload($article)),
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    public function show(string $slug): Response
    {
        $article = KnowledgeBaseArticle::query()
            ->visibleToPortal()
            ->with('category')
            ->withCount([
                'feedback',
                'feedback as helpful_count' => fn ($query) => $query->where('helpful', true),
                'feedback as not_helpful_count' => fn ($query) => $query->where('helpful', false),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Portal/KnowledgeBase/Show', [
            'article' => $this->articlePayload($article) + [
                'body' => $article->body,
                'feedback_count' => $article->feedback_count ?? 0,
                'helpful_count' => $article->helpful_count ?? 0,
                'not_helpful_count' => $article->not_helpful_count ?? 0,
            ],
        ]);
    }

    public function feedback(Request $request, string $slug, KnowledgeBaseService $knowledgeBase): RedirectResponse
    {
        $article = KnowledgeBaseArticle::query()
            ->visibleToPortal()
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'helpful' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $knowledgeBase->storeFeedback($article, $request->user(), (bool) $validated['helpful'], $validated['comment'] ?? null, $request);

        return back()->with('success', 'Feedback recorded.');
    }

    private function articlePayload(KnowledgeBaseArticle $article): array
    {
        return [
            'id' => $article->public_id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'category' => $article->category?->name,
            'category_slug' => $article->category?->slug,
            'published_at' => $article->published_at?->toISOString(),
        ];
    }
}
