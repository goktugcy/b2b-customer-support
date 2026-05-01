<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\TicketPriority;
use App\Models\ApiClient;
use App\Models\Company;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_api_client_can_create_ticket_and_replay_idempotency_key(): void
    {
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $client = ApiClient::create([
            'company_id' => $company->id,
            'name' => 'Integration',
            'status' => 'active',
        ]);
        $department = SupportDepartment::factory()->create();
        $token = $client->createToken('integration', ['tickets:create', 'tickets:read'])->plainTextToken;

        $payload = [
            'subject' => 'API ticket',
            'description' => 'Created through the API',
            'priority' => TicketPriority::High->value,
            'target_department_ids' => [$department->public_id],
        ];

        $first = $this->withToken($token)
            ->withHeader('Idempotency-Key', 'ticket-123')
            ->postJson('/api/v1/tickets', $payload)
            ->assertCreated()
            ->json('id');

        $second = $this->withToken($token)
            ->withHeader('Idempotency-Key', 'ticket-123')
            ->postJson('/api/v1/tickets', $payload)
            ->assertCreated()
            ->json('id');

        $this->assertSame($first, $second);
        $this->assertSame(1, Ticket::query()->count());
        $this->assertDatabaseHas('tickets', [
            'company_id' => $company->id,
            'subject' => 'API ticket',
        ]);
    }

    public function test_api_client_cannot_read_another_company_ticket(): void
    {
        $companyA = Company::factory()->create(['type' => CompanyType::Client]);
        $companyB = Company::factory()->create(['type' => CompanyType::Client]);

        $client = ApiClient::create([
            'company_id' => $companyA->id,
            'name' => 'Integration',
            'status' => 'active',
        ]);
        $token = $client->createToken('integration', ['tickets:read'])->plainTextToken;

        $ticketB = Ticket::factory()->create(['company_id' => $companyB->id]);

        $this->withToken($token)
            ->getJson('/api/v1/tickets/'.$ticketB->public_id)
            ->assertForbidden();
    }
}
