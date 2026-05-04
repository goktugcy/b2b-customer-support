<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\TicketStatus;
use App\Enums\UserStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\AutomationRuleExecution;
use App\Models\CannedResponse;
use App\Models\Company;
use App\Models\CompanySlaPolicy;
use App\Models\Invitation;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\ReportExport;
use App\Models\SupportDepartment;
use App\Models\SupportProject;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketCustomField;
use App\Models\TicketTag;
use App\Models\TicketTracker;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommandCenterController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()->can('companies.manage'), 403);

        $openStatuses = [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::WaitingOnCustomer->value,
            TicketStatus::Pending->value,
        ];

        $ticketQuery = Ticket::query()->visibleTo($request->user());
        $clientCompanies = Company::query()->clients()->get(['id', 'settings']);

        return Inertia::render('Admin/CommandCenter/Index', [
            'platform' => [
                'queue_connection' => config('queue.default'),
                'broadcast_connection' => config('broadcasting.default'),
                'reverb_enabled' => config('broadcasting.default') === 'reverb',
                'attachment_max_mb' => round(((int) config('support.attachments.max_kilobytes', 20480)) / 1024, 1),
                'attachment_extensions' => array_values(config('support.attachments.allowed_extensions', [])),
                'report_exports' => [
                    'pending' => ReportExport::query()->where('status', ReportExport::STATUS_PENDING)->count(),
                    'processing' => ReportExport::query()->where('status', ReportExport::STATUS_PROCESSING)->count(),
                    'failed' => ReportExport::query()->where('status', ReportExport::STATUS_FAILED)->count(),
                ],
            ],
            'operations' => [
                'open' => (clone $ticketQuery)->whereIn('status', $openStatuses)->count(),
                'overdue' => (clone $ticketQuery)
                    ->whereIn('status', $openStatuses)
                    ->where(fn ($query) => $query
                        ->whereNotNull('sla_first_response_breached_at')
                        ->orWhereNotNull('sla_resolution_breached_at'))
                    ->count(),
                'unassigned' => (clone $ticketQuery)
                    ->whereIn('status', $openStatuses)
                    ->whereNull('assigned_to_user_id')
                    ->count(),
                'due_soon' => (clone $ticketQuery)
                    ->whereIn('status', $openStatuses)
                    ->whereNull('sla_first_response_breached_at')
                    ->whereNull('sla_resolution_breached_at')
                    ->where(fn ($query) => $query
                        ->where(fn ($first) => $first
                            ->whereNull('first_responded_at')
                            ->whereBetween('first_response_due_at', [now(), now()->addHours(4)]))
                        ->orWhereBetween('due_at', [now(), now()->addHours(4)]))
                    ->count(),
                'failed_webhooks' => WebhookDelivery::query()
                    ->where('status', WebhookDeliveryStatus::Failed->value)
                    ->count(),
                'failed_automations' => AutomationRuleExecution::query()
                    ->where('status', 'failed')
                    ->count(),
            ],
            'customers' => [
                'active_companies' => Company::query()->clients()->where('status', CompanyStatus::Active->value)->count(),
                'suspended_companies' => Company::query()->clients()->where('status', CompanyStatus::Suspended->value)->count(),
                'users' => User::query()->whereHas('company', fn ($query) => $query->clients())->count(),
                'disabled_users' => User::query()
                    ->whereHas('company', fn ($query) => $query->clients())
                    ->where('status', UserStatus::Disabled->value)
                    ->count(),
                'pending_invitations' => Invitation::query()
                    ->whereNull('accepted_at')
                    ->whereNull('revoked_at')
                    ->where('expires_at', '>', now())
                    ->count(),
                'api_docs_enabled_companies' => $clientCompanies->filter(fn (Company $company): bool => $company->hasApiDocsAccess())->count(),
                'companies' => Company::query()
                    ->clients()
                    ->withCount(['users', 'tickets'])
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn (Company $company): array => [
                        'id' => $company->public_id,
                        'name' => $company->name,
                        'slug' => $company->slug,
                        'status' => $company->status->value,
                        'users_count' => $company->users_count,
                        'tickets_count' => $company->tickets_count,
                        'api_docs_enabled' => $company->hasApiDocsAccess(),
                    ]),
            ],
            'configuration' => [
                'knowledge_base' => [
                    'categories' => KnowledgeBaseCategory::query()->count(),
                    'published_articles' => KnowledgeBaseArticle::query()->where('status', KnowledgeBaseArticle::STATUS_PUBLISHED)->count(),
                    'draft_articles' => KnowledgeBaseArticle::query()->where('status', KnowledgeBaseArticle::STATUS_DRAFT)->count(),
                ],
                'issue_tracking' => [
                    'projects' => SupportProject::query()->count(),
                    'trackers' => TicketTracker::query()->count(),
                    'categories' => TicketCategory::query()->count(),
                    'tags' => TicketTag::query()->count(),
                    'custom_fields' => TicketCustomField::query()->count(),
                ],
                'support' => [
                    'departments' => SupportDepartment::query()->count(),
                    'canned_responses' => CannedResponse::query()->count(),
                    'automation_rules' => AutomationRule::query()->count(),
                    'sla_policies' => CompanySlaPolicy::query()->count(),
                    'client_companies_without_sla' => Company::query()
                        ->where('type', CompanyType::Client->value)
                        ->whereDoesntHave('slaPolicies')
                        ->count(),
                ],
            ],
        ]);
    }
}
