<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\NotificationPreference;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'notificationPreferences' => $request->user()->notificationPreference()->firstOrCreate([])->only([
                'database_enabled',
                'mail_enabled',
                'digest_enabled',
                'event_settings',
            ]),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'database_enabled' => ['required', 'boolean'],
            'mail_enabled' => ['required', 'boolean'],
            'digest_enabled' => ['required', 'boolean'],
            'event_settings' => ['nullable', 'array'],
        ]);

        NotificationPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated + ['event_settings' => $validated['event_settings'] ?? []],
        );

        return Redirect::route('profile.edit')->with('success', 'Notification preferences updated.');
    }
}
