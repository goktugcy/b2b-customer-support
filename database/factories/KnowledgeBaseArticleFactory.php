<?php

namespace Database\Factories;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeBaseArticle>
 */
class KnowledgeBaseArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(5);

        return [
            'knowledge_base_category_id' => KnowledgeBaseCategory::factory(),
            'author_user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'excerpt' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'visibility' => KnowledgeBaseArticle::VISIBILITY_PUBLIC,
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
            'published_at' => now(),
        ];
    }
}
