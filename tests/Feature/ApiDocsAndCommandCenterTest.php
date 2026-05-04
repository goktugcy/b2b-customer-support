<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocsAndCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_api_docs_require_authentication(): void
    {
        $this->get(route('api-docs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_provider_admin_can_view_swagger_ui_and_openapi_spec(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $admin = User::factory()->for($provider)->create();
        $admin->assignRole(RoleName::ProviderAdmin->value);

        $this->actingAs($admin)
            ->get(route('api-docs.index'))
            ->assertOk()
            ->assertViewIs('api-docs')
            ->assertSee('Interactive API documentation');

        $this->actingAs($admin)
            ->get(route('api-docs.openapi'))
            ->assertOk();
    }

    public function test_customer_users_need_company_api_docs_access(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $company = Company::factory()->create(['type' => CompanyType::Client, 'settings' => []]);
        $customer = User::factory()->for($company)->create();
        $customer->assignRole(RoleName::CustomerAdmin->value);

        $this->actingAs($customer)
            ->get(route('api-docs.index'))
            ->assertForbidden();

        $company->update(['settings' => ['api_docs' => ['enabled' => true]]]);
        $customer->unsetRelation('company');

        $this->actingAs($customer)
            ->get(route('api-docs.index'))
            ->assertOk();
    }

    public function test_provider_admin_can_toggle_company_api_docs_access_and_audit_is_recorded(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $admin = User::factory()->for($provider)->create();
        $admin->assignRole(RoleName::ProviderAdmin->value);
        $company = Company::factory()->create(['type' => CompanyType::Client, 'settings' => []]);

        $this->actingAs($admin)
            ->patch(route('admin.companies.api-docs-access.update', $company), [
                'enabled' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($company->fresh()->hasApiDocsAccess());
        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'actor_user_id' => $admin->id,
            'action' => 'company.api_docs_access_updated',
        ]);
    }

    public function test_command_center_is_provider_admin_only(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $admin = User::factory()->for($provider)->create();
        $admin->assignRole(RoleName::ProviderAdmin->value);

        $company = Company::factory()->create(['type' => CompanyType::Client]);
        $customer = User::factory()->for($company)->create();
        $customer->assignRole(RoleName::CustomerAdmin->value);

        $this->actingAs($admin)
            ->get(route('admin.command-center.index'))
            ->assertOk();

        $this->actingAs($customer)
            ->get(route('admin.command-center.index'))
            ->assertForbidden();
    }
}
