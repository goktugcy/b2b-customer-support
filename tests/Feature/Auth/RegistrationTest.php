<?php

namespace Tests\Feature\Auth;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_not_available(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_users_can_register_only_by_accepting_invitation(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $inviter = User::factory()->for($company)->create();
        $token = 'plain-invitation-token';

        Invitation::create([
            'company_id' => $company->id,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role_name' => RoleName::CustomerUser->value,
            'token_hash' => hash('sha256', $token),
            'invited_by_user_id' => $inviter->id,
            'expires_at' => now()->addDay(),
            'metadata' => [],
        ]);

        $response = $this->post(route('invitations.accept.store', $token), [
            'name' => 'Test User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('portal.home', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'company_id' => $company->id,
        ]);
    }
}
