<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class E2eSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate([
            'slug' => 'acme-corp',
        ], [
            'type' => CompanyType::Client,
            'name' => 'Acme Corp',
            'status' => 'active',
            'timezone' => 'UTC',
            'settings' => [],
        ]);

        $user = User::updateOrCreate([
            'email' => 'customer@example.com',
        ], [
            'company_id' => $company->id,
            'name' => 'Customer Admin',
            'password' => 'password',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $user->assignRole(RoleName::CustomerAdmin->value);
    }
}
