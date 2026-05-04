<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Models\Company;
use App\Models\CompanySlaPolicy;
use App\Services\Sla\SlaService;
use App\Services\Audit\AuditLogger;
use App\Services\Tickets\IssueTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Company::class);

        return Inertia::render('Admin/Companies/Index', [
            'companies' => Company::query()
                ->withCount(['users', 'tickets'])
                ->latest()
                ->paginate(15)
                ->through(fn (Company $company): array => [
                    'id' => $company->public_id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'type' => $company->type->value,
                    'status' => $company->status->value,
                    'users_count' => $company->users_count,
                    'tickets_count' => $company->tickets_count,
                ]),
        ]);
    }

    public function store(StoreCompanyRequest $request, IssueTrackingService $issueTracking, SlaService $sla): RedirectResponse
    {
        $company = Company::create($request->validated() + [
            'status' => CompanyStatus::Active,
            'settings' => [],
        ]);

        if ($company->type === CompanyType::Client) {
            $issueTracking->defaultProjectForCompany($company);
            $sla->ensurePolicies($company);
        }

        return redirect()->route('admin.companies.show', $company)->with('success', 'Company created.');
    }

    public function show(Company $company, SlaService $sla): Response
    {
        $this->authorize('view', $company);

        if ($company->isClient()) {
            $sla->ensurePolicies($company);
        }

        $company->load(['users.roles', 'apiClients', 'webhookEndpoints', 'slaPolicies']);

        return Inertia::render('Admin/Companies/Show', [
            'company' => [
                'id' => $company->public_id,
                'name' => $company->name,
                'slug' => $company->slug,
                'type' => $company->type->value,
                'status' => $company->status->value,
                'timezone' => $company->timezone,
                'settings' => $company->settings ?? [],
                'api_docs_enabled' => $company->hasApiDocsAccess(),
                'users' => $company->users->map(fn ($user): array => [
                    'id' => $user->public_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status->value,
                    'roles' => $user->roles->pluck('name'),
                ]),
                'api_clients' => $company->apiClients->map(fn ($client): array => [
                    'id' => $client->public_id,
                    'name' => $client->name,
                    'status' => $client->status->value,
                    'last_used_at' => $client->last_used_at?->toISOString(),
                ]),
                'webhooks' => $company->webhookEndpoints->map(fn ($endpoint): array => [
                    'id' => $endpoint->public_id,
                    'url' => $endpoint->url,
                    'status' => $endpoint->status->value,
                    'events' => $endpoint->events,
                ]),
                'sla_policies' => $company->slaPolicies
                    ->sortBy(fn (CompanySlaPolicy $policy) => array_search($policy->priority, TicketPriority::cases(), true))
                    ->map(fn (CompanySlaPolicy $policy): array => [
                        'id' => $policy->id,
                        'priority' => $policy->priority->value,
                        'first_response_minutes' => $policy->first_response_minutes,
                        'resolution_minutes' => $policy->resolution_minutes,
                        'enabled' => $policy->enabled,
                    ])->values(),
            ],
        ]);
    }

    public function updateSlaPolicy(Request $request, Company $company, CompanySlaPolicy $policy): RedirectResponse
    {
        $this->authorize('manage', Company::class);
        abort_unless($policy->company_id === $company->id, 404);

        $validated = $request->validate([
            'first_response_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'resolution_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'enabled' => ['required', 'boolean'],
        ]);

        $policy->update($validated);

        return back()->with('success', 'SLA policy updated.');
    }

    public function updateBranding(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('manage', Company::class);

        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:160'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'brand_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'timezone' => ['required', 'timezone'],
        ]);

        $settings = $company->settings ?? [];
        $settings['branding'] = [
            'display_name' => $validated['display_name'] ?? null,
            'logo_url' => $validated['logo_url'] ?? null,
            'brand_color' => $validated['brand_color'] ?? null,
        ];

        $company->update([
            'timezone' => $validated['timezone'],
            'settings' => $settings,
        ]);

        return back()->with('success', 'Company branding updated.');
    }

    public function updateApiDocsAccess(Request $request, Company $company, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manage', Company::class);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $before = ['api_docs' => ['enabled' => $company->hasApiDocsAccess()]];
        $settings = $company->settings ?? [];
        data_set($settings, 'api_docs.enabled', (bool) $validated['enabled']);

        $company->update(['settings' => $settings]);

        $after = ['api_docs' => ['enabled' => $company->hasApiDocsAccess()]];

        $audit->log(
            'company.api_docs_access_updated',
            $company,
            $request->user(),
            $before,
            $after,
            null,
            $request,
        );

        return back()->with('success', 'API docs access updated.');
    }
}
