<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Enums\TicketSource;
use App\Models\Company;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketCreationService;
use App\Services\Tickets\TicketWatcherService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketTargetsAndWatchersTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_creation_requires_a_target_and_persists_valid_targets(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $clientCompany = Company::factory()->create(['type' => CompanyType::Client]);
        $customer = User::factory()->for($clientCompany)->create();
        $customer->assignRole(RoleName::CustomerUser->value);

        try {
            app(TicketCreationService::class)->create([
                'company_id' => $clientCompany->id,
                'subject' => 'Missing target',
                'description' => 'This should roll back.',
            ], $customer, TicketSource::Portal);
        } catch (ValidationException) {
            $this->assertDatabaseMissing('tickets', ['subject' => 'Missing target']);

            return;
        }

        $this->fail('Ticket creation without targets should fail.');
    }

    public function test_valid_ticket_targets_are_saved(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $clientCompany = Company::factory()->create(['type' => CompanyType::Client]);
        $customer = User::factory()->for($clientCompany)->create();
        $customer->assignRole(RoleName::CustomerUser->value);
        $department = SupportDepartment::factory()->create();

        $ticket = app(TicketCreationService::class)->create([
            'company_id' => $clientCompany->id,
            'subject' => 'Targeted ticket',
            'description' => 'Targeted to support.',
            'target_department_ids' => [$department->public_id],
        ], $customer, TicketSource::Portal);

        $this->assertDatabaseHas('ticket_target_departments', [
            'ticket_id' => $ticket->id,
            'support_department_id' => $department->id,
        ]);
    }

    public function test_provider_and_customer_watchers_are_side_restricted(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $providerActor = User::factory()->for($provider)->create();
        $providerActor->assignRole(RoleName::ProviderAdmin->value);
        $providerWatcher = User::factory()->for($provider)->create();
        $providerWatcher->assignRole(RoleName::Agent->value);

        $clientCompany = Company::factory()->create(['type' => CompanyType::Client]);
        $customerActor = User::factory()->for($clientCompany)->create();
        $customerActor->assignRole(RoleName::CustomerUser->value);
        $customerWatcher = User::factory()->for($clientCompany)->create();
        $customerWatcher->assignRole(RoleName::CustomerUser->value);

        $otherCompany = Company::factory()->create(['type' => CompanyType::Client]);
        $otherCustomer = User::factory()->for($otherCompany)->create();
        $otherCustomer->assignRole(RoleName::CustomerUser->value);

        $ticket = Ticket::factory()->create(['company_id' => $clientCompany->id]);
        $service = app(TicketWatcherService::class);

        $service->add($ticket, $providerWatcher, $providerActor);
        $service->add($ticket, $customerWatcher, $customerActor);

        $this->assertDatabaseHas('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $providerWatcher->id,
            'side' => 'provider',
        ]);
        $this->assertDatabaseHas('ticket_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $customerWatcher->id,
            'side' => 'client',
        ]);

        $this->expectException(ValidationException::class);

        $service->add($ticket, $otherCustomer, $customerActor);
    }
}
