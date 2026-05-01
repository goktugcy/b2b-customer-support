<?php

namespace Database\Factories;

use App\Enums\SupportDepartmentStatus;
use App\Models\Company;
use App\Models\SupportDepartment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportDepartment>
 */
class SupportDepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'company_id' => Company::factory()->provider(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->sentence(),
            'status' => SupportDepartmentStatus::Active,
        ];
    }
}
