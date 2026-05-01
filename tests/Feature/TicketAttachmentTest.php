<?php

namespace Tests\Feature;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Enums\TicketVisibility;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketAttachmentService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_download_uses_public_id_and_respects_visibility(): void
    {
        Storage::fake('local');
        $this->seed(RoleAndPermissionSeeder::class);

        $provider = Company::factory()->provider()->create();
        $providerUser = User::factory()->for($provider)->create();
        $providerUser->assignRole(RoleName::ProviderAdmin->value);

        $clientCompany = Company::factory()->create(['type' => CompanyType::Client]);
        $customer = User::factory()->for($clientCompany)->create();
        $customer->assignRole(RoleName::CustomerUser->value);

        $ticket = Ticket::factory()->create(['company_id' => $clientCompany->id]);

        $attachments = app(TicketAttachmentService::class);
        $publicAttachment = $attachments->store(
            $ticket,
            UploadedFile::fake()->create('public.txt', 10),
            $customer,
            TicketVisibility::Public,
        );
        $internalAttachment = $attachments->store(
            $ticket,
            UploadedFile::fake()->create('internal.txt', 10),
            $providerUser,
            TicketVisibility::Internal,
        );

        $this->assertNotNull($publicAttachment->public_id);
        $this->assertNotSame((string) $publicAttachment->id, $publicAttachment->public_id);

        $this->actingAs($customer)
            ->get(route('attachments.download', $publicAttachment))
            ->assertOk();

        $this->actingAs($customer)
            ->get(route('attachments.download', $internalAttachment))
            ->assertForbidden();

        $this->actingAs($providerUser)
            ->get(route('attachments.download', $internalAttachment))
            ->assertOk();
    }
}
