<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_users_cannot_access_other_company_tickets(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $companyA = Company::factory()->create(['type' => CompanyType::Client]);
        $companyB = Company::factory()->create(['type' => CompanyType::Client]);

        $userA = User::factory()->for($companyA)->create();
        $userA->assignRole(RoleName::CustomerUser->value);

        $ticketA = Ticket::factory()->create(['company_id' => $companyA->id]);
        $ticketB = Ticket::factory()->create(['company_id' => $companyB->id]);

        $this->actingAs($userA)
            ->get(route('portal.tickets.show', $ticketA))
            ->assertOk();

        $this->actingAs($userA)
            ->get(route('portal.tickets.show', $ticketB))
            ->assertForbidden();

        $this->actingAs($userA)
            ->get(route('admin.tickets.index'))
            ->assertForbidden();
    }

    public function test_provider_users_can_access_all_tickets_from_admin_area(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $providerUser = User::factory()->for($provider)->create();
        $providerUser->assignRole(RoleName::ProviderAdmin->value);

        $client = Company::factory()->create(['type' => CompanyType::Client]);
        $ticket = Ticket::factory()->create(['company_id' => $client->id]);

        $this->actingAs($providerUser)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk();
    }
}
