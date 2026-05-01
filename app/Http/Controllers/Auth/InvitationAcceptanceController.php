<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Invitations\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptanceController extends Controller
{
    public function show(string $token, InvitationService $invitations): Response
    {
        $invitation = $invitations->findAcceptableByToken($token);

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token,
            'invitation' => [
                'email' => $invitation->email,
                'name' => $invitation->name,
                'company' => $invitation->company->name,
                'role' => $invitation->role_name,
                'expires_at' => $invitation->expires_at?->toISOString(),
            ],
        ]);
    }

    public function store(string $token, Request $request, InvitationService $invitations): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $invitations->accept($token, $validated, $request);

        return redirect()->intended($user->isProviderUser()
            ? route('admin.home', absolute: false)
            : route('portal.home', absolute: false));
    }
}
