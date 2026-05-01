<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketVisibility;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\Invitations\InvitationService;
use App\Services\Tickets\TicketAttachmentService;
use App\Services\Tickets\TicketCreationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductionCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_sla_policy_sets_due_dates_and_breach_command_marks_overdue_tickets(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $customer = User::factory()->for($company)->create();
        $customer->assignRole(RoleName::CustomerUser->value);
        $department = SupportDepartment::factory()->create();

        $ticket = app(TicketCreationService::class)->create([
            'company_id' => $company->id,
            'subject' => 'SLA ticket',
            'description' => 'Needs a response.',
            'priority' => TicketPriority::Urgent->value,
            'target_department_ids' => [$department->public_id],
        ], $customer, TicketSource::Portal);

        $this->assertNotNull($ticket->fresh()->first_response_due_at);
        $this->assertNotNull($ticket->fresh()->due_at);

        $ticket->forceFill([
            'first_response_due_at' => now()->subMinute(),
            'due_at' => now()->subMinute(),
        ])->save();

        $this->artisan('support:check-sla-breaches')->assertSuccessful();

        $this->assertNotNull($ticket->fresh()->sla_first_response_breached_at);
        $this->assertNotNull($ticket->fresh()->sla_resolution_breached_at);
    }

    public function test_attachment_upload_rejects_disallowed_file_types(): void
    {
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $customer = User::factory()->for($company)->create();
        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        $this->expectException(ValidationException::class);

        app(TicketAttachmentService::class)->store(
            $ticket,
            UploadedFile::fake()->create('payload.exe', 1, 'application/x-msdownload'),
            $customer,
            TicketVisibility::Public,
        );
    }

    public function test_duplicate_pending_invitation_is_rejected_and_invitation_can_be_revoked_and_resent(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $adminCompany = Company::factory()->provider()->create();
        $admin = User::factory()->for($adminCompany)->create();
        $admin->assignRole(RoleName::ProviderAdmin->value);
        $service = app(InvitationService::class);

        $result = $service->create($company, 'user@example.com', 'User', RoleName::CustomerUser->value, $admin);

        try {
            $service->create($company, 'USER@example.com', 'User', RoleName::CustomerUser->value, $admin);
            $this->fail('Duplicate pending invitations should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        $service->revoke($result['invitation']->fresh(), $admin);
        $this->assertNotNull($result['invitation']->fresh()->revoked_at);

        $resent = $service->resend($result['invitation']->fresh(), $admin);
        $this->assertInstanceOf(Invitation::class, $resent['invitation']);
        $this->assertNull($resent['invitation']->fresh()->revoked_at);
    }

    public function test_user_lifecycle_prevents_disabling_last_provider_admin(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $provider = Company::factory()->provider()->create();
        $admin = User::factory()->for($provider)->create();
        $admin->assignRole(RoleName::ProviderAdmin->value);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), [
                'role_name' => RoleName::ProviderAdmin->value,
                'status' => UserStatus::Disabled->value,
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_webhook_secret_rotation_test_delivery_and_retry_are_tenant_scoped(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $this->seed(RoleAndPermissionSeeder::class);
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $admin = User::factory()->for($company)->create();
        $admin->assignRole(RoleName::CustomerAdmin->value);
        $endpoint = WebhookEndpoint::create([
            'company_id' => $company->id,
            'url' => 'https://example.com/webhook',
            'secret' => 'secret',
            'events' => ['ticket.created'],
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->patch(route('portal.webhooks.secret', $endpoint))
            ->assertSessionHas('webhook_secret');

        $this->actingAs($admin)
            ->post(route('portal.webhooks.test', $endpoint))
            ->assertRedirect();

        $delivery = WebhookDelivery::query()->where('webhook_endpoint_id', $endpoint->id)->latest()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('portal.webhooks.deliveries.retry', [$endpoint, $delivery]))
            ->assertRedirect();
    }
}
