<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBase\KnowledgeBaseService;
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
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Portal/KnowledgeBase/Show', [
            'article' => $this->articlePayload($article) + ['body' => $article->body],
        ]);
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
