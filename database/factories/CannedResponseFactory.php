<?php

namespace Database\Factories;

use App\Models\CannedResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CannedResponse>
 */
class CannedResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'scope' => CannedResponse::SCOPE_GLOBAL,
            'title' => fake()->sentence(3),
            'shortcut' => '/'.fake()->unique()->word(),
            'body' => fake()->paragraph(),
            'variables' => ['ticket.subject', 'requester.name'],
            'status' => CannedResponse::STATUS_PUBLISHED,
        ];
    }
}
