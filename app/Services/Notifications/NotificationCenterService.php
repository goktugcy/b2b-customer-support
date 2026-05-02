<?php

namespace App\Services\Notifications;

use App\Events\UserNotificationUpdated;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationCenterService
{
    public function forUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()
            ->latest()
            ->paginate($perPage);
    }

    public function latestForUser(User $user, string $filter = 'all', int $limit = 20): Collection
    {
        return $user->notifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->limit(min(max($limit, 1), 50))
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markRead(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();

        $notification->markAsRead();
        UserNotificationUpdated::dispatch($user);
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
        UserNotificationUpdated::dispatch($user);
    }
}
