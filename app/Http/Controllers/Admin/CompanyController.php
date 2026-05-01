<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Models\Company;
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

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = Company::create($request->validated() + [
            'status' => CompanyStatus::Active,
            'settings' => [],
        ]);

        return redirect()->route('admin.companies.show', $company)->with('success', 'Company created.');
    }

    public function show(Company $company): Response
    {
        $this->authorize('view', $company);

        $company->load(['users.roles', 'apiClients', 'webhookEndpoints']);

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
                    'id' => $endpoint->id,
                    'url' => $endpoint->url,
                    'status' => $endpoint->status->value,
                    'events' => $endpoint->events,
                ]),
            ],
        ]);
    }
}
