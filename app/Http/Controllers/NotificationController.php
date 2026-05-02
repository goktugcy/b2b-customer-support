<?php

namespace App\Http\Controllers;

use App\Services\Notifications\NotificationCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationCenterService $notifications): Response
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications->forUser($request->user())
                ->through(fn ($notification): array => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                ]),
        ]);
    }

    public function markRead(Request $request, string $notification, NotificationCenterService $notifications): RedirectResponse
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $notifications->markRead($request->user(), $notification);

        return back();
    }

    public function markAllRead(Request $request, NotificationCenterService $notifications): RedirectResponse
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $notifications->markAllRead($request->user());

        return back();
    }
}
