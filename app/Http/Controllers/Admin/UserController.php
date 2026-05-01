<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->with(['company', 'roles'])
                ->when($request->string('company')->isNotEmpty(), fn ($query) => $query->whereHas('company', fn ($company) => $company->where('public_id', $request->string('company'))))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (User $user): array => [
                    'id' => $user->public_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company' => $user->company?->name,
                    'status' => $user->status->value,
                    'roles' => $user->roles->pluck('name'),
                    'last_login_at' => $user->last_login_at?->toISOString(),
                ]),
            'roles' => array_map(fn (RoleName $role): string => $role->value, RoleName::cases()),
            'statuses' => [UserStatus::Active->value, UserStatus::Disabled->value],
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('manage', $user);

        $validated = $request->validate([
            'role_name' => ['required', Rule::in(array_map(fn (RoleName $role): string => $role->value, RoleName::cases()))],
            'status' => ['required', Rule::in([UserStatus::Active->value, UserStatus::Disabled->value])],
        ]);

        $this->assertCanDisable($user, $validated['status']);

        $before = [
            'status' => $user->status->value,
            'roles' => $user->roles->pluck('name')->values()->all(),
        ];

        $user->forceFill(['status' => UserStatus::from($validated['status'])])->save();
        $user->syncRoles([$validated['role_name']]);

        $audit->log('user.updated', $user, $request->user(), before: $before, after: [
            'status' => $user->status->value,
            'roles' => $user->roles()->pluck('name')->values()->all(),
        ], request: $request);

        return back()->with('success', 'User updated.');
    }

    private function assertCanDisable(User $user, string $status): void
    {
        if ($status !== UserStatus::Disabled->value || ! $user->hasRole(RoleName::ProviderAdmin->value)) {
            return;
        }

        $activeProviderAdmins = User::query()
            ->where('status', UserStatus::Active->value)
            ->whereHas('company', fn ($company) => $company->provider())
            ->role(RoleName::ProviderAdmin->value)
            ->count();

        if ($activeProviderAdmins <= 1) {
            throw ValidationException::withMessages(['status' => 'The last active provider admin cannot be disabled.']);
        }
    }
}
