<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Models\ApiClient;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketNumberAndNotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_numbers_are_company_scoped_and_api_exposes_them(): void
    {
        $companyA = Company::factory()->create(['type' => CompanyType::Client]);
        $companyB = Company::factory()->create(['type' => CompanyType::Client]);

        $ticketA = Ticket::factory()->create(['company_id' => $companyA->id]);
        $ticketA2 = Ticket::factory()->create(['company_id' => $companyA->id]);
        $ticketB = Ticket::factory()->create(['company_id' => $companyB->id]);

        $this->assertSame(100001, $ticketA->ticket_number);
        $this->assertSame(100002, $ticketA2->ticket_number);
        $this->assertSame(100001, $ticketB->ticket_number);

        $client = ApiClient::create([
            'company_id' => $companyA->id,
            'name' => 'Integration',
            'status' => 'active',
        ]);
        $token = $client->createToken('integration', ['tickets:read'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/tickets/'.$ticketA->public_id)
            ->assertOk()
            ->assertJsonPath('data.id', $ticketA->public_id)
            ->assertJsonPath('data.ticket_number', 100001)
            ->assertJsonPath('data.display_id', '#100001');
    }

    public function test_numeric_admin_route_and_legacy_public_id_redirect_work(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $providerUser = User::factory()->for($provider)->create();
        $providerUser->assignRole(RoleName::ProviderAdmin->value);

        $client = Company::factory()->create(['type' => CompanyType::Client]);
        $ticket = Ticket::factory()->create(['company_id' => $client->id]);

        $this->actingAs($providerUser)
            ->get(route('admin.tickets.show', $ticket->adminRouteParameters()))
            ->assertOk()
            ->assertSee($ticket->displayId());

        $this->actingAs($providerUser)
            ->get(route('admin.tickets.legacy-show', $ticket))
            ->assertRedirect(route('admin.tickets.show', $ticket->adminRouteParameters()));
    }

    public function test_notification_inbox_filters_and_json_read_state(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $user = User::factory()->for($company)->create();
        $user->assignRole(RoleName::CustomerUser->value);

        $unread = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'ticket.updated',
            'data' => [
                'display_id' => '#100001',
                'ticket_subject' => 'Unread ticket',
                'message' => 'A reply was added.',
                'url' => '/portal/tickets/100001',
            ],
            'read_at' => null,
        ]);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'ticket.updated',
            'data' => [
                'display_id' => '#100002',
                'ticket_subject' => 'Read ticket',
                'message' => 'This was already read.',
            ],
            'read_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.inbox', ['filter' => 'unread']))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.data.ticket_subject', 'Unread ticket');

        $this->actingAs($user)
            ->patchJson(route('notifications.read', $unread->id))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($unread->fresh()->read_at);
    }
}
