<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $provider = Company::firstOrCreate([
            'slug' => 'main-provider',
        ], [
            'type' => CompanyType::Provider,
            'name' => 'Main Provider',
            'status' => 'active',
            'timezone' => 'UTC',
            'settings' => [],
        ]);

        $user = User::firstOrCreate([
            'email' => env('SEED_PROVIDER_ADMIN_EMAIL', 'admin@example.com'),
        ], [
            'company_id' => $provider->id,
            'name' => env('SEED_PROVIDER_ADMIN_NAME', 'Provider Admin'),
            'password' => env('SEED_PROVIDER_ADMIN_PASSWORD', 'password'),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $user->assignRole(RoleName::ProviderAdmin->value);

        Company::firstOrCreate([
            'slug' => 'acme-corp',
        ], [
            'type' => CompanyType::Client,
            'name' => 'Acme Corp',
            'status' => 'active',
            'timezone' => 'UTC',
            'settings' => [],
        ]);
    }
}
