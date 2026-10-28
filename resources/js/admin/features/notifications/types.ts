import type { Paginated } from '@/shared/types';

/** Mirrors `App\Notifications\NewOrderNotification::TYPE` and `NewContactMessageNotification::TYPE`. */
export type NotificationType = 'order' | 'message';

/** One row of `Admin\Notification\NotificationResource`. */
export type AdminNotification = {
    id: string;
    type: NotificationType;
    title: string;
    body: string;
    /** Admin path the item opens, e.g. `/admin/orders/12`. */
    url: string;
    read: boolean;
    created_at: string;
};

/** Shared props of every signed-in admin page (see HandleInertiaRequests). */
export type NotificationSharedProps = {
    notifications: { unreadCount: number } | null;
    /** Optional prop: only present after `router.reload({ only: ['notificationItems'] })`. */
    notificationItems?: AdminNotification[];
};

export type NotificationFilters = {
    type: NotificationType | null;
    status: 'unread' | 'read' | null;
};

export type NotificationIndexProps = {
    feed: Paginated<AdminNotification>;
    filters: NotificationFilters;
};
