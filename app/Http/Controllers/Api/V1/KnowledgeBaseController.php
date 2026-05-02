<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function categories(Request $request, KnowledgeBaseService $knowledgeBase): JsonResponse
    {
        abort_unless($request->user()->tokenCan('knowledge_base:read'), 403);

        return response()->json([
            'data' => $knowledgeBase->categoriesForPortal()->map(fn ($category): array => [
                'id' => $category->public_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'articles_count' => $category->articles_count,
            ]),
        ]);
    }

    public function articles(Request $request, KnowledgeBaseService $knowledgeBase): JsonResponse
    {
        abort_unless($request->user()->tokenCan('knowledge_base:read'), 403);

        return response()->json([
            'data' => $knowledgeBase->articlesForPortal(
                $request->query('search'),
                $request->query('category'),
            )->map(fn (KnowledgeBaseArticle $article): array => $this->articlePayload($article)),
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        abort_unless($request->user()->tokenCan('knowledge_base:read'), 403);

        $article = KnowledgeBaseArticle::query()
            ->visibleToPortal()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $this->articlePayload($article) + ['body' => $article->body]]);
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
