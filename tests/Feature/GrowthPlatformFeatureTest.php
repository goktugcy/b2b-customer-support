<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Models\ApiClient;
use App\Models\AutomationRule;
use App\Models\Company;
use App\Models\InboundEmailMessage;
use App\Models\KnowledgeBaseArticle;
use App\Models\ReportExport;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use App\Services\Automation\AutomationRuleService;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GrowthPlatformFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_ticket_options_reject_missing_ability(): void
    {
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $client = ApiClient::create([
            'company_id' => $company->id,
            'name' => 'Integration',
            'status' => 'active',
        ]);

        $this->withToken($client->createToken('none', [])->plainTextToken)
            ->getJson('/api/v1/meta/ticket-options')
            ->assertForbidden();
    }

    public function test_api_ticket_options_allow_read_or_create_ability(): void
    {
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $client = ApiClient::create([
            'company_id' => $company->id,
            'name' => 'Integration',
            'status' => 'active',
        ]);

        $this->withToken($client->createToken('creator', ['tickets:create'])->plainTextToken)
            ->getJson('/api/v1/meta/ticket-options')
            ->assertOk()
            ->assertJsonStructure(['statuses', 'priorities', 'projects']);
    }

    public function test_knowledge_base_versions_and_portal_feedback_are_recorded(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $author = User::factory()->for($provider)->create();
        $author->assignRole(RoleName::ProviderAdmin->value);

        $article = app(KnowledgeBaseService::class)->storeArticle([
            'title' => 'Reset password',
            'body' => 'Initial body',
            'visibility' => KnowledgeBaseArticle::VISIBILITY_PUBLIC,
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
        ], $author);

        app(KnowledgeBaseService::class)->updateArticle($article, ['body' => 'Updated body'], $author);

        $this->assertSame(2, $article->versions()->count());

        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $customer = User::factory()->for($company)->create();
        $customer->assignRole(RoleName::CustomerUser->value);

        $this->actingAs($customer)
            ->post(route('portal.knowledge-base.feedback', $article->slug), [
                'helpful' => true,
                'comment' => 'Useful',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('knowledge_base_article_feedback', [
            'knowledge_base_article_id' => $article->id,
            'company_id' => $company->id,
            'helpful' => true,
        ]);
    }

    public function test_async_report_export_generates_downloadable_csv(): void
    {
        Storage::fake('local');
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $admin = User::factory()->for($provider)->create();
        $admin->assignRole(RoleName::ProviderAdmin->value);
        Ticket::factory()->create(['subject' => 'Export me']);

        $this->actingAs($admin)
            ->post(route('admin.reports.exports.store'), [
                'type' => 'tickets',
                'format' => 'csv',
            ])
            ->assertRedirect();

        $export = ReportExport::query()->firstOrFail();

        $this->assertSame(ReportExport::STATUS_COMPLETED, $export->fresh()->status);
        Storage::disk('local')->assertExists($export->fresh()->path);
    }

    public function test_inbound_email_creates_ticket_and_deduplicates_message_id(): void
    {
        SupportDepartment::factory()->create();
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $customer = User::factory()->for($company)->create(['email' => 'sender@example.com']);

        $payload = [
            'company_id' => $company->public_id,
            'message_id' => 'email-1@example.com',
            'from' => $customer->email,
            'subject' => 'Email created ticket',
            'body' => 'Please help.',
        ];

        $this->postJson(route('inbound-email', 'generic'), $payload)
            ->assertAccepted()
            ->assertJsonPath('status', InboundEmailMessage::STATUS_PROCESSED);

        $this->assertDatabaseHas('tickets', [
            'company_id' => $company->id,
            'subject' => 'Email created ticket',
        ]);

        $this->postJson(route('inbound-email', 'generic'), $payload)
            ->assertAccepted()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, Ticket::query()->where('subject', 'Email created ticket')->count());
    }

    public function test_automation_rule_applies_ticket_actions_and_logs_execution(): void
    {
        $ticket = Ticket::factory()->create();
        $rule = AutomationRule::create([
            'name' => 'Tag urgent tickets',
            'trigger' => 'ticket.created',
            'conditions' => ['priority' => $ticket->priority->value],
            'actions' => [
                ['type' => 'add_tag', 'name' => 'Needs review'],
            ],
            'enabled' => true,
            'priority' => 10,
        ]);
        $event = TicketEvent::create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'event_type' => 'ticket.created',
            'new_values' => [],
            'occurred_at' => now(),
        ]);

        app(AutomationRuleService::class)->runForEvent($event);

        $this->assertTrue($ticket->fresh()->tags()->where('name', 'Needs review')->exists());
        $this->assertDatabaseHas('automation_rule_executions', [
            'automation_rule_id' => $rule->id,
            'ticket_id' => $ticket->id,
            'status' => 'completed',
        ]);
    }
}
