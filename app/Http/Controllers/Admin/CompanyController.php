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
}
