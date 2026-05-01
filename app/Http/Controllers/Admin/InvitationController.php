<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvitationRequest;
use App\Models\Company;
use App\Models\Invitation;
use App\Services\Invitations\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invitation::class);

        return Inertia::render('Admin/Invitations/Index', [
            'invitations' => Invitation::query()
                ->with(['company', 'invitedBy'])
                ->latest()
                ->paginate(20)
                ->through(fn (Invitation $invitation): array => [
                    'id' => $invitation->id,
                    'company' => $invitation->company?->name,
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'role_name' => $invitation->role_name,
                    'accepted_at' => $invitation->accepted_at?->toISOString(),
                    'expires_at' => $invitation->expires_at?->toISOString(),
                ]),
            'companies' => Company::orderBy('name')->get(['public_id', 'name', 'type']),
            'roles' => array_map(fn (RoleName $role): string => $role->value, RoleName::cases()),
        ]);
    }

    public function store(StoreInvitationRequest $request, InvitationService $invitations): RedirectResponse
    {
        $company = Company::where('public_id', $request->validated('company_id'))->firstOrFail();
        $this->authorize('create', [Invitation::class, $company]);

        $role = $request->validated('role_name');

        if ($company->type === CompanyType::Client && in_array($role, [RoleName::ProviderAdmin->value, RoleName::Agent->value], true)) {
            throw ValidationException::withMessages(['role_name' => 'Client companies can only receive customer roles.']);
        }

        $result = $invitations->create(
            $company,
            $request->validated('email'),
            $request->validated('name'),
            $role,
            $request->user(),
            $request,
        );

        return back()
            ->with('success', 'Invitation sent.')
            ->with('invitation_url', route('invitations.accept', ['token' => $result['token']]));
    }
}
