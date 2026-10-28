import { router } from '@inertiajs/react';
import TableFooter from '@admin/components/common/TableFooter';
import { index, readAll } from '@/routes/admin/notifications';
import { FILTER_ALL } from './constants';
import NotificationItem from './NotificationItem';
import type { NotificationFilters, NotificationIndexProps } from './types';

const TYPE_OPTIONS = [
    { value: FILTER_ALL, label: 'All types' },
    { value: 'order', label: 'Orders' },
    { value: 'message', label: 'Messages' },
];

const STATUS_OPTIONS = [
    { value: FILTER_ALL, label: 'All' },
    { value: 'unread', label: 'Unread' },
    { value: 'read', label: 'Read' },
];

const SELECT_CLASS =
    'h-11 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';

function visit(filters: Partial<NotificationFilters>, page?: number): void {
    router.get(
        index().url,
        {
            type: filters.type ?? undefined,
            status: filters.status ?? undefined,
            page,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

export default function NotificationManager({
    feed,
    filters,
}: NotificationIndexProps) {
    const change = (patch: Partial<NotificationFilters>) =>
        visit({ ...filters, ...patch });

    return (
        <div className="w-full">
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
                    Notifications
                </h1>
                <button
                    type="button"
                    onClick={() =>
                        router.post(readAll().url, {}, { preserveScroll: true })
                    }
                    className="text-brand-500 hover:text-brand-600 text-sm font-medium"
                >
                    Mark all as read
                </button>
            </div>

            <div className="mb-4 flex flex-wrap gap-3">
                <select
                    aria-label="Type"
                    value={filters.type ?? FILTER_ALL}
                    onChange={(event) =>
                        change({
                            type:
                                event.target.value === FILTER_ALL
                                    ? null
                                    : (event.target
                                          .value as NotificationFilters['type']),
                        })
                    }
                    className={SELECT_CLASS}
                >
                    {TYPE_OPTIONS.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                <select
                    aria-label="Status"
                    value={filters.status ?? FILTER_ALL}
                    onChange={(event) =>
                        change({
                            status:
                                event.target.value === FILTER_ALL
                                    ? null
                                    : (event.target
                                          .value as NotificationFilters['status']),
                        })
                    }
                    className={SELECT_CLASS}
                >
                    {STATUS_OPTIONS.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3">
                {feed.data.length === 0 ? (
                    <p className="px-6 py-12 text-center text-sm text-gray-500">
                        No notifications match these filters.
                    </p>
                ) : (
                    feed.data.map((notification) => (
                        <NotificationItem
                            key={notification.id}
                            notification={notification}
                        />
                    ))
                )}
            </div>

            <TableFooter
                meta={feed.meta}
                onPageChange={(page) => visit(filters, page)}
            />
        </div>
    );
}
