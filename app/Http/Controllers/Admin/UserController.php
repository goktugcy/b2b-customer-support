<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
        ]);
    }
}
