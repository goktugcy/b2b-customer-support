<?php

namespace App\Http\Controllers;

use App\Services\Notifications\NotificationCenterService;
use Illuminate\Http\JsonResponse;
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

    public function inbox(Request $request, NotificationCenterService $notifications): JsonResponse
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $filter = $request->string('filter')->lower()->value() === 'unread' ? 'unread' : 'all';

        return response()->json([
            'unread_count' => $notifications->unreadCount($request->user()),
            'notifications' => $notifications->latestForUser($request->user(), $filter, (int) $request->integer('limit', 20))
                ->map(fn ($notification): array => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                ])
                ->values(),
        ]);
    }

    public function markRead(Request $request, string $notification, NotificationCenterService $notifications): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $notifications->markRead($request->user(), $notification);

        if ($request->expectsJson()) {
            return response()->json([
                'unread_count' => $notifications->unreadCount($request->user()),
            ]);
        }

        return back();
    }

    public function markAllRead(Request $request, NotificationCenterService $notifications): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->can('notifications.view'), 403);

        $notifications->markAllRead($request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'unread_count' => 0,
            ]);
        }

        return back();
    }
}
