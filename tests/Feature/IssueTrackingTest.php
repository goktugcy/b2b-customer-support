<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Enums\TicketVisibility;
use App\Models\ApiClient;
use App\Models\Company;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use App\Models\TicketCustomField;
use App\Models\TicketTag;
use App\Models\User;
use App\Services\Tickets\IssueTrackingService;
use App\Services\Tickets\TicketCommentService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_create_assigns_default_project_tracker_sanitizes_html_and_creates_tags(): void
    {
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $department = SupportDepartment::factory()->create();
        $client = ApiClient::create([
            'company_id' => $company->id,
            'name' => 'Integration',
            'status' => 'active',
        ]);
        $token = $client->createToken('integration', ['tickets:create', 'tickets:read'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/tickets', [
            'subject' => 'API ticket',
            'description' => '<p>Hello</p><script>alert(1)</script><a href="javascript:alert(1)">bad</a>',
            'tag_names' => ['ERP', 'Urgent Context'],
            'target_department_ids' => [$department->public_id],
        ])->assertCreated()
            ->assertJsonPath('data.project.name', 'General')
            ->assertJsonPath('data.tracker.name', 'Support')
            ->assertJsonFragment(['name' => 'ERP']);

        $ticket = Ticket::query()->with(['supportProject', 'tracker', 'tags'])->firstOrFail();

        $this->assertSame('General', $ticket->supportProject->name);
        $this->assertSame('Support', $ticket->tracker->name);
        $this->assertStringNotContainsString('<script', $ticket->description);
        $this->assertStringNotContainsString('javascript:', $ticket->description);
        $this->assertTrue($ticket->tags->contains('name', 'Urgent Context'));
    }

    public function test_custom_field_required_and_regex_validation_runs_during_api_create(): void
    {
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $department = SupportDepartment::factory()->create();
        $tracker = app(IssueTrackingService::class)->defaultTracker();
        $field = TicketCustomField::create([
            'ticket_tracker_id' => $tracker->id,
            'name' => 'Incident reference',
            'slug' => 'incident-reference',
            'type' => 'text',
            'is_required' => true,
            'validation_regex' => '/^INC-[0-9]+$/',
            'settings' => [],
            'status' => 'active',
            'sort_order' => 0,
        ]);
        $client = ApiClient::create([
            'company_id' => $company->id,
            'name' => 'Integration',
            'status' => 'active',
        ]);
        $token = $client->createToken('integration', ['tickets:create'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/tickets', [
            'subject' => 'Invalid custom field',
            'description' => 'Body',
            'target_department_ids' => [$department->public_id],
            'custom_fields' => [$field->public_id => 'BAD-1'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(["custom_fields.$field->public_id"]);

        $this->assertSame(0, Ticket::query()->count());
    }

    public function test_admin_deleting_tag_removes_pivot_rows(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $admin = User::factory()->for($provider)->create();
        $admin->assignRole(RoleName::ProviderAdmin->value);
        $ticket = Ticket::factory()->create();
        $tag = TicketTag::create(['name' => 'Legacy', 'slug' => 'legacy', 'color' => '#64748b']);
        $ticket->tags()->attach($tag);

        $this->actingAs($admin)
            ->delete(route('admin.issue-tracking.tags.destroy', $tag))
            ->assertRedirect();

        $this->assertDatabaseMissing('ticket_tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('ticket_tag', ['ticket_tag_id' => $tag->id]);
    }

    public function test_comment_body_is_sanitized_before_storage(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $user = User::factory()->for($company)->create();
        $user->assignRole(RoleName::CustomerUser->value);
        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        $comment = app(TicketCommentService::class)->create(
            $ticket,
            $user,
            '<p>Visible</p><img src=x onerror=alert(1)><script>alert(1)</script>',
            TicketVisibility::Public,
        );

        $this->assertStringContainsString('Visible', $comment->body);
        $this->assertStringNotContainsString('<script', $comment->body);
        $this->assertStringNotContainsString('onerror', $comment->body);
        $this->assertStringNotContainsString('<img', $comment->body);
    }
}
