<?php

namespace Database\Factories;

use App\Models\KnowledgeBaseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeBaseCategory>
 */
class KnowledgeBaseCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'visibility' => KnowledgeBaseCategory::VISIBILITY_PUBLIC,
            'status' => KnowledgeBaseCategory::STATUS_PUBLISHED,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
