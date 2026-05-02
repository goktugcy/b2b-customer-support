<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\ApiClient;
use App\Models\Company;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\Ticket;
use App\Models\TicketCsatSurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadmapFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_knowledge_base_api_hides_internal_and_draft_articles(): void
    {
        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $client = ApiClient::create([
            'company_id' => $company->id,
            'name' => 'Integration',
            'status' => 'active',
        ]);
        $token = $client->createToken('integration', ['knowledge_base:read'])->plainTextToken;

        $category = KnowledgeBaseCategory::factory()->create();
        KnowledgeBaseArticle::factory()->create([
            'knowledge_base_category_id' => $category->id,
            'title' => 'Published public article',
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
            'visibility' => KnowledgeBaseArticle::VISIBILITY_PUBLIC,
        ]);
        KnowledgeBaseArticle::factory()->create([
            'title' => 'Internal article',
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
            'visibility' => KnowledgeBaseArticle::VISIBILITY_INTERNAL,
        ]);
        KnowledgeBaseArticle::factory()->create([
            'title' => 'Draft article',
            'status' => KnowledgeBaseArticle::STATUS_DRAFT,
            'visibility' => KnowledgeBaseArticle::VISIBILITY_PUBLIC,
            'published_at' => null,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/knowledge-base/articles')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Published public article'])
            ->assertJsonMissing(['title' => 'Internal article'])
            ->assertJsonMissing(['title' => 'Draft article']);
    }

    public function test_api_bulk_update_is_company_scoped(): void
    {
        $companyA = Company::factory()->create(['type' => CompanyType::Client]);
        $companyB = Company::factory()->create(['type' => CompanyType::Client]);
        $client = ApiClient::create([
            'company_id' => $companyA->id,
            'name' => 'Integration',
            'status' => 'active',
        ]);
        $token = $client->createToken('integration', ['tickets:bulk_update'])->plainTextToken;

        $ticketA = Ticket::factory()->create(['company_id' => $companyA->id]);
        $ticketB = Ticket::factory()->create(['company_id' => $companyB->id]);

        $this->withToken($token)
            ->patchJson('/api/v1/tickets/bulk', [
                'ticket_ids' => [$ticketA->public_id, $ticketB->public_id],
                'status' => TicketStatus::Resolved->value,
                'priority' => TicketPriority::High->value,
            ])
            ->assertOk()
            ->assertJson(['updated' => 1]);

        $this->assertSame(TicketStatus::Resolved, $ticketA->fresh()->status);
        $this->assertSame(TicketPriority::High, $ticketA->fresh()->priority);
        $this->assertSame(TicketStatus::Open, $ticketB->fresh()->status);
    }

    public function test_csat_token_is_single_use(): void
    {
        $ticket = Ticket::factory()->create();
        $plainToken = 'plain-csat-token';

        TicketCsatSurvey::create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'requester_user_id' => $ticket->requester_user_id,
            'token_hash' => hash('sha256', $plainToken),
            'sent_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $this->postJson('/api/v1/csat/'.$plainToken, [
            'rating' => 5,
            'comment' => 'Great support.',
        ])->assertOk();

        $this->postJson('/api/v1/csat/'.$plainToken, [
            'rating' => 4,
        ])->assertUnprocessable();
    }
}
