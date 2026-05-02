<?php

namespace Database\Factories;

use App\Models\TicketSavedView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketSavedView>
 */
class TicketSavedViewFactory extends Factory
{
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'section' => TicketSavedView::SECTION_PORTAL,
            'name' => fake()->words(3, true),
            'filters' => ['status' => 'open'],
            'columns' => null,
            'sort' => null,
            'is_shared' => false,
            'is_default' => false,
        ];
    }
}
