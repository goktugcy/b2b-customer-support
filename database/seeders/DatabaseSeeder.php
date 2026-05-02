<?php

namespace Database\Seeders;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Enums\SupportDepartmentStatus;
use App\Models\Company;
use App\Models\CannedResponse;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\SupportDepartment;
use App\Models\SupportProject;
use App\Models\TicketTracker;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $provider = Company::firstOrCreate([
            'slug' => 'main-provider',
        ], [
            'type' => CompanyType::Provider,
            'name' => 'Main Provider',
            'status' => 'active',
            'timezone' => 'UTC',
            'settings' => [],
        ]);

        $user = User::firstOrCreate([
            'email' => env('SEED_PROVIDER_ADMIN_EMAIL', 'admin@example.com'),
        ], [
            'company_id' => $provider->id,
            'name' => env('SEED_PROVIDER_ADMIN_NAME', 'Provider Admin'),
            'password' => env('SEED_PROVIDER_ADMIN_PASSWORD', 'password'),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $user->assignRole(RoleName::ProviderAdmin->value);

        SupportDepartment::firstOrCreate([
            'company_id' => $provider->id,
            'slug' => 'general-support',
        ], [
            'name' => 'General Support',
            'description' => 'Default support queue for incoming customer tickets.',
            'status' => SupportDepartmentStatus::Active,
        ]);

        $client = Company::firstOrCreate([
            'slug' => 'acme-corp',
        ], [
            'type' => CompanyType::Client,
            'name' => 'Acme Corp',
            'status' => 'active',
            'timezone' => 'UTC',
            'settings' => [],
        ]);

        TicketTracker::firstOrCreate([
            'slug' => 'support',
        ], [
            'name' => 'Support',
            'description' => 'Default support issue tracker.',
            'color' => '#2563eb',
            'status' => 'active',
            'is_default' => true,
            'sort_order' => 0,
        ]);

        SupportProject::firstOrCreate([
            'company_id' => $client->id,
            'slug' => 'general',
        ], [
            'name' => 'General',
            'description' => 'Default support project.',
            'status' => 'active',
            'is_default' => true,
        ]);

        $category = KnowledgeBaseCategory::firstOrCreate([
            'slug' => 'getting-started',
        ], [
            'name' => 'Getting started',
            'visibility' => KnowledgeBaseCategory::VISIBILITY_PUBLIC,
            'status' => KnowledgeBaseCategory::STATUS_PUBLISHED,
            'sort_order' => 0,
        ]);

        KnowledgeBaseArticle::firstOrCreate([
            'slug' => 'how-to-open-a-support-ticket',
        ], [
            'knowledge_base_category_id' => $category->id,
            'author_user_id' => $user->id,
            'title' => 'How to open a support ticket',
            'excerpt' => 'Basic steps for creating a complete support request.',
            'body' => '<p>Choose the right project, add a clear subject, and include files or screenshots when they help the support team reproduce the issue.</p>',
            'visibility' => KnowledgeBaseArticle::VISIBILITY_PUBLIC,
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        CannedResponse::firstOrCreate([
            'shortcut' => '/received',
        ], [
            'scope' => CannedResponse::SCOPE_GLOBAL,
            'title' => 'Request received',
            'body' => '<p>Hello {{requester.name}}, we received your request about {{ticket.subject}} and will follow up here.</p>',
            'variables' => ['requester.name', 'ticket.subject'],
            'status' => CannedResponse::STATUS_PUBLISHED,
        ]);
    }
}
