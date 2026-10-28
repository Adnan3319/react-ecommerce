import { Link, router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { index, readAll } from '@/routes/admin/notifications';
import { useClickOutside } from '@admin/hooks/useClickOutside';
import { cn } from '@admin/utils';
import { BADGE_MAX, BELL_PROPS } from './constants';
import NotificationItem from './NotificationItem';
import type { NotificationSharedProps } from './types';
import {
    useNotificationPolling,
    useUnreadTitle,
} from './useNotificationPolling';

/** Header bell: unread badge, the latest notifications in a dropdown, mark-all-read and a link to the full list. */
export default function NotificationBell() {
    const { notifications, notificationItems } =
        usePage<NotificationSharedProps>().props;
    const unreadCount = notifications?.unreadCount ?? 0;
    const [isOpen, setIsOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useNotificationPolling();
    useUnreadTitle(unreadCount);
    useClickOutside(containerRef, () => setIsOpen(false));

    // Items are an optional prop: fetched when the bell opens, and again when a new one arrives meanwhile.
    useEffect(() => {
        if (isOpen) {
            router.reload({ only: BELL_PROPS });
        }
    }, [isOpen, unreadCount]);

    const markAllRead = () =>
        router.post(
            readAll().url,
            {},
            { only: BELL_PROPS, preserveScroll: true, preserveState: true },
        );

    return (
        <div className="relative" ref={containerRef}>
            <button
                type="button"
                aria-label={
                    unreadCount > 0
                        ? `Notifications, ${unreadCount} unread`
                        : 'Notifications'
                }
                aria-haspopup="dialog"
                aria-expanded={isOpen}
                onClick={() => setIsOpen((open) => !open)}
                className="dropdown-toggle relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            >
                <Bell className="size-5" aria-hidden="true" />
                {unreadCount > 0 && (
                    <span
                        data-testid="notification-badge"
                        className="absolute -inset-e-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-orange-500 px-1 text-[11px] font-semibold text-white"
                    >
                        {unreadCount > BADGE_MAX
                            ? `${BADGE_MAX}+`
                            : unreadCount}
                    </span>
                )}
            </button>

            {isOpen && (
                <div
                    role="dialog"
                    aria-label="Notifications"
                    className={cn(
                        'shadow-theme-lg dark:bg-gray-dark absolute -inset-s-13.5 z-50 mt-4.25 flex max-h-120 w-87.5 flex-col rounded-2xl border border-gray-200 bg-white sm:w-90.25 xl:inset-s-auto xl:inset-e-0 dark:border-gray-800',
                    )}
                >
                    <div className="flex items-center justify-between border-b border-gray-100 px-4.5 py-3 dark:border-gray-700">
                        <h5 className="text-lg font-semibold text-gray-800 dark:text-gray-200">
                            Notifications
                        </h5>
                        {unreadCount > 0 && (
                            <button
                                type="button"
                                onClick={markAllRead}
                                className="text-brand-500 hover:text-brand-600 text-sm font-medium"
                            >
                                Mark all as read
                            </button>
                        )}
                    </div>

                    <div className="custom-scrollbar overflow-y-auto">
                        {notificationItems === undefined && (
                            <p className="px-4.5 py-8 text-center text-sm text-gray-500">
                                Loading…
                            </p>
                        )}
                        {notificationItems?.length === 0 && (
                            <p className="px-4.5 py-8 text-center text-sm text-gray-500">
                                You are all caught up. New orders and messages
                                will show up here.
                            </p>
                        )}
                        {notificationItems?.map((notification) => (
                            <NotificationItem
                                key={notification.id}
                                notification={notification}
                                onOpen={() => setIsOpen(false)}
                            />
                        ))}
                    </div>

                    <Link
                        href={index().url}
                        onClick={() => setIsOpen(false)}
                        className="text-theme-sm border-t border-gray-100 px-4.5 py-3 text-center font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"
                    >
                        View all
                    </Link>
                </div>
            )}
        </div>
    );
}
