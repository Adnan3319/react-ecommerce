<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The admin bell. Every query goes through `$user->notifications()`, so an admin can never read or change
 * another admin's notification (an id that is not theirs is simply not found).
 */
class NotificationService
{
    public const LATEST_LIMIT = 10;

    public const PRUNE_AFTER_DAYS = 30;

    public const FILTER_UNREAD = 'unread';

    public const FILTER_READ = 'read';

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function latest(User $user): Collection
    {
        return $user->notifications()->latest()->limit(self::LATEST_LIMIT)->get();
    }

    /**
     * @param  array{type: ?string, status: ?string, per_page: int}  $filters
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        return $user->notifications()
            ->when($filters['type'] !== null, fn ($query) => $query->where('data->type', $filters['type']))
            ->when($filters['status'] === self::FILTER_UNREAD, fn ($query) => $query->whereNull('read_at'))
            ->when($filters['status'] === self::FILTER_READ, fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate($filters['per_page'])
            ->withQueryString();
    }

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the notification is not the user's
     */
    public function markRead(User $user, string $id): DatabaseNotification
    {
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $notification;
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Deletes read notifications older than PRUNE_AFTER_DAYS; unread ones are never touched.
     *
     * @return int rows deleted
     */
    public function prune(): int
    {
        return DatabaseNotification::query()
            ->whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays(self::PRUNE_AFTER_DAYS))
            ->delete();
    }
}
