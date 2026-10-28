import { router } from '@inertiajs/react';
import { Mail, ShoppingBag } from 'lucide-react';
import { read } from '@/routes/admin/notifications';
import { formatRelative } from '@admin/lib/relative-time';
import { cn } from '@admin/utils';
import type { AdminNotification, NotificationType } from './types';

const ICONS: Record<NotificationType, typeof Mail> = {
    order: ShoppingBag,
    message: Mail,
};

type NotificationItemProps = {
    notification: AdminNotification;
    /** Called when the item is clicked, before the visit starts (the bell closes itself here). */
    onOpen?: () => void;
};

/** One notification row: type icon, title, body, relative time and an unread dot. Clicking marks it read and opens its page. */
export default function NotificationItem({
    notification,
    onOpen,
}: NotificationItemProps) {
    const Icon = ICONS[notification.type];

    const open = () => {
        onOpen?.();
        router.post(read(notification.id).url);
    };

    return (
        <button
            type="button"
            onClick={open}
            className={cn(
                'flex w-full gap-3 border-b border-gray-100 px-4.5 py-3 text-start hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5',
                !notification.read && 'bg-brand-50/40 dark:bg-white/3',
            )}
        >
            <span className="bg-brand-50 text-brand-500 dark:bg-brand-500/15 flex size-10 shrink-0 items-center justify-center rounded-full">
                <Icon className="size-5" aria-hidden="true" />
            </span>

            <span className="min-w-0 flex-1">
                <span className="text-theme-sm block font-medium text-gray-800 dark:text-white/90">
                    {notification.title}
                </span>
                <span className="text-theme-sm block truncate text-gray-500 dark:text-gray-400">
                    {notification.body}
                </span>
                <span className="text-theme-xs mt-1 block text-gray-400">
                    {formatRelative(notification.created_at)}
                </span>
            </span>

            {!notification.read && (
                <span
                    role="img"
                    aria-label="Unread"
                    className="bg-brand-500 mt-2 size-2 shrink-0 rounded-full"
                />
            )}
        </button>
    );
}
