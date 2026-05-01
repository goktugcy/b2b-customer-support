<?php

namespace App\Console\Commands;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateProviderAdminCommand extends Command
{
    protected $signature = 'support:create-provider-admin
        {email : Admin email address}
        {--name=Provider Admin : Admin display name}
        {--password= : Admin password. If omitted, a random password is generated.}';

    protected $description = 'Create or update the first provider admin user.';

    public function handle(): int
    {
        $company = Company::firstOrCreate([
            'slug' => 'main-provider',
        ], [
            'type' => CompanyType::Provider,
            'name' => 'Main Provider',
            'status' => CompanyStatus::Active,
            'timezone' => 'UTC',
            'settings' => [],
        ]);

        $password = $this->option('password') ?: Str::password(16);

        $user = User::updateOrCreate([
            'email' => $this->argument('email'),
        ], [
            'company_id' => $company->id,
            'name' => $this->option('name'),
            'password' => $password,
            'email_verified_at' => now(),
            'status' => UserStatus::Active,
        ]);

        $user->assignRole(RoleName::ProviderAdmin->value);

        $this->info('Provider admin ready: '.$user->email);

        if (! $this->option('password')) {
            $this->line('Generated password: '.$password);
        }

        return self::SUCCESS;
    }
}
