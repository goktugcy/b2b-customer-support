<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'type' => CompanyType::Client,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'status' => CompanyStatus::Active,
            'timezone' => 'UTC',
            'settings' => [],
        ];
    }

    public function provider(): static
    {
        return $this->state(fn (): array => [
            'type' => CompanyType::Provider,
        ]);
    }
}
