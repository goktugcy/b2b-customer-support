<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyType;
use App\Enums\SupportDepartmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SupportDepartment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportDepartmentController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SupportDepartment::class);

        return Inertia::render('Admin/Departments/Index', [
            'departments' => SupportDepartment::query()
                ->with('users')
                ->where('company_id', $this->providerCompany()->id)
                ->orderBy('name')
                ->get()
                ->map(fn (SupportDepartment $department): array => [
                    'id' => $department->public_id,
                    'name' => $department->name,
                    'description' => $department->description,
                    'status' => $department->status->value,
                    'user_ids' => $department->users->pluck('public_id')->values(),
                    'users' => $department->users->map(fn (User $user): array => [
                        'id' => $user->public_id,
                        'name' => $user->name,
                    ])->values(),
                ]),
            'providerUsers' => $this->providerUsers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SupportDepartment::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['string', 'exists:users,public_id'],
        ]);

        $provider = $this->providerCompany();
        $department = SupportDepartment::create([
            'company_id' => $provider->id,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($provider, $validated['name']),
            'description' => $validated['description'] ?? null,
            'status' => SupportDepartmentStatus::Active,
        ]);

        $department->users()->sync($this->providerUserIds($validated['user_ids'] ?? []));

        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, SupportDepartment $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_map(fn (SupportDepartmentStatus $status) => $status->value, SupportDepartmentStatus::cases()))],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['string', 'exists:users,public_id'],
        ]);

        $department->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => SupportDepartmentStatus::from($validated['status']),
        ]);

        $department->users()->sync($this->providerUserIds($validated['user_ids'] ?? []));

        return back()->with('success', 'Department updated.');
    }

    public function destroy(SupportDepartment $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->update(['status' => SupportDepartmentStatus::Disabled]);

        return back()->with('success', 'Department disabled.');
    }

    private function providerCompany(): Company
    {
        return Company::query()->where('type', CompanyType::Provider->value)->firstOrFail();
    }

    /**
     * @return list<array{id:string,name:string}>
     */
    private function providerUsers(): array
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('company', fn ($query) => $query->where('type', CompanyType::Provider->value))
            ->orderBy('name')
            ->get(['id', 'public_id', 'name'])
            ->map(fn (User $user): array => ['id' => $user->public_id, 'name' => $user->name])
            ->all();
    }

    /**
     * @param  list<string>  $publicIds
     * @return list<int>
     */
    private function providerUserIds(array $publicIds): array
    {
        return User::query()
            ->whereIn('public_id', collect($publicIds)->filter()->unique()->values())
            ->where('status', 'active')
            ->whereHas('company', fn ($query) => $query->where('type', CompanyType::Provider->value))
            ->pluck('id')
            ->all();
    }

    private function uniqueSlug(Company $provider, string $name): string
    {
        $base = Str::slug($name) ?: 'department';
        $slug = $base;
        $index = 2;

        while (SupportDepartment::query()->where('company_id', $provider->id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$index++;
        }

        return $slug;
    }
}
