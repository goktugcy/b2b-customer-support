<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Enums\TicketStatus;
use App\Enums\TicketVisibility;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketCommentService;
use App\Services\Tickets\TicketWorkflowService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_ticket_transition_is_rejected(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $agent = User::factory()->for($provider)->create();
        $agent->assignRole(RoleName::Agent->value);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        $this->expectException(ValidationException::class);

        app(TicketWorkflowService::class)->transition($ticket, TicketStatus::Resolved, $agent);
    }

    public function test_customer_comment_reopens_resolved_ticket(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $user = User::factory()->for($company)->create();
        $user->assignRole(RoleName::CustomerUser->value);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);

        app(TicketCommentService::class)->create($ticket, $user, 'Still broken', TicketVisibility::Public);

        $this->assertSame(TicketStatus::Open, $ticket->refresh()->status);
    }

    public function test_customer_can_resolve_and_close_own_ticket_but_cannot_reopen_closed_ticket(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $user = User::factory()->for($company)->create();
        $user->assignRole(RoleName::CustomerUser->value);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'created_by_user_id' => $user->id,
            'requester_user_id' => $user->id,
            'status' => TicketStatus::InProgress,
        ]);

        $workflow = app(TicketWorkflowService::class);

        $workflow->customerTransition($ticket, TicketStatus::Closed, $user);

        $this->assertSame(TicketStatus::Closed, $ticket->refresh()->status);

        $this->expectException(ValidationException::class);

        $workflow->customerTransition($ticket, TicketStatus::Resolved, $user);
    }

    public function test_provider_can_reopen_closed_ticket(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $agent = User::factory()->for($provider)->create();
        $agent->assignRole(RoleName::Agent->value);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        app(TicketWorkflowService::class)->transition($ticket, TicketStatus::InProgress, $agent);

        $this->assertSame(TicketStatus::InProgress, $ticket->refresh()->status);
    }

    public function test_customer_cannot_close_another_users_ticket(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $user = User::factory()->for($company)->create();
        $user->assignRole(RoleName::CustomerUser->value);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status' => TicketStatus::InProgress,
        ]);

        $this->expectException(AuthorizationException::class);

        app(TicketWorkflowService::class)->customerTransition($ticket, TicketStatus::Closed, $user);
    }
}
